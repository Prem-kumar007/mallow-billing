<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $merchantId = (int) $request->route('merchantId');

        if ((int) $request->session()->get('dashboard_merchant_id') !== $merchantId) {
            return redirect()->route('merchant.login', ['merchantId' => $merchantId]);
        }

        return $next($request);
    }
}
