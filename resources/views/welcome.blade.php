<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171b2b">
    <title>Mallow Billing | Workspace setup</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root { --ink:#17212f; --muted:#758196; --brand:#635bdb; }
        body { min-height:100vh; color:var(--ink); background:#f6f7fb; font-family:"DM Sans",sans-serif; }
        .navbar { background:#171b2b; }
        .brand { color:#fff; font:800 21px "Manrope",sans-serif; letter-spacing:-.8px; }
        .brand-mark { display:inline-grid; width:35px; height:35px; place-items:center; border-radius:11px; background:#7169ee; }
        .hero { position:relative; overflow:hidden; min-height:460px; display:flex; align-items:center; border-radius:24px; color:#fff; background:linear-gradient(120deg,#171b2b 15%,#28254d 100%); }
        .hero::after { position:absolute; top:-150px; right:-100px; width:500px; height:500px; border:1px solid rgba(255,255,255,.1); border-radius:50%; box-shadow:0 0 0 55px rgba(255,255,255,.025),0 0 0 110px rgba(255,255,255,.02); content:""; }
        .hero-content { position:relative; z-index:1; max-width:650px; }
        .eyebrow { color:#bdb9ff; font-size:11px; font-weight:700; letter-spacing:1.6px; }
        h1 { font:800 clamp(38px,6vw,62px)/1.08 "Manrope",sans-serif; letter-spacing:-2.5px; }
        .hero-copy { max-width:540px; color:#c0c4d2; font-size:16px; line-height:1.8; }
        .btn-primary { --bs-btn-bg:var(--brand); --bs-btn-border-color:var(--brand); --bs-btn-hover-bg:#5149c5; --bs-btn-hover-border-color:#5149c5; border-radius:11px; font-weight:700; }
        .setup-card,.feature-card { height:100%; border:1px solid #e9edf3; border-radius:17px; background:#fff; box-shadow:0 5px 20px rgba(31,42,68,.035); }
        .setup-card { padding:26px; }
        .feature-card { padding:22px; }
        .icon-wrap { display:grid; width:43px; height:43px; place-items:center; border-radius:13px; color:var(--brand); background:#f0efff; font-size:19px; }
        .muted { color:var(--muted); }
        @media(max-width:575.98px) { .hero { min-height:400px; padding:32px 22px !important; } .hero-copy { font-size:14px; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark py-3">
        <div class="container">
            <a class="brand d-flex align-items-center gap-2 text-decoration-none" href="{{ url('/') }}">
                <span class="brand-mark"><i class="bi bi-layers-half"></i></span>
                Mallow
            </a>
            <span class="small text-white-50">Usage &amp; Billing Platform</span>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        <section class="hero p-4 p-md-5 mb-4 mb-lg-5">
            <div class="hero-content">
                <div class="eyebrow text-uppercase mb-3">Subscription billing, made clear</div>
                <h1 class="mb-4">Usage to invoices.<br>All in one place.</h1>
                <p class="hero-copy mb-4">
                    Track customer usage, manage plan changes, and understand projected overage
                    revenue from one simple billing workspace.
                </p>
                <a class="btn btn-primary btn-lg px-4 py-3" href="#setup">
                    Set up your workspace <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </section>

        <section class="row g-3 g-lg-4 mb-4 mb-lg-5" aria-label="Platform features">
            <div class="col-md-4">
                <article class="feature-card">
                    <div class="icon-wrap mb-3"><i class="bi bi-activity"></i></div>
                    <h2 class="h6 fw-bold">Usage metering</h2>
                    <p class="small muted mb-0">Capture usage safely with tenant-scoped, idempotent events.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="feature-card">
                    <div class="icon-wrap mb-3"><i class="bi bi-receipt"></i></div>
                    <h2 class="h6 fw-bold">Accurate billing</h2>
                    <p class="small muted mb-0">Prorate plan changes and apply the right allowance and rate to each period.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="feature-card">
                    <div class="icon-wrap mb-3"><i class="bi bi-bar-chart-line"></i></div>
                    <h2 class="h6 fw-bold">Useful insights</h2>
                    <p class="small muted mb-0">See usage trends, overage projections, and customer churn signals.</p>
                </article>
            </div>
        </section>

        <section id="setup" class="setup-card">
            <div class="row align-items-center g-4">
                <div class="col-md">
                    <div class="eyebrow text-uppercase mb-2" style="color:#635bdb">Getting started</div>
                    <h2 class="h4 fw-bold mb-2">Create your first merchant workspace</h2>
                    <p class="muted mb-0">
                        No merchant workspace exists yet. Run the migrations, then create a merchant
                        to generate its one-time API key and sign in to the dashboard.
                    </p>
                </div>
                <div class="col-md-auto">
                    <div class="d-grid gap-2">
                        <code class="small">php artisan migrate</code>
                        <code class="small">php artisan billing:merchant-create "Acme"</code>
                    </div>
                </div>
            </div>
        </section>

        <footer class="text-center small muted pt-4 mt-2">
            Mallow Billing <span class="mx-1">·</span> Multi-tenant usage &amp; billing
        </footer>
    </main>
</body>
</html>
