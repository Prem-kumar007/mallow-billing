<?php

namespace App\Console\Commands;

use App\Services\InvoiceGenerationService;
use Illuminate\Console\Command;

class GenerateDueInvoices extends Command
{
    protected $signature = 'billing:generate-due-invoices';

    protected $description = 'Generate invoices for elapsed subscription billing periods';

    public function handle(InvoiceGenerationService $invoices): int
    {
        $generatedCount = $invoices->generateDueForMerchant();

        $this->info($generatedCount.' due invoice(s) generated.');

        return self::SUCCESS;
    }
}
