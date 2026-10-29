<?php

namespace App\Console\Commands;

use App\Jobs\AggregateDailyUsage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AggregateUsageForDate extends Command
{
    protected $signature = 'billing:aggregate-usage {date : Calendar date in YYYY-MM-DD format}';

    protected $description = 'Queue a retry or reconciliation of daily usage for a date';

    public function handle(): int
    {
        $validator = Validator::make(
            ['date' => $this->argument('date')],
            ['date' => ['required', 'date_format:Y-m-d']]
        );

        if ($validator->fails()) {
            $this->error('The date must be a valid calendar date in YYYY-MM-DD format.');

            return self::INVALID;
        }

        $date = $validator->validated()['date'];
        AggregateDailyUsage::dispatch($date);

        $this->info('Queued daily usage aggregation for '.$date.'.');

        return self::SUCCESS;
    }
}
