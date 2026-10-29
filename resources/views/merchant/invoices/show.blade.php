<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #{{ $invoice->id }} | {{ $merchant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --ink:#17212f; --muted:#758196; --line:#e9edf3; --brand:#635bdb; }
        body { background:#f6f7fb; color:var(--ink); font-family:"DM Sans",sans-serif; font-size:14px; }
        .sidebar { min-height:100vh; background:#171b2b; color:#fff; }
        .sidebar-inner { position:sticky; top:0; display:flex; flex-direction:column; min-height:100vh; }
        .brand { color:#fff; font:800 20px "Manrope",sans-serif; letter-spacing:-.8px; }
        .brand-mark { display:inline-grid; width:34px; height:34px; place-items:center; border-radius:11px; background:#7169ee; }
        .nav-section-label { color:#798198; font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; }
        .nav-link-custom { display:flex; gap:12px; align-items:center; padding:11px 13px; border-radius:10px; color:#aab1c1; text-decoration:none; margin:4px 0; transition:background .18s,color .18s; }
        .nav-link-custom i { width:18px; font-size:16px; }
        .nav-link-custom:hover,.nav-link-custom.active { color:#fff; background:#292d41; }
        .nav-link-custom.active { box-shadow:inset 3px 0 #827bff; }
        .merchant-card { background:#22263a; border:1px solid #30354b; }
        .merchant-avatar { display:grid; width:36px; height:36px; place-items:center; border-radius:11px; background:#393658; color:#c7c2ff; font-weight:700; }
        .panel { border:1px solid var(--line); border-radius:16px; background:#fff; box-shadow:0 5px 20px rgba(31,42,68,.035); }
        .muted { color:var(--muted); }
        h1,h2 { font-family:"Manrope",sans-serif; }
        h1 { font-size:26px; font-weight:800; letter-spacing:-.8px; }
        .table { --bs-table-bg:transparent; }
        .table > :not(caption) > * > * { padding:13px 10px; vertical-align:middle; }
        .table thead th { color:#8992a2; font-size:10px; text-transform:uppercase; letter-spacing:.6px; }
        @media(max-width:991.98px) { .sidebar { min-height:auto; } .sidebar-inner { position:static; min-height:auto; } .sidebar .nav { flex-direction:row; flex-wrap:wrap; } main { padding:22px !important; } }
        @media print {
            body { background:#fff; }
            .sidebar,.no-print { display:none !important; }
            main { width:100% !important; padding:0 !important; }
            .panel { box-shadow:none; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 sidebar p-3 p-lg-4">
            <div class="sidebar-inner">
            <a class="brand d-flex align-items-center gap-2 text-decoration-none mb-4"
               href="{{ route('merchant.dashboard', ['merchantId' => $merchantId]) }}">
                <span class="brand-mark"><i class="bi bi-layers-half"></i></span>Mallow
            </a>
            @include('merchant.partials.sidebar-navigation', ['page' => 'dashboard'])
            <div class="small text-secondary mt-auto pt-4 d-none d-lg-block">
                Multi-tenant billing workspace
            </div>
            </div>
        </aside>

        <main class="col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
                <div>
                    <div class="small text-uppercase fw-bold text-primary mb-2">MERCHANT WORKSPACE / INVOICES</div>
                    <h1 class="mb-1">Invoice #{{ $invoice->id }}</h1>
                    <div class="muted">{{ $merchant->name }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('merchant.dashboard', ['merchantId' => $merchantId]).'#invoices' }}"
                       class="btn btn-light border">
                        <i class="bi bi-arrow-left me-1"></i>Back to invoices
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
            </header>

            <section class="panel p-4 p-lg-5">
                <div class="d-flex flex-wrap justify-content-between gap-4 mb-5">
                    <div>
                        <div class="text-uppercase small fw-bold muted mb-2">Bill to</div>
                        <div class="fs-5 fw-bold">{{ $invoice->customer_name }}</div>
                        @if ($invoice->customer_email)
                            <div class="muted">{{ $invoice->customer_email }}</div>
                        @endif
                        <div class="small muted mt-1">Customer #{{ $invoice->customer_id }}</div>
                    </div>
                    <div class="text-lg-end">
                        <div class="text-uppercase small fw-bold muted mb-2">Invoice details</div>
                        <div><span class="muted">Invoice:</span> <strong>#{{ $invoice->id }}</strong></div>
                        <div>
                            <span class="muted">Status:</span>
                            <span class="badge {{ $invoice->status === 'issued' ? 'text-bg-success' : 'text-bg-light border text-dark' }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                        <div class="mt-1">
                            <span class="muted">Billing period:</span>
                            {{ \Carbon\Carbon::parse($invoice->period_start)->format('d M Y') }}
                            to
                            {{ \Carbon\Carbon::parse($invoice->period_end)->format('d M Y') }}
                        </div>
                        @if ($invoice->issued_at)
                            <div class="mt-1">
                                <span class="muted">Issued:</span>
                                {{ \Carbon\Carbon::parse($invoice->issued_at)->format('d M Y, H:i') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Unit price</th>
                            <th class="text-end">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->description }}</div>
                                    <div class="small muted">{{ ucfirst(str_replace('_', ' ', $item->item_type)) }}</div>
                                </td>
                                <td class="text-end">{{ number_format((float) $item->quantity, 4) }}</td>
                                <td class="text-end">{{ $invoice->currency }} {{ number_format((float) $item->unit_price, 4) }}</td>
                                <td class="text-end fw-semibold">{{ $invoice->currency }} {{ number_format((float) $item->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 muted">This invoice has no line items.</td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end fs-5">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="small muted mt-3">
                    This invoice records billing charges only. Payment collection is not enabled in this application.
                </div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
