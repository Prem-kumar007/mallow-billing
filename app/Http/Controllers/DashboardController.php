<?php

namespace App\Http\Controllers;

use App\Services\ProjectedOverageService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        int $merchantId,
        ProjectedOverageService $projectedOverageService,
        Request $request
    ): JsonResponse|View {
        abort_unless(
            (int) $request->attributes->get('merchant_id', $merchantId) === $merchantId,
            403,
            'Merchant credentials do not match the requested dashboard.'
        );

        // 1. Find merchant
        $merchant = DB::table('merchants')
            ->where('id', $merchantId)
            ->first();

        abort_unless($merchant, 404, 'Merchant not found');

        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $nextMonth = $monthStart->copy()->addMonth();
        $previousMonth = $monthStart->copy()->subMonth();
        $comparisonSeconds = min(
            (int) $monthStart->diffInSeconds($now),
            (int) $previousMonth->diffInSeconds($monthStart)
        );
        $previousComparisonEnd = $previousMonth->copy()->addSeconds($comparisonSeconds);

        // 2. Active subscriptions and customers
        $activeSubscriptions = DB::table('subscriptions')
            ->where('merchant_id', $merchantId)
            ->where('status', 'active')
            ->count();

        $activeCustomers = DB::table('subscriptions')
            ->where('merchant_id', $merchantId)
            ->where('status', 'active')
            ->distinct()
            ->count('customer_id');

        // 3. New subscriptions this month and previous month
        $thisMonthSubscriptions = DB::table('subscriptions')
            ->where('merchant_id', $merchantId)
            ->where('started_at', '>=', $monthStart)
            ->where('started_at', '<', $nextMonth)
            ->count();

        $previousMonthSubscriptions = DB::table('subscriptions')
            ->where('merchant_id', $merchantId)
            ->where('started_at', '>=', $previousMonth)
            ->where('started_at', '<', $monthStart)
            ->count();

        $subscriptionGrowth = $previousMonthSubscriptions > 0
            ? round(
                (
                    ($thisMonthSubscriptions - $previousMonthSubscriptions)
                    / $previousMonthSubscriptions
                ) * 100,
                1
            )
            : null;

        // 4. Total usage during the current calendar month
        $currentUsage = (int) DB::table('usage_events')
            ->where('merchant_id', $merchantId)
            ->where('occurred_at', '>=', $monthStart)
            ->where('occurred_at', '<', $nextMonth)
            ->sum('units');

        // 5. Top 5 customers by current calendar-month usage
        $topCustomers = DB::table('usage_events as ue')
            ->join('subscriptions as s', 's.id', '=', 'ue.subscription_id')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->where('ue.merchant_id', $merchantId)
            ->where('ue.occurred_at', '>=', $monthStart)
            ->where('ue.occurred_at', '<', $nextMonth)
            ->select(
                'c.id',
                'c.name',
                DB::raw('SUM(ue.units) as total_units')
            )
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total_units')
            ->limit(5)
            ->get();

        $allowanceSegments = DB::table('subscriptions as s')
            ->join('subscription_segments as ss', 'ss.subscription_id', '=', 's.id')
            ->where('s.merchant_id', $merchantId)
            ->where('s.status', 'active')
            ->whereIn('s.customer_id', $topCustomers->pluck('id'))
            ->whereColumn('ss.effective_from', '<', 's.current_period_end')
            ->whereColumn('ss.effective_to', '>', 's.current_period_start')
            ->select(
                's.customer_id',
                's.current_period_start',
                's.current_period_end',
                'ss.effective_from',
                'ss.effective_to',
                'ss.included_units'
            )
            ->get()
            ->groupBy('customer_id');

        $topCustomers = $topCustomers->map(function ($customer) use ($allowanceSegments) {
            $customer->included_allowance = 0.0;

            foreach ($allowanceSegments->get($customer->id, []) as $segment) {
                $cycleStart = Carbon::parse(
                    $segment->current_period_start
                );

                $cycleEnd = Carbon::parse(
                    $segment->current_period_end
                );

                $cycleSeconds = $cycleStart->diffInSeconds($cycleEnd);

                if ($cycleSeconds <= 0) {
                    continue;
                }

                $segmentStart = Carbon::parse($segment->effective_from);
                $segmentEnd = Carbon::parse($segment->effective_to);

                $start = $segmentStart->greaterThan($cycleStart)
                    ? $segmentStart->copy()
                    : $cycleStart->copy();

                $end = $segmentEnd->lessThan($cycleEnd)
                    ? $segmentEnd->copy()
                    : $cycleEnd->copy();

                if ($end->lessThanOrEqualTo($start)) {
                    continue;
                }

                $segmentSeconds = $start->diffInSeconds($end);

                $customer->included_allowance +=
                    (float) $segment->included_units
                    * ($segmentSeconds / $cycleSeconds);
            }

            $customer->included_allowance = round(
                $customer->included_allowance,
                2
            );

            $customer->usage_percentage =
                $customer->included_allowance > 0
                ? round(
                    (
                        (int) $customer->total_units
                        / $customer->included_allowance
                    ) * 100,
                    1
                )
                : 0;

            return $customer;
        });

        // 6. Usage by customer for current and previous calendar months
        $currentByCustomer = DB::table('usage_events as ue')
            ->join('subscriptions as s', 's.id', '=', 'ue.subscription_id')
            ->where('ue.merchant_id', $merchantId)
            ->where('ue.occurred_at', '>=', $monthStart)
            ->where('ue.occurred_at', '<', $nextMonth)
            ->select(
                's.customer_id',
                DB::raw('SUM(ue.units) as total_units')
            )
            ->groupBy('s.customer_id')
            ->pluck('total_units', 'customer_id');

        $previousByCustomer = DB::table('usage_events as ue')
            ->join('subscriptions as s', 's.id', '=', 'ue.subscription_id')
            ->where('ue.merchant_id', $merchantId)
            ->where('ue.occurred_at', '>=', $previousMonth)
            ->where('ue.occurred_at', '<', $previousComparisonEnd)
            ->select(
                's.customer_id',
                DB::raw('SUM(ue.units) as total_units')
            )
            ->groupBy('s.customer_id')
            ->pluck('total_units', 'customer_id');

        // 7. Customers whose usage dropped by more than 50%
        $churnRisk = collect();
        $churnCandidates = $previousByCustomer->filter(
            fn($previousUnits, $customerId): bool =>
            (int) $previousUnits > 0
                && (int) ($currentByCustomer[$customerId] ?? 0) < ((int) $previousUnits * 0.5)
        );
        $churnCustomerNames = DB::table('customers')
            ->where('merchant_id', $merchantId)
            ->whereIn('id', $churnCandidates->keys())
            ->pluck('name', 'id');

        foreach ($churnCandidates as $customerId => $previousUnits) {
            $previousUnits = (int) $previousUnits;
            $currentUnits = (int) ($currentByCustomer[$customerId] ?? 0);

            if (! isset($churnCustomerNames[$customerId])) {
                continue;
            }

            $churnRisk->push((object) [
                'id' => $customerId,
                'name' => $churnCustomerNames[$customerId],
                'previous_units' => $previousUnits,
                'current_units' => $currentUnits,
                'drop_percentage' => round(
                    (($previousUnits - $currentUnits) / $previousUnits) * 100,
                    1
                ),
            ]);
        }

        // 8. Daily usage trend for the last 30 days
        $trendStart = $now->copy()->subDays(29)->startOfDay();
        $trendEnd = $now->copy()->addDay()->startOfDay();

        $dailyRows = DB::table('usage_events')
            ->where('merchant_id', $merchantId)
            ->where('occurred_at', '>=', $trendStart)
            ->where('occurred_at', '<', $trendEnd)
            ->selectRaw(
                'DATE(occurred_at) as usage_date, SUM(units) as total_units'
            )
            ->groupByRaw('DATE(occurred_at)')
            ->orderBy('usage_date')
            ->get()
            ->keyBy('usage_date');

        $trendLabels = [];
        $trendValues = [];

        for (
            $date = $trendStart->copy();
            $date < $trendEnd;
            $date->addDay()
        ) {
            $key = $date->toDateString();

            $trendLabels[] = $date->format('d M');
            $trendValues[] = (int) ($dailyRows[$key]->total_units ?? 0);
        }

        // 9. Active subscriptions and their current plan segments
        $activePlans = DB::table('subscriptions as s')
            ->join('subscription_segments as ss', function ($join) use ($now) {
                $join->on('ss.subscription_id', '=', 's.id')
                    ->where('ss.effective_from', '<=', $now)
                    ->where('ss.effective_to', '>', $now);
            })
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->join('plans as p', 'p.id', '=', 'ss.plan_id')
            ->where('s.merchant_id', $merchantId)
            ->where('s.status', 'active')
            ->select(
                's.id as subscription_id',
                'c.name as customer_name',
                'p.name as plan_name',
                'ss.base_price',
                'ss.included_units',
                'ss.overage_rate',
                'ss.currency',
                's.current_period_start',
                's.current_period_end'
            )
            ->orderBy('c.name')
            ->limit(10)
            ->get();

        // 10. Usage and included allowance for current billing cycles
        $cycleUsage = 0;
        $cycleAllowance = 0;

        $subscriptions = DB::table('subscriptions')
            ->where('merchant_id', $merchantId)
            ->where('status', 'active')
            ->get();

        foreach ($subscriptions as $subscription) {
            if (
                !$subscription->current_period_start ||
                !$subscription->current_period_end
            ) {
                continue;
            }

            $cycleStart = Carbon::parse(
                $subscription->current_period_start
            );

            $cycleEnd = Carbon::parse(
                $subscription->current_period_end
            );

            $cycleSeconds = $cycleStart->diffInSeconds($cycleEnd);

            if ($cycleSeconds <= 0) {
                continue;
            }

            $segments = DB::table('subscription_segments')
                ->where('subscription_id', $subscription->id)
                ->where('effective_from', '<', $cycleEnd)
                ->where('effective_to', '>', $cycleStart)
                ->get();

            foreach ($segments as $segment) {
                $segmentStart = Carbon::parse(
                    $segment->effective_from
                );

                $segmentEnd = Carbon::parse(
                    $segment->effective_to
                );

                $start = $segmentStart->greaterThan($cycleStart)
                    ? $segmentStart->copy()
                    : $cycleStart->copy();

                $end = $segmentEnd->lessThan($cycleEnd)
                    ? $segmentEnd->copy()
                    : $cycleEnd->copy();

                if ($end->lessThanOrEqualTo($start)) {
                    continue;
                }

                // Count usage only up to the present time.
                $observedEnd = $end->lessThan($now)
                    ? $end->copy()
                    : $now->copy();

                if ($observedEnd->greaterThan($start)) {
                    $cycleUsage += (int) DB::table('usage_events')
                        ->where('subscription_id', $subscription->id)
                        ->where('segment_id', $segment->id)
                        ->where('occurred_at', '>=', $start)
                        ->where('occurred_at', '<', $observedEnd)
                        ->sum('units');
                }

                // Included units are prorated over the full segment.
                $segmentSeconds = $start->diffInSeconds($end);

                $cycleAllowance += (int) floor(
                    (float) $segment->included_units
                        * ($segmentSeconds / $cycleSeconds)
                );
            }
        }

        $allowancePercentage = $cycleAllowance > 0
            ? min(
                100,
                round(($cycleUsage / $cycleAllowance) * 100, 1)
            )
            : 0;

        // 11. Projected overage revenue
        $projectedOverageRevenue = $projectedOverageService
            ->calculate($merchantId);

        // 12. Recent invoices
        $recentInvoices = DB::table('invoices')
            ->where('merchant_id', $merchantId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 13. Basic system status
        $databaseStatus = 'Healthy';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            report($e);
            $databaseStatus = 'Unavailable';
        }

        $usageApiStatus = collect(
            app('router')->getRoutes()->getRoutes()
        )->contains(function ($route) {
            return $route->uri() === 'api/usage'
                && in_array('POST', $route->methods());
        }) ? 'Configured' : 'Missing';


        // 13. System status (informational)
        $systemStatus = [
            'pricing_cache' => ucfirst(config('cache.default', 'array')) . ', TTL 10m',
            'aggregation_job' => 'queued daily, 500 grouped rows/batch',
            'usage_endpoint' => $usageApiStatus . ', 120 req/min per merchant',
            'database' => $databaseStatus,
        ];

        // 14. Send all data to the dashboard view
        if ($request->routeIs('api.merchant.dashboard') || $request->expectsJson()) {
            return response()->json([
                'data' => [
                    'merchant' => [
                        'id' => $merchantId,
                        'name' => $merchant->name,
                    ],
                    'periods' => [
                        'timezone' => config('app.timezone'),
                        'current_month' => [
                            'start' => $monthStart->toIso8601String(),
                            'end' => $nextMonth->toIso8601String(),
                        ],
                        'previous_month_comparison' => [
                            'start' => $previousMonth->toIso8601String(),
                            'end' => $previousComparisonEnd->toIso8601String(),
                        ],
                    ],
                    'top_customers_by_usage' => $topCustomers->map(fn($customer) => [
                        'customer_id' => (int) $customer->id,
                        'customer_name' => $customer->name,
                        'usage_units' => (int) $customer->total_units,
                    ])->values(),
                    'projected_overage_revenue' => collect($projectedOverageRevenue)
                        ->map(fn(string $amount, string $currency) => [
                            'currency' => $currency,
                            'amount' => $amount,
                        ])
                        ->values(),
                    'customers_with_usage_drop_over_50_percent' => $churnRisk
                        ->map(fn($customer) => [
                            'customer_id' => (int) $customer->id,
                            'customer_name' => $customer->name,
                            'previous_usage_units' => (int) $customer->previous_units,
                            'current_usage_units' => (int) $customer->current_units,
                            'drop_percentage' => (float) $customer->drop_percentage,
                        ])
                        ->values(),
                ],
            ]);
        }

        return view('dashboard', compact(
            'merchant',
            'merchantId',
            'now',
            'activeSubscriptions',
            'activeCustomers',
            'thisMonthSubscriptions',
            'previousMonthSubscriptions',
            'subscriptionGrowth',
            'currentUsage',
            'topCustomers',
            'churnRisk',
            'trendLabels',
            'trendValues',
            'activePlans',
            'cycleUsage',
            'cycleAllowance',
            'allowancePercentage',
            'projectedOverageRevenue',
            'recentInvoices',
            'databaseStatus',
            'usageApiStatus',
            'systemStatus'
        ));
    }
}
