<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage workspace | {{ $merchant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --ink:#17212f; --muted:#758196; --line:#e9edf3; --brand:#635bdb; }
        body { background:#f6f7fb; color:var(--ink); font-family:"DM Sans",sans-serif; font-size:14px; }
        .sidebar { min-height:100vh; background:#171b2b; color:#fff; }
        .brand { color:#fff; font:800 20px "Manrope",sans-serif; letter-spacing:-.8px; }
        .brand-mark { display:inline-grid; width:34px; height:34px; place-items:center; border-radius:11px; background:#7169ee; }
        .nav-link-custom { display:flex; align-items:center; gap:11px; padding:11px 13px; margin:4px 0; border-radius:10px; color:#aab1c1; text-decoration:none; }
        .nav-link-custom:hover,.nav-link-custom.active { color:#fff; background:#292d41; }
        .nav-link-custom i { width:18px; }
        .merchant-card { background:#22263a; border:1px solid #30354b; }
        .merchant-avatar { display:grid; width:36px; height:36px; flex:0 0 36px; place-items:center; border-radius:11px; background:#393658; color:#c7c2ff; font-weight:700; }
        .panel { border:1px solid var(--line); border-radius:16px; background:#fff; box-shadow:0 5px 20px rgba(31,42,68,.035); }
        .panel { padding:22px; }
        h1,h2,h3 { font-family:"Manrope",sans-serif; }
        h1 { font-size:26px; font-weight:800; letter-spacing:-.8px; }
        .muted { color:var(--muted); }
        .btn-primary { --bs-btn-bg:var(--brand); --bs-btn-border-color:var(--brand); --bs-btn-hover-bg:#5149c5; --bs-btn-hover-border-color:#5149c5; }
        .btn,.form-control,.form-select { border-radius:9px; }
        .table { --bs-table-bg:transparent; }
        .table > :not(caption) > * > * { padding:12px 10px; vertical-align:middle; }
        .table thead th { color:#8992a2; font-size:10px; text-transform:uppercase; letter-spacing:.6px; white-space:nowrap; }
        .section-anchor { scroll-margin-top:20px; }
        .section-title { font:700 16px "Manrope",sans-serif; }
        .section-note { color:var(--muted); font-size:12px; }
        .metric-pill { display:inline-flex; align-items:center; gap:7px; padding:7px 10px; border:1px solid var(--line); border-radius:99px; background:#fff; color:#5f6b7d; font-size:12px; }
        .form-label { color:#4c586c; font-size:12px; font-weight:700; }
        .form-control:focus,.form-select:focus { border-color:#b7b3ff; box-shadow:0 0 0 .2rem rgba(99,91,219,.1); }
        .table-search { max-width:220px; }
        @media(max-width:991.98px) { .sidebar { min-height:auto; } .sidebar .nav { flex-direction:row; flex-wrap:wrap; } main { padding:22px !important; } }
        @media(max-width:575.98px) { main { padding:16px !important; } .panel { padding:16px; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 sidebar p-3 p-lg-4">
            <a class="brand d-flex align-items-center gap-2 text-decoration-none mb-4" href="{{ route('merchant.dashboard', ['merchantId' => $merchantId]) }}">
                <span class="brand-mark"><i class="bi bi-layers-half"></i></span>Mallow
            </a>
            @include('merchant.partials.sidebar-navigation', ['page' => 'management'])
        </aside>

        <main class="col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="small text-uppercase fw-bold text-primary mb-2">MERCHANT WORKSPACE / MANAGEMENT</div>
                    <h1 class="mb-1">Customers, plans &amp; subscriptions</h1>
                    <div class="muted">Manage your customer records, pricing catalog and active billing cycles.</div>
                </div>
                <a href="{{ route('merchant.dashboard', ['merchantId' => $merchantId]) }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i>Back to overview
                </a>
            </header>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="status">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <div class="fw-bold mb-1">Please review the form:</div>
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="metric-pill"><i class="bi bi-people text-primary"></i>{{ $customers->count() }} customers</span>
                <span class="metric-pill"><i class="bi bi-box-seam text-primary"></i>{{ $plans->count() }} plans</span>
                <span class="metric-pill"><i class="bi bi-arrow-repeat text-primary"></i>{{ $subscriptions->where('status', 'active')->count() }} active subscriptions</span>
            </div>

            <section id="customers" class="section-anchor panel mb-4">
                <div class="row g-4">
                    <div class="col-xl-3">
                        <div class="section-title"><i class="bi bi-person-plus text-primary me-2"></i>Add a customer</div>
                        <p class="section-note mb-3">Customer details are scoped to this merchant.</p>
                        <form method="POST" action="{{ route('merchant.customers.store', ['merchantId' => $merchantId]) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="customer-name">Name</label>
                                <input id="customer-name" class="form-control" name="name" value="{{ old('name') }}" required maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="customer-email">Email <span class="fw-normal muted">(optional)</span></label>
                                <input id="customer-email" class="form-control" name="email" type="email" value="{{ old('email') }}" maxlength="255">
                            </div>
                            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Create customer</button>
                        </form>
                    </div>
                    <div class="col-xl-9">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="section-title">Customer directory</div>
                                <div class="section-note">Edit contact details or remove customers without billing history.</div>
                            </div>
                            <input class="form-control form-control-sm table-search" type="search" data-table-filter="customersTable" placeholder="Search customers..." aria-label="Search customers">
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="customersTable">
                                <thead><tr><th>Customer</th><th>Email</th><th>Subscriptions</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                @forelse ($customers as $customer)
                                    <tr>
                                        <td class="fw-semibold">{{ $customer->name }}</td>
                                        <td>{{ $customer->email ?: '—' }}</td>
                                        <td><span class="badge text-bg-light border">{{ $customer->subscriptions_count }}</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="modal" data-bs-target="#editCustomer{{ $customer->id }}"><i class="bi bi-pencil"></i><span class="visually-hidden">Edit {{ $customer->name }}</span></button>
                                            @if ((int) $customer->subscriptions_count === 0)
                                                <form method="POST" action="{{ route('merchant.customers.destroy', ['merchantId' => $merchantId, 'customerId' => $customer->id]) }}" class="d-inline" onsubmit="return confirm('Delete this customer?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i><span class="visually-hidden">Delete {{ $customer->name }}</span></button>
                                                </form>
                                            @else
                                                <button class="btn btn-sm btn-light border" type="button" disabled title="Customer has billing history"><i class="bi bi-lock"></i><span class="visually-hidden">Cannot delete customer with billing history</span></button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4 muted">No customers yet. Add your first customer using the form.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section id="plans" class="section-anchor panel mb-4">
                <div class="row g-4">
                    <div class="col-xl-3">
                        <div class="section-title"><i class="bi bi-box-seam text-primary me-2"></i>Create a plan</div>
                        <p class="section-note mb-3">Set the recurring fee, allowance and overage price.</p>
                        <form method="POST" action="{{ route('merchant.plans.store', ['merchantId' => $merchantId]) }}">
                            @csrf
                            @include('merchant.partials.plan-fields', ['prefix' => 'new-plan', 'plan' => null, 'active' => true])
                            <button class="btn btn-primary w-100 mt-2" type="submit"><i class="bi bi-plus-lg me-1"></i>Create plan</button>
                        </form>
                    </div>
                    <div class="col-xl-9">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="section-title">Plan catalog</div>
                                <div class="section-note">Updating a plan won't rewrite pricing already saved to subscriptions.</div>
                            </div>
                            <input class="form-control form-control-sm table-search" type="search" data-table-filter="plansTable" placeholder="Search plans..." aria-label="Search plans">
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="plansTable">
                                <thead><tr><th>Plan</th><th>Base price</th><th>Allowance</th><th>Overage</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                @forelse ($plans as $plan)
                                    <tr>
                                        <td><div class="fw-semibold">{{ $plan->name }}</div><div class="small muted">Every {{ $plan->billing_interval_count }} {{ $plan->billing_interval_unit }}{{ $plan->billing_interval_count > 1 ? 's' : '' }}</div></td>
                                        <td>{{ $plan->currency }} {{ number_format((float) $plan->base_price, 2) }}</td>
                                        <td>{{ number_format($plan->included_units) }} units</td>
                                        <td>{{ $plan->currency }} {{ number_format((float) $plan->overage_rate, 4) }}/unit</td>
                                        <td><span class="badge {{ $plan->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $plan->is_active ? 'Active' : 'Archived' }}</span></td>
                                        <td class="text-end"><button class="btn btn-sm btn-light border" type="button" data-bs-toggle="modal" data-bs-target="#editPlan{{ $plan->id }}"><i class="bi bi-pencil me-1"></i>Edit</button></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4 muted">No plans yet. Create a plan to start subscriptions.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section id="subscriptions" class="section-anchor panel mb-4">
                <div class="row g-4">
                    <div class="col-xl-3">
                        <div class="section-title"><i class="bi bi-arrow-repeat text-primary me-2"></i>Start a subscription</div>
                        <p class="section-note mb-3">Choose a customer and active plan.</p>
                        @if ($customers->isEmpty() || $plans->where('is_active', true)->isEmpty())
                            <div class="alert alert-light border small mb-0">Create a customer and an active plan before adding a subscription.</div>
                        @else
                            <form method="POST" action="{{ route('merchant.subscriptions.store', ['merchantId' => $merchantId]) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="subscription-customer">Customer</label>
                                    <select id="subscription-customer" class="form-select" name="customer_id" required>
                                        @foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="subscription-plan">Plan</label>
                                    <select id="subscription-plan" class="form-select" name="plan_id" required>
                                        @foreach ($plans->where('is_active', true) as $plan)<option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }} · {{ $plan->currency }} {{ number_format((float) $plan->base_price, 2) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="subscription-started-at">Billing starts</label>
                                    <input id="subscription-started-at" class="form-control" type="datetime-local" name="started_at" value="{{ old('started_at', now()->format('Y-m-d\TH:i')) }}" required>
                                </div>
                                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Start subscription</button>
                            </form>
                        @endif
                    </div>
                    <div class="col-xl-9">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="section-title">Subscription ledger</div>
                                <div class="section-note">Plan changes preserve the old segment and prorate billing.</div>
                            </div>
                            <input class="form-control form-control-sm table-search" type="search" data-table-filter="subscriptionsTable" placeholder="Search subscriptions..." aria-label="Search subscriptions">
                        </div>
                        @error('subscription')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        <div class="table-responsive">
                            <table class="table" id="subscriptionsTable">
                                <thead><tr><th>Customer</th><th>Plan</th><th>Status</th><th>Current period</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                @forelse ($subscriptions as $subscription)
                                    <tr>
                                        <td><div class="fw-semibold">{{ $subscription->customer_name }}</div><div class="small muted">{{ $subscription->customer_email ?: 'No email' }}</div></td>
                                        <td>{{ $subscription->plan_name }}</td>
                                        <td><span class="badge {{ $subscription->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($subscription->status) }}</span></td>
                                        <td><div>{{ \Carbon\Carbon::parse($subscription->current_period_start)->format('d M Y') }}</div><div class="small muted">to {{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') }}</div></td>
                                        <td class="text-end text-nowrap">
                                            @if ($subscription->status === 'active')
                                                <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="modal" data-bs-target="#changePlan{{ $subscription->id }}"><i class="bi bi-arrow-left-right me-1"></i>Change plan</button>
                                                <form method="POST" action="{{ route('merchant.subscriptions.cancel', ['merchantId' => $merchantId, 'subscriptionId' => $subscription->id]) }}" class="d-inline" onsubmit="return confirm('Cancel this subscription? Billing history will be kept.')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i><span class="visually-hidden">Cancel subscription</span></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4 muted">No subscriptions yet. Create one using the form.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

@foreach ($customers as $customer)
    <div class="modal fade" id="editCustomer{{ $customer->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('merchant.customers.update', ['merchantId' => $merchantId, 'customerId' => $customer->id]) }}">
                @csrf @method('PUT')
                <div class="modal-header"><h2 class="modal-title fs-5">Edit customer</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <label class="form-label" for="edit-customer-name-{{ $customer->id }}">Name</label>
                    <input id="edit-customer-name-{{ $customer->id }}" class="form-control mb-3" name="name" value="{{ $customer->name }}" required>
                    <label class="form-label" for="edit-customer-email-{{ $customer->id }}">Email</label>
                    <input id="edit-customer-email-{{ $customer->id }}" class="form-control" type="email" name="email" value="{{ $customer->email }}">
                </div>
                <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
            </form>
        </div></div>
    </div>
@endforeach

@foreach ($plans as $plan)
    <div class="modal fade" id="editPlan{{ $plan->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <form method="POST" action="{{ route('merchant.plans.update', ['merchantId' => $merchantId, 'planId' => $plan->id]) }}">
                @csrf @method('PUT')
                <div class="modal-header"><h2 class="modal-title fs-5">Edit {{ $plan->name }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">@include('merchant.partials.plan-fields', ['prefix' => 'edit-plan-' . $plan->id, 'plan' => $plan, 'active' => (bool) $plan->is_active])</div>
                <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save plan</button></div>
            </form>
        </div></div>
    </div>
@endforeach

@foreach ($subscriptions->where('status', 'active') as $subscription)
    <div class="modal fade" id="changePlan{{ $subscription->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('merchant.subscriptions.change-plan', ['merchantId' => $merchantId, 'subscriptionId' => $subscription->id]) }}">
                @csrf @method('PUT')
                <div class="modal-header"><h2 class="modal-title fs-5">Change subscription plan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="small muted">Usage before the selected time remains on <strong>{{ $subscription->plan_name }}</strong>. Billing is prorated across both plan segments.</p>
                    <label class="form-label" for="change-plan-{{ $subscription->id }}">New plan</label>
                    <select id="change-plan-{{ $subscription->id }}" class="form-select mb-3" name="plan_id" required>
                        @foreach ($plans->where('is_active', true) as $plan)
                            @if ($plan->id !== $subscription->plan_id)
                                <option value="{{ $plan->id }}">{{ $plan->name }} · {{ $plan->currency }} {{ number_format((float) $plan->base_price, 2) }}</option>
                            @endif
                        @endforeach
                    </select>
                    <label class="form-label" for="change-effective-{{ $subscription->id }}">Change effective at</label>
                    <input id="change-effective-{{ $subscription->id }}" class="form-control" type="datetime-local" name="effective_at" value="{{ now()->format('Y-m-d\TH:i') }}" min="{{ \Carbon\Carbon::parse($subscription->current_period_start)->format('Y-m-d\TH:i') }}" max="{{ \Carbon\Carbon::parse($subscription->current_period_end)->subMinute()->format('Y-m-d\TH:i') }}" required>
                </div>
                <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit" @disabled($plans->where('is_active', true)->where('id', '!=', $subscription->plan_id)->isEmpty())>Save plan change</button></div>
            </form>
        </div></div>
    </div>
@endforeach

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('[data-table-filter]').forEach((input) => {
        input.addEventListener('input', () => {
            const table = document.getElementById(input.dataset.tableFilter);
            const query = input.value.trim().toLocaleLowerCase();
            table.querySelectorAll('tbody tr').forEach((row) => {
                row.hidden = !row.innerText.toLocaleLowerCase().includes(query);
            });
        });
    });

    if (window.location.hash) {
        document.querySelector(window.location.hash)?.scrollIntoView();
    }
</script>
</body>
</html>
