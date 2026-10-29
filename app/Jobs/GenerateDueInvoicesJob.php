<?php

namespace App\Jobs;

use App\Services\InvoiceGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateDueInvoicesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(InvoiceGenerationService $invoices): void
    {
        $invoices->generateDueForMerchant();
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
