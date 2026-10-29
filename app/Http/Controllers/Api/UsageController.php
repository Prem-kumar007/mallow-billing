<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordUsageRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function store(RecordUsageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['occurred_at'] = Carbon::parse($data['occurred_at'])
            ->utc()
            ->toDateTimeString();
        $merchantId = (int) $request->attributes->get('merchant_id');

        $subscription = DB::table('subscriptions')
            ->where('id', $data['subscription_id'])
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found for this merchant.',
            ], 404);
        }

        $existing = $this->findExistingEvent($merchantId, $data['idempotency_key']);

        if ($existing) {
            return $this->duplicateResponse($existing, $data);
        }

        if ($subscription->status !== 'active') {
            return response()->json(['message' => 'Active subscription not found.'], 404);
        }

        $segment = DB::table('subscription_segments')
            ->where('subscription_id', $subscription->id)
            ->where('effective_from', '<=', $data['occurred_at'])
            ->where('effective_to', '>', $data['occurred_at'])
            ->first();

        if (!$segment) {
            return response()->json([
                'message' => 'No valid subscription segment found.',
            ], 422);
        }


        try {
            $id = DB::table('usage_events')->insertGetId([
                'merchant_id' => $merchantId,
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'segment_id' => $segment->id,
                'idempotency_key' => $data['idempotency_key'],
                'units' => $data['units'],
                'occurred_at' => $data['occurred_at'],
                'created_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $existing = $this->findExistingEvent($merchantId, $data['idempotency_key']);

            if ($existing) {
                return $this->duplicateResponse($existing, $data);
            }

            throw $e;
        }

        $usage = DB::table('usage_events')
            ->where('id', $id)
            ->first();

        return response()->json([
            'message' => 'Usage recorded successfully.',
            'data' => $usage,
        ], 201);
    }

    private function findExistingEvent(int $merchantId, string $key): ?object
    {
        return DB::table('usage_events')
            ->where('merchant_id', $merchantId)
            ->where('idempotency_key', $key)
            ->first();
    }

    private function duplicateResponse(object $existing, array $data)
    {
        if (
            (int) $existing->subscription_id !== (int) $data['subscription_id']
            || (int) $existing->units !== (int) $data['units']
            || $existing->occurred_at !== $data['occurred_at']
        ) {
            return response()->json([
                'message' => 'Idempotency key was already used for a different event.',
            ], 409);
        }

        return response()->json([
            'message' => 'Usage event already recorded.',
            'data' => $existing,
        ], 200);
    }
}