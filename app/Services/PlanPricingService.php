<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlanPricingService
{
    public function get(int $merchantId, int $planId): object
    {
        return Cache::remember(
            $this->cacheKey($merchantId, $planId),
            now()->addMinutes(10),
            function () use ($merchantId, $planId): object {
                $plan = DB::table('plans')
                    ->where('merchant_id', $merchantId)
                    ->where('id', $planId)
                    ->where('is_active', true)
                    ->first();

                if (!$plan) {
                    throw new RuntimeException('Active plan not found for merchant.');
                }

                return $plan;
            }
        );
    }

    public function invalidate(int $merchantId, int $planId): void
    {
        Cache::forget($this->cacheKey($merchantId, $planId));
    }

    private function cacheKey(int $merchantId, int $planId): string
    {
        return 'merchant:' . $merchantId . ':plan:' . $planId . ':pricing';
    }
}
