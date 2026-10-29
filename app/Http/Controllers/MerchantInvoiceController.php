<?php

namespace App\Http\Controllers;

use App\Services\InvoiceGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MerchantInvoiceController extends Controller
{
    public function show(int $merchantId, int $invoiceId): View
    {
        $merchant = DB::table('merchants')
            ->where('id', $merchantId)
            ->first();

        abort_unless($merchant, 404, 'Merchant not found.');

        $invoice = DB::table('invoices as i')
            ->join('subscriptions as s', 's.id', '=', 'i.subscription_id')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->where('i.merchant_id', $merchantId)
            ->where('i.id', $invoiceId)
            ->select(
                'i.*',
                's.customer_id',
                'c.name as customer_name',
                'c.email as customer_email'
            )
            ->first();

        abort_unless($invoice, 404, 'Invoice not found.');

        $items = DB::table('invoice_items')
            ->where('invoice_id', $invoiceId)
            ->orderBy('id')
            ->get();

        return view('merchant.invoices.show', compact(
            'merchant',
            'merchantId',
            'invoice',
            'items'
        ));
    }

    public function generateDue(int $merchantId, InvoiceGenerationService $invoices): RedirectResponse
    {
        $generatedCount = $invoices->generateDueForMerchant($merchantId);

        $message = $generatedCount > 0
            ? $generatedCount.' due invoice(s) generated.'
            : 'No invoices are due yet. Invoices can be generated after a billing period ends.';

        return redirect()
            ->route('merchant.dashboard', ['merchantId' => $merchantId])
            ->with('invoice_generation_message', $message);
    }
}
