<?php

namespace App\Console\Commands;

use App\Jobs\AggregateDailyUsage;
use Illuminate\Console\Command;

class AggregatePreviousDayUsage extends Command
{
    protected $signature = 'billing:aggregate-previous-day-usage';

    protected $description = 'Queue aggregation of the previous day of usage';

    public function handle(): int
    {
        $date = now()->subDay()->toDateString();

        AggregateDailyUsage::dispatch($date);

        $this->info('Queued daily usage aggregation for '.$date.'.');

        return self::SUCCESS;
    }
}
