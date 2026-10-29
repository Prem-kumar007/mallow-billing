<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign out | {{ $merchant->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height:100vh; display:grid; place-items:center; background:#f6f7fb; color:#17212f; font-family:system-ui,sans-serif; }
        .logout-card { width:min(100% - 32px, 440px); padding:32px; border:1px solid #e9edf3; border-radius:18px; background:#fff; box-shadow:0 12px 40px rgba(31,42,68,.08); }
    </style>
</head>
<body>
    <main class="logout-card">
        <h1 class="h4 mb-2">Sign out of {{ $merchant->name }}?</h1>
        <p class="text-secondary">You will need the merchant API key to sign in again.</p>
        <form method="POST" action="{{ route('merchant.logout', ['merchantId' => $merchant->id]) }}">
            @csrf
            <div class="d-flex gap-2">
                <button class="btn btn-danger" type="submit">Sign out</button>
                <a class="btn btn-light border" href="{{ route('merchant.dashboard', ['merchantId' => $merchant->id]) }}">
                    Cancel
                </a>
            </div>
        </form>
    </main>
</body>
</html>
