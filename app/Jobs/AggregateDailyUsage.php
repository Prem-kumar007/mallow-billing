<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;

class AggregateDailyUsage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $date
    ) {}

    public function handle(): void
    {
        $start = Carbon::parse($this->date)->startOfDay();
        $end = $start->copy()->addDay();

        DB::transaction(function () use ($start, $end) {
            $date = $start->toDateString();

            DB::table('daily_usage')->where('usage_date', $date)->delete();

            DB::table('usage_events')
                ->where('occurred_at', '>=', $start)
                ->where('occurred_at', '<', $end)
                ->select(
                    'merchant_id',
                    'customer_id',
                    'subscription_id',
                    'segment_id',
                    DB::raw('SUM(units) as total_units')
                )
                ->groupBy(
                    'merchant_id',
                    'customer_id',
                    'subscription_id',
                    'segment_id'
                )
                ->orderBy('merchant_id')
                ->orderBy('customer_id')
                ->orderBy('subscription_id')
                ->orderBy('segment_id')
                ->chunk(500, function ($rows) use ($date) {
                    $now = now();
                    $dailyRows = [];

                    foreach ($rows as $row) {
                        $dailyRows[] = [
                            'merchant_id' => $row->merchant_id,
                            'customer_id' => $row->customer_id,
                            'subscription_id' => $row->subscription_id,
                            'segment_id' => $row->segment_id,
                            'usage_date' => $date,
                            'total_units' => $row->total_units,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($dailyRows !== []) {
                        DB::table('daily_usage')->insert($dailyRows);
                    }
                });
        });
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
