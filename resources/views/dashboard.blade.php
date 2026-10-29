
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $merchant->name }} | Billing Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { --ink:#17212f; --muted:#758196; --line:#e9edf3; --brand:#635bdb; --brand-soft:#f0efff; }
        * { box-sizing:border-box; }
        body { background:#f6f7fb; color:var(--ink); font-family:"DM Sans",sans-serif; font-size:14px; }
        .app-shell { min-height:100vh; }
        .sidebar { background:#171b2b; color:#fff; min-height:100vh; }
        .sidebar-inner { position:sticky; top:0; display:flex; flex-direction:column; min-height:100vh; }
        .brand { font:800 21px "Manrope",sans-serif; letter-spacing:-.8px; }
        .brand-mark { display:inline-grid; width:34px; height:34px; place-items:center; border-radius:11px; background:#7169ee; color:#fff; }
        .nav-section-label { color:#798198; font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; }
        .nav-link-custom { display:flex; gap:12px; align-items:center; padding:11px 13px; border-radius:10px; color:#aab1c1; text-decoration:none; margin:4px 0; transition:background .18s,color .18s; }
        .nav-link-custom i { width:18px; font-size:16px; }
        .nav-link-custom:hover,.nav-link-custom.active { color:#fff; background:#292d41; }
        .nav-link-custom.active { box-shadow:inset 3px 0 #827bff; }
        .merchant-card { background:#22263a; border:1px solid #30354b; }
        .merchant-avatar { display:grid; width:36px; height:36px; place-items:center; border-radius:11px; background:#393658; color:#c7c2ff; font-weight:700; }
        .muted,.small-label { color:var(--muted); }
        .metric,.panel { background:#fff; border:1px solid var(--line); border-radius:16px; box-shadow:0 5px 20px rgba(31,42,68,.035); }
        .metric { position:relative; overflow:hidden; padding:21px; height:100%; transition:transform .2s,box-shadow .2s; }
        .metric:hover { transform:translateY(-2px); box-shadow:0 12px 26px rgba(31,42,68,.08); }
        .metric-label { color:#69758a; font-size:12px; font-weight:700; letter-spacing:.15px; }
        .metric-value { font:800 29px "Manrope",sans-serif; letter-spacing:-1px; margin:15px 0 5px; }
        .metric-icon { width:42px; height:42px; border-radius:13px; display:grid; place-items:center; background:var(--brand-soft); color:var(--brand); font-size:19px; }
        .metric-icon.icon-green { background:#e9f8f0; color:#1c9a61; }
        .metric-icon.icon-amber { background:#fff4df; color:#d28a16; }
        .metric-icon.icon-blue { background:#e8f3ff; color:#3482d3; }
        .metric-foot { display:flex; align-items:center; gap:6px; color:#8791a2; font-size:11px; }
        .panel { padding:22px; height:100%; }
        .panel-title { font:750 15px "Manrope",sans-serif; letter-spacing:-.2px; }
        .panel-subtitle { color:#8791a2; font-size:12px; margin-top:5px; }
        .section-heading { font:800 25px "Manrope",sans-serif; letter-spacing:-.8px; }
        .small-label { font-size:10px; font-weight:700; letter-spacing:.9px; }
        .table { --bs-table-bg:transparent; }
        .table > :not(caption) > * > * { padding:13px 10px; vertical-align:middle; }
        .table thead th { color:#8992a2; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.65px; border-bottom-color:#edf0f5; white-space:nowrap; }
        .table tbody tr:last-child td { border-bottom:0; }
        .table tbody td { color:#465166; }
        .progress { height:7px; background:#eef0f5; border-radius:99px; }
        .progress-bar { border-radius:99px; background:#6b63e6; }
        .progress-bar.bg-danger { background:#dc3545 !important; }
        .avatar { width:34px; height:34px; flex:0 0 34px; border-radius:11px; display:grid; place-items:center; background:#f0efff; color:#5b54c6; font-weight:700; }
        .date-chip { background:#fff; border:1px solid var(--line); border-radius:12px; padding:9px 13px; }
        .btn-refresh { color:#5550b8; background:#f0efff; border:1px solid #e4e2ff; border-radius:10px; font-weight:700; }
        .btn-refresh:hover { color:#fff; background:#635bdb; border-color:#635bdb; }
        .risk-item { border-color:#edf0f5 !important; background:#fcfcfe; }
        .status-dot { display:inline-block; width:7px; height:7px; margin-right:6px; border-radius:50%; background:#20ad6c; }
        .search-box { max-width:220px; border-radius:10px; font-size:12px; }
        .search-box:focus { border-color:#b7b3ff; box-shadow:0 0 0 .2rem rgba(99,91,219,.1); }
        .topbar { border-bottom:1px solid var(--line); }
        .chart-container { position:relative; height:280px; }
        #usageChart { width:100% !important; height:100% !important; }
        @media(max-width:991.98px) {
            .sidebar { min-height:auto; }
            .sidebar-inner { position:static; min-height:auto; }
            .sidebar-nav { padding-top:16px; }
            main { padding:24px !important; }
        }
        @media(max-width:575.98px) {
            main { padding:18px !important; }
            .section-heading { font-size:22px; }
            .metric { padding:17px; }
            .metric-value { font-size:25px; }
            .panel { padding:17px; }
            .chart-container { height:230px; }
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <aside class="col-lg-2 sidebar p-3 p-lg-4">
            <div class="sidebar-inner">
            <div class="d-flex align-items-center justify-content-between">
                <a class="brand d-flex align-items-center gap-2 text-white text-decoration-none" href="{{ route('merchant.dashboard', ['merchantId' => $merchantId]) }}">
                    <span class="brand-mark"><i class="bi bi-layers-half"></i></span>Mallow
                </a>
                <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarNavigation" aria-expanded="false" aria-controls="sidebarNavigation" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
            </div>
            <div class="small text-secondary mb-4 mt-2">Usage & Billing Platform</div>

            <div class="collapse d-lg-block sidebar-nav" id="sidebarNavigation">
            @include('merchant.partials.sidebar-navigation', ['page' => 'dashboard'])
            </div>

            <div class="small text-secondary mt-auto pt-4 d-none d-lg-block">
                Multi-tenant billing workspace
            </div>
            </div>
        </aside>

        <main class="col-lg-10 p-3 p-lg-4">
            <div class="topbar d-flex flex-wrap justify-content-between align-items-center gap-3 pb-4 mb-4">
                <div>
                    <div class="small-label mb-2">MERCHANT WORKSPACE <span class="mx-1">/</span> OVERVIEW</div>
                    <h1 class="section-heading mb-1">Billing dashboard</h1>
                    <div class="muted">
                        Usage, subscriptions and revenue for {{ $merchant->name }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="date-chip">
                        <div class="small-label">REPORT DATE</div>
                        <div class="fw-semibold">{{ $now->format('d M Y') }}</div>
                    </div>
                    <button class="btn btn-refresh px-3 py-2" type="button" onclick="window.location.reload()" aria-label="Refresh dashboard">
                        <i class="bi bi-arrow-clockwise me-sm-1"></i><span class="d-none d-sm-inline">Refresh</span>
                    </button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="metric">
                        <div class="d-flex justify-content-between">
                            <div class="metric-label">Current Month Usage</div>
                            <div class="metric-icon icon-blue"><i class="bi bi-speedometer2"></i></div>
                        </div>
                        <div class="metric-value">{{ number_format($currentUsage) }}</div>
                        <div class="metric-foot"><i class="bi bi-calendar3"></i> Units recorded this month</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="metric">
                        <div class="d-flex justify-content-between">
                            <div class="metric-label">Projected overage revenue</div>
                            <div class="metric-icon icon-green"><i class="bi bi-cash-stack"></i></div>
                        </div>
                        <div class="metric-value fs-5">
                            @forelse ($projectedOverageRevenue as $currency => $amount)
                                <div>{{ $currency }} {{ number_format($amount, 2) }}</div>
                            @empty
                                <div>—</div>
                            @endforelse
                        </div>
                        <div class="metric-foot"><i class="bi bi-graph-up-arrow"></i> Estimated for current billing cycles</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="metric">
                        <div class="d-flex justify-content-between">
                            <div class="metric-label">Active subscriptions</div>
                            <div class="metric-icon icon-amber"><i class="bi bi-arrow-repeat"></i></div>
                        </div>
                        <div class="metric-value">{{ number_format($activeSubscriptions) }}</div>
                        <div class="metric-foot"><i class="bi bi-check-circle"></i> Subscriptions marked active</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="metric">
                        <div class="d-flex justify-content-between">
                            <div class="metric-label">Active customers</div>
                            <div class="metric-icon"><i class="bi bi-people"></i></div>
                        </div>
                        <div class="metric-value">{{ number_format($activeCustomers) }}</div>
                        <div class="metric-foot"><i class="bi bi-person-check"></i> Customers with active subscriptions</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-8" id="usage">
                    <section class="panel">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <div class="panel-title"><i class="bi bi-activity text-primary me-2"></i>Daily usage trend</div>
                                <div class="panel-subtitle">Daily recorded usage · last 30 days</div>
                            </div>
                            <span class="badge rounded-pill text-bg-light border px-3 py-2">Last 30 days</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="usageChart" aria-label="Daily usage over the last 30 days" role="img"></canvas>
                        </div>
                    </section>
                </div>

                <div class="col-xl-4">
                    <section class="panel">
                        <div class="panel-title"><i class="bi bi-speedometer2 text-primary me-2"></i>Current cycle usage</div>
                        <div class="panel-subtitle mb-4">
                            Recorded usage compared with prorated included allowance
                        </div>

                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div>
                                <div class="metric-value fs-4 mb-1">
                                    {{ number_format($cycleUsage) }}
                                </div>
                                <div class="small-label">Units used</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">
                                    {{ number_format($cycleAllowance) }}
                                </div>
                                <div class="small-label">Included allowance</div>
                            </div>
                        </div>

                        <div class="progress mb-2" aria-label="Cycle allowance used">
                            <div class="progress-bar"
                                 role="progressbar"
                                 aria-valuenow="{{ min(100, max(0, $allowancePercentage)) }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 style="width:{{ min(100, max(0, $allowancePercentage)) }}%">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between small muted">
                            <span>{{ $allowancePercentage }}% of allowance</span>
                            @if ($cycleUsage > $cycleAllowance)
                                <span class="text-danger fw-semibold">Over allowance</span>
                            @else
                                <span>Within allowance</span>
                            @endif
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="muted">Usage recorded</span>
                            <strong>{{ number_format($cycleUsage) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="muted">Allowance remaining</span>
                            <strong>{{ number_format(max(0, $cycleAllowance - $cycleUsage)) }}</strong>
                        </div>

                        <div class="small muted mt-3">
                            Allowance is prorated for each subscription segment.
                        </div>
                    </section>
                </div>
            </div>

            <div class="row g-3 mb-4 align-items-stretch">
            <div class="col-lg-4" id="customers">
                <section class="panel h-100">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="panel-title"><i class="bi bi-trophy text-warning me-2"></i>Top 5 customers</div>
                            <div class="panel-subtitle mb-3">
                        Ranked by usage in the current calendar month
                            </div>
                        </div>
                        <span class="badge rounded-pill text-bg-light border">Top 5</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-end">Usage</th>
                                    <th class="text-end">% of Allowance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topCustomers as $customer)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar">
                                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                                </div>
                                                <div class="fw-semibold">{{ $customer->name }}</div>
                                            </div>
                                        </td>

                                        <td class="text-end fw-bold">
                                            {{ number_format($customer->total_units) }}
                                        </td>

                                        <td style="min-width:130px">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>{{ $customer->usage_percentage }}%</span>
                                                <span class="muted">
                                                    {{ number_format($customer->included_allowance) }}
                                                </span>
                                            </div>

                                            <div class="progress">
                                                <div
                                                    class="progress-bar {{ $customer->usage_percentage >= 100 ? 'bg-danger' : '' }}"
                                                    role="progressbar"
                                                    style="width: {{ min(100, $customer->usage_percentage) }}%">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 muted">
                                            No usage recorded this month.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        

            <div class="col-lg-4 col-xl-4" id="churn">
                <section class="panel h-100">
                    <div class="panel-title"><i class="bi bi-graph-down-arrow text-danger me-2"></i>Churn risk</div>
                    <div class="panel-subtitle mb-3">
                        Usage down by >50% vs the same elapsed period last month
                    </div>

                    @forelse ($churnRisk as $customer)
                        <div class="risk-item border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <div class="fw-semibold">{{ $customer->name }}</div>
                                <span class="badge text-bg-danger">
                                    -{{ $customer->drop_percentage }}%
                                </span>
                            </div>
                            <div class="d-flex justify-content-between small muted mt-3">
                                <span>Previous month</span>
                                <span>{{ number_format($customer->previous_units) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small mt-1">
                                <span class="muted">Current month</span>
                                <span class="fw-semibold">
                                    {{ number_format($customer->current_units) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 muted">
                            <i class="bi bi-check-circle fs-3 d-block mb-2"></i>
                            No customers currently meet the churn-risk rule.
                        </div>
                    @endforelse
                </section>
            </div>

            <div class="col-lg-4 col-xl-4" id="system-status">
                <section class="panel h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="panel-title"><i class="bi bi-shield-check text-success me-2"></i>System status</div>
                        @if ($systemStatus['database'] === 'Healthy' && str_starts_with($systemStatus['usage_endpoint'], 'Configured'))
                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                                <span class="status-dot"></span>Operational
                            </span>
                        @else
                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                <i class="bi bi-exclamation-circle me-1"></i>Attention
                            </span>
                        @endif
                    </div>

                    <div class="status-row d-flex flex-wrap justify-content-between gap-2 py-3 border-bottom">
                        <span class="muted"><i class="bi bi-lightning-charge me-2"></i>Pricing cache</span>
                        <span class="small fw-semibold text-end">{{ $systemStatus['pricing_cache'] }}</span>
                    </div>
                    <div class="status-row d-flex flex-wrap justify-content-between gap-2 py-3 border-bottom">
                        <span class="muted"><i class="bi bi-layers me-2"></i>Usage aggregation</span>
                        <span class="small fw-semibold text-end">{{ $systemStatus['aggregation_job'] }}</span>
                    </div>
                    <div class="status-row d-flex flex-wrap justify-content-between gap-2 py-3 border-bottom">
                        <span class="muted"><i class="bi bi-shield-lock me-2"></i>Usage endpoint</span>
                        <span class="small fw-semibold text-end">{{ $systemStatus['usage_endpoint'] }}</span>
                    </div>
                    <div class="status-row d-flex flex-wrap justify-content-between gap-2 py-3">
                        <span class="muted"><i class="bi bi-database me-2"></i>Database</span>
                        <span class="small fw-semibold text-end">
                            <span class="status-dot {{ $systemStatus['database'] === 'Healthy' ? '' : 'bg-danger' }}"></span>{{ $systemStatus['database'] }}
                        </span>
                    </div>
                </section>
            </div>
        </div>

            <div class="row g-3 mb-4">
                <div class="col-12" id="plans">
                    <section class="panel">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                        <div class="panel-title"><i class="bi bi-box-seam text-primary me-2"></i>Active subscription plans</div>
                        <div class="panel-subtitle mb-3">
                            Current plan segment for each active subscription
                        </div>
                            </div>
                            <div class="input-group input-group-sm search-box">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search muted"></i></span>
                                <input class="form-control border-start-0" type="search" data-table-filter="plansTable" placeholder="Search plans..." aria-label="Search subscription plans">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table mb-0" id="plansTable">
                                <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Plan</th>
                                    <th>Included units</th>
                                    <th>Overage rate</th>
                                    <th>Cycle start</th>
                                    <th>Cycle end</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($activePlans as $plan)
                                    <tr>
                                        <td class="fw-semibold">{{ $plan->customer_name }}</td>
                                        <td>
                                            <span class="badge text-bg-light border">
                                                {{ $plan->plan_name }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($plan->included_units) }}</td>
                                        <td>
                                            {{ $plan->currency }}
                                            {{ number_format((float) $plan->overage_rate, 2) }}/unit
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($plan->current_period_start)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($plan->current_period_end)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 muted">
                                            No active plan segments found for this date.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
         
            <div class="row g-3">
                <div class="col-12" id="invoices">
                    <section class="panel">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="panel-title"><i class="bi bi-receipt text-primary me-2"></i>Recent invoices</div>
                                <div class="panel-subtitle mb-3">
                                    Latest invoices belonging to this merchant
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="POST" action="{{ route('merchant.invoices.generate-due', ['merchantId' => $merchantId]) }}">
                                    @csrf
                                    <button class="btn btn-refresh btn-sm px-3 py-2" type="submit">
                                        <i class="bi bi-receipt me-1"></i>Generate due invoices
                                    </button>
                                </form>
                                <div class="input-group input-group-sm search-box">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search muted"></i></span>
                                    <input class="form-control border-start-0" type="search" data-table-filter="invoicesTable" placeholder="Search invoices..." aria-label="Search invoices">
                                </div>
                            </div>
                        </div>
                        @if (session('invoice_generation_message'))
                            <div class="alert alert-info py-2" role="status">
                                {{ session('invoice_generation_message') }}
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table mb-0" id="invoicesTable">
                                <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Billing period</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">View</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($recentInvoices as $invoice)
                                    <tr>
                                        <td class="fw-semibold">
                                            <a href="{{ route('merchant.invoices.show', ['merchantId' => $merchantId, 'invoiceId' => $invoice->id]) }}">
                                                #{{ $invoice->id }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($invoice->period_start)->format('d M Y') }}
                                            <span class="muted">to</span>
                                            {{ \Carbon\Carbon::parse($invoice->period_end)->format('d M Y') }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $invoice->status === 'issued' ? 'text-bg-success' : 'text-bg-light border' }}">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold">
                                            {{ $invoice->currency }}
                                            {{ number_format((float) $invoice->total, 2) }}
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="{{ route('merchant.invoices.show', ['merchantId' => $merchantId, 'invoiceId' => $invoice->id]) }}"
                                               aria-label="View invoice #{{ $invoice->id }}"
                                               title="View invoice">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 muted">
                                            No invoices have been generated yet.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="text-center small muted mt-4">
                Mallow Billing · Merchant #{{ $merchantId }} ·
                Dashboard data is based on stored usage, subscriptions and invoices.
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const labels = @json($trendLabels);
    const values = @json($trendValues);
    const chartCanvas = document.getElementById('usageChart');
    const chartContext = chartCanvas.getContext('2d');
    const chartGradient = chartContext.createLinearGradient(0, 0, 0, 280);
    chartGradient.addColorStop(0, 'rgba(99, 91, 219, .22)');
    chartGradient.addColorStop(1, 'rgba(99, 91, 219, 0)');

    new Chart(chartCanvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Usage units',
                data: values,
                borderColor: '#635bdb',
                backgroundColor: chartGradient,
                borderWidth: 2.5,
                tension: 0.38,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#635bdb'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#171b2b',
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => `${new Intl.NumberFormat().format(context.parsed.y)} units`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#f0f1f5', drawTicks: false },
                    ticks: { padding: 10, precision: 0 }
                },
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { maxTicksLimit: 8, maxRotation: 0 }
                }
            }
        }
    });

    document.querySelectorAll('[data-table-filter]').forEach((input) => {
        input.addEventListener('input', () => {
            const table = document.getElementById(input.dataset.tableFilter);
            const query = input.value.trim().toLocaleLowerCase();

            table.querySelectorAll('tbody tr').forEach((row) => {
                row.hidden = !row.innerText.toLocaleLowerCase().includes(query);
            });
        });
    });

    document.querySelectorAll('#sidebarNavigation .nav-link-custom').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                bootstrap.Collapse.getOrCreateInstance(
                    document.getElementById('sidebarNavigation')
                ).hide();
            }
        });
    });
</script>
</body>
</html>
