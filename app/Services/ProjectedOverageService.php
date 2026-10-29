<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectedOverageService
{
    /**
     * @return array<string, string>
     */
    public function calculate(int $merchantId): array
    {
        $now = Carbon::now();

        $segments = DB::table('subscription_segments as ss')
            ->join('subscriptions as s', 's.id', '=', 'ss.subscription_id')
            ->where('s.merchant_id', $merchantId)
            ->where('s.status', 'active')
            ->whereColumn('ss.effective_from', '<', 's.current_period_end')
            ->whereColumn('ss.effective_to', '>', 's.current_period_start')
            ->select(
                'ss.id',
                'ss.subscription_id',
                'ss.effective_from',
                'ss.effective_to',
                'ss.included_units',
                'ss.overage_rate',
                'ss.currency',
                's.current_period_start',
                's.current_period_end'
            )
            ->get();

        $projectedRevenue = [];

        foreach ($segments as $segment) {
            $cycleStart = Carbon::parse($segment->current_period_start);
            $cycleEnd = Carbon::parse($segment->current_period_end);

            $segmentStart = Carbon::parse($segment->effective_from);
            $segmentEnd = Carbon::parse($segment->effective_to);

            // Restrict calculation to the current billing cycle.
            $start = $segmentStart->greaterThan($cycleStart)
                ? $segmentStart->copy()
                : $cycleStart->copy();

            $end = $segmentEnd->lessThan($cycleEnd)
                ? $segmentEnd->copy()
                : $cycleEnd->copy();

            $cycleSeconds = $cycleStart->diffInSeconds($cycleEnd);
            $segmentSeconds = $start->diffInSeconds($end);

            if (
                $cycleSeconds <= 0 ||
                $segmentSeconds <= 0 ||
                $start->greaterThanOrEqualTo($now)
            ) {
                continue;
            }

            // Only usage recorded during the observed part of this segment.
            $observedEnd = $end->lessThan($now)
                ? $end->copy()
                : $now->copy();

            $observedSeconds = $start->diffInSeconds($observedEnd);

            if ($observedSeconds <= 0) {
                continue;
            }

            $usedUnits = DB::table('usage_events')
                ->where('segment_id', $segment->id)
                ->where('occurred_at', '>=', $start)
                ->where('occurred_at', '<', $observedEnd)
                ->sum('units');

            // Estimate full-segment usage using the observed usage rate.
            $projectedUnits = bcmul(
                (string) $usedUnits,
                bcdiv((string) $segmentSeconds, (string) $observedSeconds, 12),
                12
            );

            $includedUnits = bcmul(
                (string) $segment->included_units,
                bcdiv((string) $segmentSeconds, (string) $cycleSeconds, 12),
                12
            );

            $overageUnits = bccomp($projectedUnits, $includedUnits, 12) > 0
                ? bcsub($projectedUnits, $includedUnits, 12)
                : '0';

            $projectedRevenue[$segment->currency] = bcadd(
                $projectedRevenue[$segment->currency] ?? '0',
                bcmul($overageUnits, (string) $segment->overage_rate, 12),
                12
            );
        }

        foreach ($projectedRevenue as $currency => $amount) {
            $rounded = bcadd(bcadd($amount, '0.005', 12), '0', 2);
            $projectedRevenue[$currency] = $rounded;
        }

        return $projectedRevenue;
    }
}
