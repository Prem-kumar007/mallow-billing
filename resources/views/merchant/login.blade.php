<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | {{ $merchant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { min-height:100vh; display:grid; place-items:center; background:#f6f7fb; color:#17212f; font-family:"DM Sans",sans-serif; }
        .login-card { width:min(100% - 32px, 460px); padding:36px; border:1px solid #e9edf3; border-radius:20px; background:#fff; box-shadow:0 12px 40px rgba(31,42,68,.08); }
        .brand-mark { display:grid; width:42px; height:42px; place-items:center; border-radius:13px; background:#7169ee; color:#fff; font-size:20px; }
        h1 { font:800 24px "Manrope",sans-serif; letter-spacing:-.7px; }
        .form-control { padding:12px 14px; border-radius:10px; }
        .btn-primary { --bs-btn-bg:#635bdb; --bs-btn-border-color:#635bdb; --bs-btn-hover-bg:#5149c5; --bs-btn-hover-border-color:#5149c5; border-radius:10px; }
        @media(max-width:575px) { .login-card { padding:26px 22px; } }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="brand-mark mb-4"><i class="bi bi-layers-half"></i></div>
        <div class="small text-uppercase fw-bold text-primary mb-2">Merchant workspace</div>
        <h1 class="mb-2">Sign in to {{ $merchant->name }}</h1>
        <p class="text-secondary mb-4">Use the merchant API key to manage customers, plans and subscriptions.</p>

        <form method="POST" action="{{ route('merchant.login.store', ['merchantId' => $merchant->id]) }}">
            @csrf
            <label for="api_key" class="form-label fw-semibold">Merchant API key</label>
            <input id="api_key" name="api_key" type="password" class="form-control @error('api_key') is-invalid @enderror" autocomplete="current-password" required autofocus>
            @error('api_key')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn btn-primary w-100 py-2 mt-4">
                Continue to workspace <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <div class="small text-secondary mt-4">
            <i class="bi bi-shield-lock me-1"></i>Management access is restricted to this merchant's API key.
        </div>
    </main>
</body>
</html>
