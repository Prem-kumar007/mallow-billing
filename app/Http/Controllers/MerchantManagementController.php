<?php

namespace App\Http\Controllers;

use App\Services\PlanPricingService;
use App\Services\SubscriptionSegmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class MerchantManagementController extends Controller
{
    public function index(int $merchantId): View
    {
        $merchant = DB::table('merchants')->where('id', $merchantId)->first();
        abort_unless($merchant, 404, 'Merchant not found.');

        $customers = DB::table('customers as c')
            ->where('c.merchant_id', $merchantId)
            ->select('c.*')
            ->selectSub(
                DB::table('subscriptions as s')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('s.customer_id', 'c.id'),
                'subscriptions_count'
            )
            ->orderBy('c.name')
            ->get();

        $plans = DB::table('plans as p')
            ->where('p.merchant_id', $merchantId)
            ->select('p.*')
            ->selectSub(
                DB::table('subscriptions as s')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('s.plan_id', 'p.id')
                    ->where('s.merchant_id', $merchantId),
                'subscriptions_count'
            )
            ->orderByDesc('p.is_active')
            ->orderBy('p.name')
            ->get();

        $subscriptions = DB::table('subscriptions as s')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.merchant_id', $merchantId)
            ->select(
                's.*',
                'c.name as customer_name',
                'c.email as customer_email',
                'p.name as plan_name',
                'p.currency',
                'p.is_active as plan_is_active'
            )
            ->orderByRaw("CASE WHEN s.status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('s.created_at')
            ->get();

        return view('merchant.management', compact(
            'merchant',
            'merchantId',
            'customers',
            'plans',
            'subscriptions'
        ));
    }

    public function storeCustomer(Request $request, int $merchantId): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->where('merchant_id', $merchantId),
            ],
        ]);

        DB::table('customers')->insert([
            'merchant_id' => $merchantId,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->backTo($merchantId, 'customers', 'Customer created.');
    }

    public function updateCustomer(Request $request, int $merchantId, int $customerId): RedirectResponse
    {
        $customer = DB::table('customers')
            ->where('merchant_id', $merchantId)
            ->where('id', $customerId)
            ->first();
        abort_unless($customer, 404, 'Customer not found.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')
                    ->where('merchant_id', $merchantId)
                    ->ignore($customerId),
            ],
        ]);

        DB::table('customers')
            ->where('id', $customerId)
            ->where('merchant_id', $merchantId)
            ->update([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'updated_at' => now(),
            ]);

        return $this->backTo($merchantId, 'customers', 'Customer updated.');
    }

    public function destroyCustomer(int $merchantId, int $customerId): RedirectResponse
    {
        $customer = DB::table('customers')
            ->where('merchant_id', $merchantId)
            ->where('id', $customerId)
            ->first();
        abort_unless($customer, 404, 'Customer not found.');

        if (DB::table('subscriptions')->where('customer_id', $customerId)->exists()) {
            return $this->backTo(
                $merchantId,
                'customers',
                'Customers with subscriptions cannot be deleted. Keep the billing history intact.',
                'error'
            );
        }

        DB::table('customers')
            ->where('id', $customerId)
            ->where('merchant_id', $merchantId)
            ->delete();

        return $this->backTo($merchantId, 'customers', 'Customer deleted.');
    }

    public function storePlan(Request $request, int $merchantId): RedirectResponse
    {
        $data = $request->validate($this->planRules($merchantId));
        $now = now();

        DB::table('plans')->insert([
            'merchant_id' => $merchantId,
            'name' => $data['name'],
            'base_price' => $data['base_price'],
            'currency' => strtoupper($data['currency']),
            'billing_interval_count' => $data['billing_interval_count'],
            'billing_interval_unit' => $data['billing_interval_unit'],
            'included_units' => $data['included_units'],
            'overage_rate' => $data['overage_rate'],
            'is_active' => $request->boolean('is_active'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->backTo($merchantId, 'plans', 'Plan created.');
    }

    public function updatePlan(
        Request $request,
        int $merchantId,
        int $planId,
        PlanPricingService $pricing
    ): RedirectResponse {
        $plan = DB::table('plans')
            ->where('merchant_id', $merchantId)
            ->where('id', $planId)
            ->first();
        abort_unless($plan, 404, 'Plan not found.');

        $data = $request->validate($this->planRules($merchantId, $planId));

        DB::table('plans')
            ->where('id', $planId)
            ->where('merchant_id', $merchantId)
            ->update([
                'name' => $data['name'],
                'base_price' => $data['base_price'],
                'currency' => strtoupper($data['currency']),
                'billing_interval_count' => $data['billing_interval_count'],
                'billing_interval_unit' => $data['billing_interval_unit'],
                'included_units' => $data['included_units'],
                'overage_rate' => $data['overage_rate'],
                'is_active' => $request->boolean('is_active'),
                'updated_at' => now(),
            ]);

        $pricing->invalidate($merchantId, $planId);

        return $this->backTo($merchantId, 'plans', 'Plan updated. Existing subscription segments retain their saved pricing.');
    }

    public function storeSubscription(
        Request $request,
        int $merchantId,
        SubscriptionSegmentService $subscriptions
    ): RedirectResponse {
        $data = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('merchant_id', $merchantId),
            ],
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')
                    ->where('merchant_id', $merchantId)
                    ->where('is_active', true),
            ],
            'started_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        try {
            $subscriptions->subscribe(
                $merchantId,
                (int) $data['customer_id'],
                (int) $data['plan_id'],
                $data['started_at']
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['subscription' => $exception->getMessage()])
                ->withInput();
        }

        return $this->backTo($merchantId, 'subscriptions', 'Subscription created with a saved plan-pricing segment.');
    }

    public function changeSubscriptionPlan(
        Request $request,
        int $merchantId,
        int $subscriptionId,
        SubscriptionSegmentService $subscriptions
    ): RedirectResponse {
        $data = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')
                    ->where('merchant_id', $merchantId)
                    ->where('is_active', true),
            ],
            'effective_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        try {
            $subscriptions->changePlan(
                $merchantId,
                $subscriptionId,
                (int) $data['plan_id'],
                $data['effective_at']
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['subscription' => $exception->getMessage()])
                ->withInput();
        }

        return $this->backTo($merchantId, 'subscriptions', 'Plan change saved. Earlier usage keeps its original segment.');
    }

    public function cancelSubscription(int $merchantId, int $subscriptionId): RedirectResponse
    {
        $updated = DB::table('subscriptions')
            ->where('merchant_id', $merchantId)
            ->where('id', $subscriptionId)
            ->where('status', 'active')
            ->update([
                'status' => 'canceled',
                'updated_at' => now(),
            ]);

        abort_unless($updated, 404, 'Active subscription not found.');

        return $this->backTo($merchantId, 'subscriptions', 'Subscription canceled. Billing history has been retained.');
    }

    private function planRules(int $merchantId, ?int $planId = null): array
    {
        $uniqueName = Rule::unique('plans', 'name')
            ->where('merchant_id', $merchantId);

        if ($planId !== null) {
            $uniqueName->ignore($planId);
        }

        return [
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'base_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'billing_interval_count' => ['required', 'integer', 'min:1', 'max:120'],
            'billing_interval_unit' => ['required', Rule::in(['day', 'week', 'month', 'year'])],
            'included_units' => ['required', 'integer', 'min:0'],
            'overage_rate' => ['required', 'numeric', 'min:0', 'max:99999999.9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function backTo(
        int $merchantId,
        string $section,
        string $message,
        string $flashKey = 'success'
    ): RedirectResponse {
        return redirect()
            ->route('merchant.management', ['merchantId' => $merchantId])
            ->withFragment($section)
            ->with($flashKey, $message);
    }
}
