<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceGenerationService
{
    public function __construct(
        private readonly PlanPricingService $pricing
    ) {}

    public function generate(
        int $subscriptionId,
        string $periodStart,
        string $periodEnd
    ): int {
        $start = Carbon::parse($periodStart);
        $end = Carbon::parse($periodEnd);

        if ($start->greaterThanOrEqualTo($end)) {
            throw new RuntimeException('Invalid billing period.');
        }

        return DB::transaction(function () use (
            $subscriptionId,
            $start,
            $end
        ) {
            $subscription = DB::table('subscriptions')
                ->where('id', $subscriptionId)
                ->lockForUpdate()
                ->first();

            if (!$subscription) {
                throw new RuntimeException('Subscription not found.');
            }

            $existing = DB::table('invoices')
                ->where('merchant_id', $subscription->merchant_id)
                ->where('subscription_id', $subscriptionId)
                ->where('period_start', $start->toDateTimeString())
                ->where('period_end', $end->toDateTimeString())
                ->first();

            if ($existing) {
                return (int) $existing->id;
            }

            $segments = DB::table('subscription_segments')
                ->where('subscription_id', $subscriptionId)
                ->where('effective_from', '<', $end)
                ->where('effective_to', '>', $start)
                ->orderBy('effective_from')
                ->get();

            $cycleSeconds = $start->diffInSeconds($end);

            if ($cycleSeconds <= 0) {
                throw new RuntimeException('Invalid billing period.');
            }

            $subtotal = '0.00';
            $currency = null;
            $items = [];

            foreach ($segments as $segment) {
                $segmentStart = Carbon::parse($segment->effective_from);
                $segmentEnd = Carbon::parse($segment->effective_to);

                $billStart = $segmentStart->greaterThan($start)
                    ? $segmentStart->copy()
                    : $start->copy();

                $billEnd = $segmentEnd->lessThan($end)
                    ? $segmentEnd->copy()
                    : $end->copy();

                $billableSeconds = $billStart->diffInSeconds($billEnd);

                if ($billableSeconds <= 0) {
                    continue;
                }

                if ($currency === null) {
                    $currency = $segment->currency;
                } elseif ($currency !== $segment->currency) {
                    throw new RuntimeException(
                        'Cannot combine different currencies in one invoice.'
                    );
                }

                // Prorated base fee.
                $durationRatio = bcdiv(
                    (string) $billableSeconds,
                    (string) $cycleSeconds,
                    12
                );
                $baseCharge = $this->roundDecimal(
                    bcmul((string) $segment->base_price, $durationRatio, 12),
                    2
                );

                $items[] = [
                    'segment_id' => $segment->id,
                    'description' => $segment->plan_id
                        ? 'Plan #' . $segment->plan_id . ' base fee (prorated)'
                        : 'Base fee (prorated)',
                        'item_type' => 'base_fee',
                        'quantity' => $this->roundDecimal($durationRatio, 4),
                        'unit_price' => $segment->base_price,
                        'amount' => $baseCharge,
                ];

                $usedUnits = (string) DB::table('usage_events')
                        ->where('merchant_id', $subscription->merchant_id)
                    ->where('subscription_id', $subscriptionId)
                    ->where('segment_id', $segment->id)
                    ->where('occurred_at', '>=', $billStart)
                    ->where('occurred_at', '<', $billEnd)
                    ->sum('units');

                $includedUnits = bcmul(
                    (string) $segment->included_units,
                    $durationRatio,
                    12
                );

                $overageUnits = bccomp($usedUnits, $includedUnits, 12) > 0
                    ? bcsub($usedUnits, $includedUnits, 12)
                    : '0';

                $overageCharge = $this->roundDecimal(
                    bcmul($overageUnits, (string) $segment->overage_rate, 12),
                    2
                );

                if (bccomp($overageUnits, '0', 12) > 0) {
                    $items[] = [
                        'segment_id' => $segment->id,
                        'description' => 'Usage overage for plan #'
                            . $segment->plan_id,
                        'item_type' => 'overage',
                        'quantity' => $this->roundDecimal($overageUnits, 4),
                        'unit_price' => $segment->overage_rate,
                        'amount' => $overageCharge,
                    ];
                }

                $subtotal = bcadd($subtotal, $baseCharge, 2);
                $subtotal = bcadd($subtotal, $overageCharge, 2);
            }

            if ($currency === null) {
                throw new RuntimeException(
                    'No plan segment found for this billing period.'
                );
            }

            $now = now();

            $invoiceId = DB::table('invoices')->insertGetId([
                'merchant_id' => $subscription->merchant_id,
                'subscription_id' => $subscriptionId,
                'period_start' => $start->toDateTimeString(),
                'period_end' => $end->toDateTimeString(),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => $currency,
                'status' => 'issued',
                'issued_at' => now(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($items as $item) {
                DB::table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'segment_id' => $item['segment_id'],
                    'description' => $item['description'],
                    'item_type' => $item['item_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return (int) $invoiceId;
        });
    }

    /**
     * Generate every elapsed billing period and advance the subscription.
     *
     * @return list<int>
     */
    public function generateDue(int $subscriptionId): array
    {
        return DB::transaction(function () use ($subscriptionId): array {
            $subscription = DB::table('subscriptions')
                ->where('id', $subscriptionId)
                ->lockForUpdate()
                ->first();

            if (!$subscription) {
                throw new RuntimeException('Subscription not found.');
            }

            $invoiceIds = [];
            $now = Carbon::now();

            while (Carbon::parse($subscription->current_period_end)->lessThanOrEqualTo($now)) {
                $periodStart = Carbon::parse($subscription->current_period_start);
                $periodEnd = Carbon::parse($subscription->current_period_end);
                $invoiceIds[] = $this->generate(
                    $subscriptionId,
                    $periodStart->toDateTimeString(),
                    $periodEnd->toDateTimeString()
                );

                $nextPeriodEnd = BillingPeriod::nextEnd(
                    $periodEnd,
                    (int) $subscription->billing_interval_count,
                    $subscription->billing_interval_unit
                );
                $plan = $this->pricing->get(
                    (int) $subscription->merchant_id,
                    (int) $subscription->plan_id
                );
                $now = now();

                DB::table('subscriptions')
                    ->where('id', $subscriptionId)
                    ->update([
                        'current_period_start' => $periodEnd->toDateTimeString(),
                        'current_period_end' => $nextPeriodEnd->toDateTimeString(),
                        'updated_at' => now(),
                    ]);

                DB::table('subscription_segments')->insert([
                    'subscription_id' => $subscriptionId,
                    'plan_id' => $subscription->plan_id,
                    'effective_from' => $periodEnd->toDateTimeString(),
                    'effective_to' => $nextPeriodEnd->toDateTimeString(),
                    'base_price' => $plan->base_price,
                    'currency' => $plan->currency,
                    'included_units' => $plan->included_units,
                    'overage_rate' => $plan->overage_rate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $subscription->current_period_start = $periodEnd->toDateTimeString();
                $subscription->current_period_end = $nextPeriodEnd->toDateTimeString();
            }

            return $invoiceIds;
        });
    }

    public function generateDueForMerchant(?int $merchantId = null): int
    {
        $generatedCount = 0;
        $subscriptions = DB::table('subscriptions')
            ->where('status', 'active')
            ->where('current_period_end', '<=', now())
            ->when(
                $merchantId !== null,
                fn ($query) => $query->where('merchant_id', $merchantId)
            )
            ->orderBy('id');

        $subscriptions->chunkById(100, function ($rows) use (&$generatedCount): void {
            foreach ($rows as $subscription) {
                $generatedCount += count($this->generateDue((int) $subscription->id));
            }
        });

        return $generatedCount;
    }

    private function roundDecimal(string $value, int $scale): string
    {
        $halfUnit = '0.'.str_repeat('0', $scale).'5';

        return bcadd(bcadd($value, $halfUnit, $scale + 8), '0', $scale);
    }

}
