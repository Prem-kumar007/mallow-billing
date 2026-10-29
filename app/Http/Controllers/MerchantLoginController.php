<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MerchantLoginController extends Controller
{
    public function show(int $merchantId): View
    {
        $merchant = DB::table('merchants')
            ->where('id', $merchantId)
            ->first(['id', 'name']);

        abort_unless($merchant, 404, 'Merchant not found.');

        return view('merchant.login', compact('merchant'));
    }

    public function store(Request $request, int $merchantId): RedirectResponse
    {
        $data = $request->validate([
            'api_key' => ['required', 'string', 'max:255'],
        ]);

        $merchant = DB::table('merchants')
            ->where('id', $merchantId)
            ->first(['id', 'api_key_hash']);

        if (! $merchant || ! $merchant->api_key_hash || ! Hash::check($data['api_key'], $merchant->api_key_hash)) {
            return back()
                ->withErrors(['api_key' => 'The merchant API key is invalid.']);
        }

        $request->session()->regenerate();
        $request->session()->put('dashboard_merchant_id', (int) $merchant->id);

        return redirect()->route('merchant.management', ['merchantId' => $merchantId])
            ->with('success', 'You are signed in to the merchant workspace.');
    }

    public function destroy(Request $request, int $merchantId): RedirectResponse
    {
        if ((int) $request->session()->get('dashboard_merchant_id') === $merchantId) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('merchant.dashboard', ['merchantId' => $merchantId]);
    }
}
