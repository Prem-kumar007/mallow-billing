<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMerchantApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $merchantId = filter_var(
            $request->header('X-Merchant-Id'),
            FILTER_VALIDATE_INT
        );
        $apiKey = $request->header('X-API-Key');

        if (!$merchantId || !$apiKey) {
            return response()->json(['message' => 'Merchant credentials are required.'], 401);
        }

        $merchant = DB::table('merchants')
            ->where('id', $merchantId)
            ->first(['id', 'api_key_hash']);

        if (!$merchant || !$merchant->api_key_hash || !Hash::check($apiKey, $merchant->api_key_hash)) {
            return response()->json(['message' => 'Invalid merchant credentials.'], 401);
        }

        $request->attributes->set('merchant_id', (int) $merchant->id);

        return $next($request);
    }
}
