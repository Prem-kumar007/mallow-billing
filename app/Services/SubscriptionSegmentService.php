<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionSegmentService
{
    public function __construct(
        private readonly PlanPricingService $pricing
    ) {}

    public function subscribe(
        int $merchantId,
        int $customerId,
        int $planId,
        ?string $startedAt = null
    ): int {
        return DB::transaction(function () use (
            $merchantId,
            $customerId,
            $planId,
            $startedAt
        ): int {
            $customerExists = DB::table('customers')
                ->where('merchant_id', $merchantId)
                ->where('id', $customerId)
                ->exists();

            if (! $customerExists) {
                throw new RuntimeException('Customer not found for merchant.');
            }

            $plan = $this->pricing->get($merchantId, $planId);
            $subscriptionStart = Carbon::parse($startedAt ?? now());
            $intervalCount = (int) $plan->billing_interval_count;
            $intervalUnit = $plan->billing_interval_unit;
            $period = BillingPeriod::firstPeriod(
                $subscriptionStart,
                $intervalCount,
                $intervalUnit
            );
            $periodStart = $period['start'];
            $periodEnd = $period['end'];
            $now = now();

            $subscriptionId = DB::table('subscriptions')->insertGetId([
                'merchant_id' => $merchantId,
                'customer_id' => $customerId,
                'plan_id' => $planId,
                'status' => 'active',
                'started_at' => $subscriptionStart->toDateTimeString(),
                'current_period_start' => $periodStart->toDateTimeString(),
                'current_period_end' => $periodEnd->toDateTimeString(),
                'billing_interval_count' => $intervalCount,
                'billing_interval_unit' => $intervalUnit,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('subscription_segments')->insert([
                'subscription_id' => $subscriptionId,
                'plan_id' => $planId,
                'effective_from' => $subscriptionStart->toDateTimeString(),
                'effective_to' => $periodEnd->toDateTimeString(),
                'base_price' => $plan->base_price,
                'currency' => $plan->currency,
                'included_units' => $plan->included_units,
                'overage_rate' => $plan->overage_rate,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return (int) $subscriptionId;
        });
    }

    public function changePlan(
        int $merchantId,
        int $subscriptionId,
        int $planId,
        string $effectiveAt
    ): int {
        return DB::transaction(function () use (
            $merchantId,
            $subscriptionId,
            $planId,
            $effectiveAt
        ): int {
            $subscription = $this->lockSubscription($merchantId, $subscriptionId);
            $effectiveAt = Carbon::parse($effectiveAt);
            $periodStart = Carbon::parse($subscription->current_period_start);
            $periodEnd = Carbon::parse($subscription->current_period_end);

            if ($effectiveAt->lessThanOrEqualTo($periodStart) || $effectiveAt->greaterThanOrEqualTo($periodEnd)) {
                throw new RuntimeException('Plan changes must occur inside the current billing period.');
            }

            $currentSegment = DB::table('subscription_segments')
                ->where('subscription_id', $subscriptionId)
                ->where('effective_from', '<=', $effectiveAt)
                ->where('effective_to', '>', $effectiveAt)
                ->lockForUpdate()
                ->first();

            if (! $currentSegment || Carbon::parse($currentSegment->effective_from)->greaterThanOrEqualTo($effectiveAt)) {
                throw new RuntimeException('No plan segment can be changed at that time.');
            }

            if ((int) $currentSegment->plan_id === $planId) {
                throw new RuntimeException('The subscription is already on this plan.');
            }

            if (DB::table('usage_events')
                ->where('subscription_id', $subscriptionId)
                ->where('occurred_at', '>=', $effectiveAt)
                ->exists()) {
                throw new RuntimeException('Usage already recorded after the requested plan-change time.');
            }

            $plan = $this->pricing->get($merchantId, $planId);
            $now = now();

            DB::table('subscription_segments')
                ->where('id', $currentSegment->id)
                ->update([
                    'effective_to' => $effectiveAt->toDateTimeString(),
                    'updated_at' => $now,
                ]);

            $segmentId = DB::table('subscription_segments')->insertGetId([
                'subscription_id' => $subscriptionId,
                'plan_id' => $planId,
                'effective_from' => $effectiveAt->toDateTimeString(),
                'effective_to' => $currentSegment->effective_to,
                'base_price' => $plan->base_price,
                'currency' => $plan->currency,
                'included_units' => $plan->included_units,
                'overage_rate' => $plan->overage_rate,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('subscriptions')
                ->where('id', $subscriptionId)
                ->update(['plan_id' => $planId, 'updated_at' => $now]);

            return (int) $segmentId;
        });
    }

    private function lockSubscription(int $merchantId, int $subscriptionId): object
    {
        $subscription = DB::table('subscriptions')
            ->where('merchant_id', $merchantId)
            ->where('id', $subscriptionId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if (! $subscription) {
            throw new RuntimeException('Active subscription not found for merchant.');
        }

        return $subscription;
    }
}
