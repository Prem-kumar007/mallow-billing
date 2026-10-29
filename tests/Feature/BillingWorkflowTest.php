<?php

namespace Tests\Feature;

use App\Jobs\AggregateDailyUsage;
use App\Jobs\GenerateDueInvoicesJob;
use App\Services\InvoiceGenerationService;
use App\Services\PlanPricingService;
use App\Services\SubscriptionSegmentService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_endpoint_is_authenticated_idempotent_and_tenant_scoped(): void
    {
        $merchantId = $this->createMerchant();
        $customerId = $this->createCustomer($merchantId);
        $planId = $this->createPlan($merchantId);
        $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
        $segmentId = $this->createSegment(
            $subscriptionId,
            $planId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );
        $headers = ['X-Merchant-Id' => (string) $merchantId, 'X-API-Key' => 'test-secret'];
        $payload = [
            'subscription_id' => $subscriptionId,
            'idempotency_key' => 'request-001',
            'units' => 12,
            'occurred_at' => '2026-01-10T12:00:00+00:00',
        ];

        $this->postJson('/api/usage', $payload)
            ->assertUnauthorized();

        $this->postJson('/api/usage', $payload, $headers)
            ->assertCreated()
            ->assertJsonPath('data.segment_id', $segmentId);

        $this->postJson('/api/usage', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Usage event already recorded.');

        $this->postJson('/api/usage', array_merge($payload, ['units' => 13]), $headers)
            ->assertStatus(409);

        $otherMerchantId = $this->createMerchant('other-secret');
        $this->postJson('/api/usage', $payload, [
            'X-Merchant-Id' => (string) $otherMerchantId,
            'X-API-Key' => 'other-secret',
        ])->assertNotFound();

        $this->assertDatabaseCount('usage_events', 1);
    }

    public function test_usage_endpoint_validates_payload_and_limits_authenticated_merchants(): void
    {
        $merchantId = random_int(100000, 200000);
        DB::table('merchants')->insert([
            'id' => $merchantId,
            'name' => 'Rate-limited merchant',
            'api_key_hash' => Hash::make('test-secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $validationMerchantId = $this->createMerchant('validation-secret');
        $headers = ['X-Merchant-Id' => (string) $merchantId, 'X-API-Key' => 'test-secret'];

        $this->postJson('/api/usage', [
            'subscription_id' => 1,
            'idempotency_key' => '',
            'units' => 0,
            'occurred_at' => 'not-a-date',
        ], [
            'X-Merchant-Id' => (string) $validationMerchantId,
            'X-API-Key' => 'validation-secret',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'idempotency_key',
                'units',
                'occurred_at',
            ]);

        $customerId = $this->createCustomer($merchantId);
        $planId = $this->createPlan($merchantId);
        $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
        $this->createSegment(
            $subscriptionId,
            $planId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );

        config(['billing.usage_rate_limit' => 2]);
        RateLimiter::clear(md5('usagemerchant:'.$merchantId));

        $payload = [
            'subscription_id' => $subscriptionId,
            'idempotency_key' => 'rate-limit-event',
            'units' => 1,
            'occurred_at' => '2026-01-10T12:00:00Z',
        ];

        $this->postJson('/api/usage', $payload, $headers)->assertCreated();
        $this->postJson('/api/usage', $payload, $headers)->assertOk();
        $this->postJson('/api/usage', $payload, $headers)->assertTooManyRequests();
        $this->assertDatabaseCount('usage_events', 1);
    }

    public function test_daily_aggregation_is_chunked_and_safe_to_rerun(): void
    {
        $merchantId = $this->createMerchant();
        $customerId = $this->createCustomer($merchantId);
        $planId = $this->createPlan($merchantId);
        $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
        $timestamp = now();
        $segments = [];

        for ($index = 0; $index < 501; $index++) {
            $segments[] = [
                'subscription_id' => $subscriptionId,
                'plan_id' => $planId,
                'effective_from' => '2026-01-01 00:00:00',
                'effective_to' => '2026-02-01 00:00:00',
                'base_price' => 10,
                'currency' => 'USD',
                'included_units' => 100,
                'overage_rate' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        foreach (array_chunk($segments, 100) as $segmentBatch) {
            DB::table('subscription_segments')->insert($segmentBatch);
        }

        $events = [];
        foreach (DB::table('subscription_segments')
            ->where('subscription_id', $subscriptionId)
            ->orderBy('id')
            ->pluck('id') as $index => $segmentId) {
            $events[] = [
                'merchant_id' => $merchantId,
                'customer_id' => $customerId,
                'subscription_id' => $subscriptionId,
                'segment_id' => $segmentId,
                'idempotency_key' => 'aggregate-'.$index,
                'units' => 1,
                'occurred_at' => '2026-01-10 12:00:00',
                'created_at' => $timestamp,
            ];
        }

        foreach (array_chunk($events, 100) as $eventBatch) {
            DB::table('usage_events')->insert($eventBatch);
        }

        $insertBatchSizes = [];
        DB::listen(function (QueryExecuted $query) use (&$insertBatchSizes): void {
            if (str_starts_with($query->sql, 'insert into "daily_usage"')) {
                $insertBatchSizes[] = intdiv(count($query->bindings), 8);
            }
        });

        $job = new AggregateDailyUsage('2026-01-10');
        $job->handle();
        $job->handle();

        $this->assertDatabaseCount('daily_usage', 501);
        $this->assertSame(501, (int) DB::table('daily_usage')->sum('total_units'));
        $this->assertSame([500, 1, 500, 1], $insertBatchSizes);
    }

    public function test_previous_day_aggregation_command_queues_the_previous_day(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-01-16 00:10:00');

        try {
            $this->artisan('billing:aggregate-previous-day-usage')
                ->expectsOutput('Queued daily usage aggregation for 2026-01-15.')
                ->assertExitCode(0);

            Queue::assertPushed(
                AggregateDailyUsage::class,
                fn (AggregateDailyUsage $job): bool => $job->date === '2026-01-15'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_aggregation_recovery_command_queues_a_requested_date(): void
    {
        Queue::fake();

        $this->artisan('billing:aggregate-usage', ['date' => '2026-01-10'])
            ->expectsOutput('Queued daily usage aggregation for 2026-01-10.')
            ->assertExitCode(0);

        Queue::assertPushed(
            AggregateDailyUsage::class,
            fn (AggregateDailyUsage $job): bool => $job->date === '2026-01-10'
        );
    }

    public function test_aggregation_recovery_command_rejects_an_invalid_date(): void
    {
        Queue::fake();

        $this->artisan('billing:aggregate-usage', ['date' => '2026-02-31'])
            ->expectsOutput('The date must be a valid calendar date in YYYY-MM-DD format.')
            ->assertExitCode(2);

        Queue::assertNothingPushed();
    }

    public function test_invoice_prorates_plan_changes_and_charges_each_segments_overage(): void
    {
        $merchantId = $this->createMerchant();
        $customerId = $this->createCustomer($merchantId);
        $oldPlanId = $this->createPlan($merchantId, 'Starter', 100, 100, 1);
        $newPlanId = $this->createPlan($merchantId, 'Pro', 200, 200, 2);
        $subscriptionId = $this->createSubscription($merchantId, $customerId, $oldPlanId);
        $oldSegmentId = $this->createSegment(
            $subscriptionId,
            $oldPlanId,
            '2026-01-01 00:00:00',
            '2026-01-16 00:00:00',
            100,
            100,
            1
        );
        $newSegmentId = $this->createSegment(
            $subscriptionId,
            $newPlanId,
            '2026-01-16 00:00:00',
            '2026-02-01 00:00:00',
            200,
            200,
            2
        );

        $this->createUsage($merchantId, $customerId, $subscriptionId, $oldSegmentId, 'old', 120, '2026-01-10 12:00:00');
        $this->createUsage($merchantId, $customerId, $subscriptionId, $newSegmentId, 'new', 250, '2026-01-20 12:00:00');

        $service = app(InvoiceGenerationService::class);
        $invoiceId = $service->generate(
            $subscriptionId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );
        $duplicateInvoiceId = $service->generate(
            $subscriptionId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );

        $this->assertSame($invoiceId, $duplicateInvoiceId);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'subtotal' => 516.78,
            'status' => 'issued',
        ]);
        $this->assertDatabaseCount('invoice_items', 4);
        $this->assertSame(
            2,
            DB::table('invoice_items')->where('invoice_id', $invoiceId)->where('item_type', 'overage')->count()
        );
    }

    public function test_invoice_does_not_create_an_overage_for_usage_within_prorated_allowance(): void
    {
        $merchantId = $this->createMerchant();
        $customerId = $this->createCustomer($merchantId);
        $planId = $this->createPlan($merchantId, 'Included', 25, 100, 1);
        $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
        $segmentId = $this->createSegment(
            $subscriptionId,
            $planId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00',
            25,
            100,
            1
        );
        $this->createUsage($merchantId, $customerId, $subscriptionId, $segmentId, 'within', 50, '2026-01-10 12:00:00');

        $invoiceId = app(InvoiceGenerationService::class)->generate(
            $subscriptionId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );

        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'total' => 25]);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertDatabaseMissing('invoice_items', [
            'invoice_id' => $invoiceId,
            'item_type' => 'overage',
        ]);
    }

    public function test_plan_change_keeps_old_pricing_before_the_effective_time(): void
    {
        $merchantId = $this->createMerchant();
        $customerId = $this->createCustomer($merchantId);
        $oldPlanId = $this->createPlan($merchantId, 'Old', 50, 100, 1);
        $newPlanId = $this->createPlan($merchantId, 'New', 100, 250, 2);
        $segments = app(SubscriptionSegmentService::class);
        $subscriptionId = $segments->subscribe(
            $merchantId,
            $customerId,
            $oldPlanId,
            '2026-01-01 00:00:00'
        );
        $oldSegmentId = (int) DB::table('subscription_segments')
            ->where('subscription_id', $subscriptionId)
            ->value('id');

        $this->createUsage(
            $merchantId,
            $customerId,
            $subscriptionId,
            $oldSegmentId,
            'before-upgrade',
            80,
            '2026-01-10 12:00:00'
        );

        $newSegmentId = $segments->changePlan(
            $merchantId,
            $subscriptionId,
            $newPlanId,
            '2026-01-16 00:00:00'
        );

        $this->assertDatabaseHas('subscription_segments', [
            'id' => $oldSegmentId,
            'effective_to' => '2026-01-16 00:00:00',
            'base_price' => 50,
        ]);
        $this->assertDatabaseHas('subscription_segments', [
            'id' => $newSegmentId,
            'effective_from' => '2026-01-16 00:00:00',
            'base_price' => 100,
            'included_units' => 250,
            'overage_rate' => 2,
        ]);
        $this->assertDatabaseHas('usage_events', [
            'idempotency_key' => 'before-upgrade',
            'segment_id' => $oldSegmentId,
        ]);
    }

    public function test_subscription_creation_prorates_its_first_calendar_cycle(): void
    {
        $merchantId = $this->createMerchant();
        $customerId = $this->createCustomer($merchantId);
        $planId = $this->createPlan($merchantId, 'Monthly', 31, 310, 1);

        $subscriptionId = app(SubscriptionSegmentService::class)->subscribe(
            $merchantId,
            $customerId,
            $planId,
            '2026-01-16 00:00:00'
        );

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionId,
            'merchant_id' => $merchantId,
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'started_at' => '2026-01-16 00:00:00',
            'current_period_start' => '2026-01-01 00:00:00',
            'current_period_end' => '2026-02-01 00:00:00',
            'billing_interval_unit' => 'month',
        ]);
        $this->assertDatabaseHas('subscription_segments', [
            'subscription_id' => $subscriptionId,
            'plan_id' => $planId,
            'effective_from' => '2026-01-16 00:00:00',
            'effective_to' => '2026-02-01 00:00:00',
            'base_price' => 31,
            'included_units' => 310,
        ]);

        $segmentId = (int) DB::table('subscription_segments')
            ->where('subscription_id', $subscriptionId)
            ->value('id');
        $this->createUsage(
            $merchantId,
            $customerId,
            $subscriptionId,
            $segmentId,
            'mid-cycle-start',
            170,
            '2026-01-20 12:00:00'
        );

        $invoiceId = app(InvoiceGenerationService::class)->generate(
            $subscriptionId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'subtotal' => 26,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoiceId,
            'item_type' => 'base_fee',
            'amount' => 16,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoiceId,
            'item_type' => 'overage',
            'quantity' => 10,
            'amount' => 10,
        ]);
    }

    public function test_due_invoice_generation_advances_the_subscription_cycle(): void
    {
        Carbon::setTestNow('2026-02-01 00:00:00');

        try {
            $merchantId = $this->createMerchant();
            $customerId = $this->createCustomer($merchantId);
            $planId = $this->createPlan($merchantId);
            $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
            $this->createSegment(
                $subscriptionId,
                $planId,
                '2026-01-01 00:00:00',
                '2026-02-01 00:00:00'
            );

            $invoiceIds = app(InvoiceGenerationService::class)->generateDue($subscriptionId);

            $this->assertCount(1, $invoiceIds);
            $this->assertDatabaseHas('invoices', [
                'id' => $invoiceIds[0],
                'period_start' => '2026-01-01 00:00:00',
                'period_end' => '2026-02-01 00:00:00',
            ]);
            $this->assertDatabaseHas('subscriptions', [
                'id' => $subscriptionId,
                'current_period_start' => '2026-02-01 00:00:00',
                'current_period_end' => '2026-03-01 00:00:00',
            ]);
            $this->assertDatabaseHas('subscription_segments', [
                'subscription_id' => $subscriptionId,
                'plan_id' => $planId,
                'effective_from' => '2026-02-01 00:00:00',
                'effective_to' => '2026-03-01 00:00:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_invoice_generation_job_can_be_retried_without_duplicate_invoices(): void
    {
        Carbon::setTestNow('2026-02-01 00:00:00');

        try {
            $merchantId = $this->createMerchant();
            $customerId = $this->createCustomer($merchantId);
            $planId = $this->createPlan($merchantId);
            $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
            $this->createSegment(
                $subscriptionId,
                $planId,
                '2026-01-01 00:00:00',
                '2026-02-01 00:00:00'
            );

            $job = new GenerateDueInvoicesJob;
            $job->handle(app(InvoiceGenerationService::class));
            $job->handle(app(InvoiceGenerationService::class));

            $this->assertDatabaseCount('invoices', 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_multiple_mid_cycle_plan_changes_keep_separate_pricing_segments(): void
    {
        $merchantId = $this->createMerchant();
        $customerId = $this->createCustomer($merchantId);
        $firstPlanId = $this->createPlan($merchantId, 'First', 10, 100, 1);
        $secondPlanId = $this->createPlan($merchantId, 'Second', 20, 200, 2);
        $thirdPlanId = $this->createPlan($merchantId, 'Third', 30, 300, 3);
        $service = app(SubscriptionSegmentService::class);
        $subscriptionId = $service->subscribe(
            $merchantId,
            $customerId,
            $firstPlanId,
            '2026-01-01 00:00:00'
        );

        $service->changePlan(
            $merchantId,
            $subscriptionId,
            $secondPlanId,
            '2026-01-10 00:00:00'
        );
        $service->changePlan(
            $merchantId,
            $subscriptionId,
            $thirdPlanId,
            '2026-01-20 00:00:00'
        );

        $segments = DB::table('subscription_segments')
            ->where('subscription_id', $subscriptionId)
            ->orderBy('effective_from')
            ->get();

        $this->assertCount(3, $segments);
        $this->assertSame(
            ['2026-01-01 00:00:00', '2026-01-10 00:00:00', $firstPlanId],
            [$segments[0]->effective_from, $segments[0]->effective_to, $segments[0]->plan_id]
        );
        $this->assertSame(
            ['2026-01-10 00:00:00', '2026-01-20 00:00:00', $secondPlanId],
            [$segments[1]->effective_from, $segments[1]->effective_to, $segments[1]->plan_id]
        );
        $this->assertSame($thirdPlanId, (int) $segments[2]->plan_id);
    }

    public function test_merchant_can_generate_only_its_due_invoices_from_dashboard(): void
    {
        Carbon::setTestNow('2026-02-01 00:00:00');

        try {
            $merchantId = $this->createMerchant();
            $otherMerchantId = $this->createMerchant('other-secret');

            foreach ([$merchantId, $otherMerchantId] as $ownerId) {
                $customerId = $this->createCustomer($ownerId);
                $planId = $this->createPlan($ownerId);
                $subscriptionId = $this->createSubscription($ownerId, $customerId, $planId);
                $this->createSegment(
                    $subscriptionId,
                    $planId,
                    '2026-01-01 00:00:00',
                    '2026-02-01 00:00:00'
                );
            }

            $this->signIn($merchantId);

            $this->post(route('merchant.invoices.generate-due', [
                'merchantId' => $merchantId,
            ]))
                ->assertRedirect(route('merchant.dashboard', ['merchantId' => $merchantId]))
                ->assertSessionHas(
                    'invoice_generation_message',
                    '1 due invoice(s) generated.'
                );

            $this->assertDatabaseCount('invoices', 1);
            $this->assertDatabaseHas('invoices', ['merchant_id' => $merchantId]);
            $this->assertDatabaseMissing('invoices', ['merchant_id' => $otherMerchantId]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_manual_invoice_generation_reports_when_nothing_is_due(): void
    {
        $merchantId = $this->createMerchant();
        $this->signIn($merchantId);

        $this->post(route('merchant.invoices.generate-due', [
            'merchantId' => $merchantId,
        ]))
            ->assertRedirect(route('merchant.dashboard', ['merchantId' => $merchantId]))
            ->assertSessionHas(
                'invoice_generation_message',
                'No invoices are due yet. Invoices can be generated after a billing period ends.'
            );

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_merchant_can_view_its_invoice_and_line_items_only(): void
    {
        $merchantId = $this->createMerchant();
        $otherMerchantId = $this->createMerchant('other-secret');
        $customerId = $this->createCustomer($merchantId);
        $planId = $this->createPlan($merchantId);
        $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
        $this->createSegment(
            $subscriptionId,
            $planId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );
        $invoiceId = app(InvoiceGenerationService::class)->generate(
            $subscriptionId,
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:00'
        );

        $this->signIn($merchantId);

        $this->get(route('merchant.dashboard', ['merchantId' => $merchantId]))
            ->assertOk()
            ->assertSee('bi-eye')
            ->assertSee('aria-label="View invoice #'.$invoiceId.'"', false);

        $this->get(route('merchant.invoices.show', [
            'merchantId' => $merchantId,
            'invoiceId' => $invoiceId,
        ]))
            ->assertOk()
            ->assertSee('Invoice #'.$invoiceId)
            ->assertSee('Customer')
            ->assertSee('Plan #'.$planId.' base fee (prorated)')
            ->assertSee('USD 10.00');

        $this->signIn($otherMerchantId);

        $this->get(route('merchant.invoices.show', [
            'merchantId' => $otherMerchantId,
            'invoiceId' => $invoiceId,
        ]))->assertNotFound();
    }

    public function test_dashboard_shows_same_period_churn_and_currency_specific_projection(): void
    {
        Carbon::setTestNow('2026-01-16 12:00:00');

        try {
            $merchantId = $this->createMerchant();
            $customerId = $this->createCustomer($merchantId);
            $planId = $this->createPlan($merchantId);
            $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
            $segmentId = $this->createSegment(
                $subscriptionId,
                $planId,
                '2025-12-01 00:00:00',
                '2026-02-01 00:00:00'
            );
            $this->createUsage(
                $merchantId,
                $customerId,
                $subscriptionId,
                $segmentId,
                'previous-month',
                500,
                '2025-12-10 12:00:00'
            );
            $this->createUsage(
                $merchantId,
                $customerId,
                $subscriptionId,
                $segmentId,
                'current-month',
                200,
                '2026-01-10 12:00:00'
            );

            $this->signIn($merchantId);

            $this->get(route('merchant.dashboard', ['merchantId' => $merchantId]))
                ->assertOk()
                ->assertSee('-60%')
                ->assertSee('USD 300.00')
                ->assertSee('Search subscription plans')
                ->assertSee('Search invoices')
                ->assertSee('Toggle navigation')
                ->assertSee('chart-container');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_json_api_returns_required_metrics_and_enforces_merchant_scope(): void
    {
        Carbon::setTestNow('2026-01-16 12:00:00');

        try {
            $merchantId = $this->createMerchant();
            $otherMerchantId = $this->createMerchant('other-secret');
            $customerId = $this->createCustomer($merchantId);
            $planId = $this->createPlan($merchantId, 'API plan', 100, 100, 2);
            $subscriptionId = $this->createSubscription($merchantId, $customerId, $planId);
            $segmentId = $this->createSegment(
                $subscriptionId,
                $planId,
                '2026-01-01 00:00:00',
                '2026-02-01 00:00:00'
            );
            $this->createUsage(
                $merchantId,
                $customerId,
                $subscriptionId,
                $segmentId,
                'dashboard-api-event',
                75,
                '2026-01-10 12:00:00'
            );
            $exactDropCustomerId = $this->createCustomer($merchantId);
            $exactDropPlanId = $this->createPlan($merchantId, 'Exact drop');
            $exactDropSubscriptionId = $this->createSubscription(
                $merchantId,
                $exactDropCustomerId,
                $exactDropPlanId
            );
            $exactDropSegmentId = $this->createSegment(
                $exactDropSubscriptionId,
                $exactDropPlanId,
                '2025-12-01 00:00:00',
                '2026-02-01 00:00:00'
            );
            $this->createUsage(
                $merchantId,
                $exactDropCustomerId,
                $exactDropSubscriptionId,
                $exactDropSegmentId,
                'exact-drop-previous',
                100,
                '2025-12-10 12:00:00'
            );
            $this->createUsage(
                $merchantId,
                $exactDropCustomerId,
                $exactDropSubscriptionId,
                $exactDropSegmentId,
                'exact-drop-current',
                50,
                '2026-01-10 12:00:00'
            );
            $riskCustomerId = $this->createCustomer($merchantId);
            $riskPlanId = $this->createPlan($merchantId, 'Risk');
            $riskSubscriptionId = $this->createSubscription($merchantId, $riskCustomerId, $riskPlanId);
            $riskSegmentId = $this->createSegment(
                $riskSubscriptionId,
                $riskPlanId,
                '2025-12-01 00:00:00',
                '2026-02-01 00:00:00'
            );
            $this->createUsage(
                $merchantId,
                $riskCustomerId,
                $riskSubscriptionId,
                $riskSegmentId,
                'risk-previous',
                100,
                '2025-12-10 12:00:00'
            );
            $this->createUsage(
                $merchantId,
                $riskCustomerId,
                $riskSubscriptionId,
                $riskSegmentId,
                'risk-current',
                40,
                '2026-01-10 12:00:00'
            );

            $this->getJson(
                '/api/merchants/'.$merchantId.'/dashboard',
                ['X-Merchant-Id' => (string) $merchantId, 'X-API-Key' => 'test-secret']
            )->assertOk()
                ->assertJsonPath('data.merchant.id', $merchantId)
                ->assertJsonPath('data.top_customers_by_usage.0.customer_id', $customerId)
                ->assertJsonPath('data.top_customers_by_usage.0.usage_units', 75)
                ->assertJsonStructure([
                    'data' => [
                        'periods' => ['timezone', 'current_month', 'previous_month_comparison'],
                        'projected_overage_revenue',
                        'customers_with_usage_drop_over_50_percent',
                    ],
                ])
                ->assertJsonCount(
                    1,
                    'data.customers_with_usage_drop_over_50_percent'
                )
                ->assertJsonPath(
                    'data.customers_with_usage_drop_over_50_percent.0.customer_id',
                    $riskCustomerId
                );

            $this->getJson(
                '/api/merchants/'.$otherMerchantId.'/dashboard',
                ['X-Merchant-Id' => (string) $merchantId, 'X-API-Key' => 'test-secret']
            )->assertForbidden();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_default_landing_page_opens_the_first_merchant_dashboard(): void
    {
        $merchantId = $this->createMerchant();
        $this->createMerchant('another-secret');

        $this->get('/')
            ->assertRedirect(route('merchant.dashboard', ['merchantId' => $merchantId]));
    }

    public function test_merchant_creation_command_only_stores_a_hashed_api_key(): void
    {
        $apiKey = str_repeat('k', 64);
        Str::createRandomStringsUsing(fn (int $length): string => str_repeat('k', $length));

        try {
            $this->artisan('billing:merchant-create', ['name' => 'Acme'])
                ->expectsOutputToContain('API key (shown once): '.$apiKey)
                ->assertExitCode(0);

            $merchant = DB::table('merchants')->where('name', 'Acme')->first();

            $this->assertNotNull($merchant);
            $this->assertNotSame($apiKey, $merchant->api_key_hash);
            $this->assertTrue(Hash::check($apiKey, $merchant->api_key_hash));
        } finally {
            Str::createRandomStringsUsing();
        }
    }

    public function test_merchant_management_is_session_protected_and_tenant_scoped(): void
    {
        $merchantId = $this->createMerchant();
        $otherMerchantId = $this->createMerchant('other-secret');

        $this->get(route('merchant.management', ['merchantId' => $merchantId]))
            ->assertRedirect(route('merchant.login', ['merchantId' => $merchantId]));
        $this->get(route('merchant.dashboard', ['merchantId' => $merchantId]))
            ->assertRedirect(route('merchant.login', ['merchantId' => $merchantId]));

        $this->post(route('merchant.login.store', ['merchantId' => $merchantId]), [
            'api_key' => 'wrong-key',
        ])->assertSessionHasErrors('api_key')
            ->assertSessionMissing('_old_input.api_key');

        $this->signIn($merchantId);

        $this->get(route('merchant.management', ['merchantId' => $merchantId]))
            ->assertOk()
            ->assertSee('Customer directory')
            ->assertSee('Plan catalog')
            ->assertSee('Subscription ledger');

        $this->get(route('merchant.management', ['merchantId' => $otherMerchantId]))
            ->assertRedirect(route('merchant.login', ['merchantId' => $otherMerchantId]));
    }

    public function test_merchant_can_create_customers_plans_and_subscriptions_and_change_plans(): void
    {
        Carbon::setTestNow('2026-01-10 12:00:00');

        try {
            $merchantId = $this->createMerchant();
            $customerId = $this->createCustomer($merchantId);
            $oldPlanId = $this->createPlan($merchantId, 'Starter', 25, 100, 1);
            $newPlanId = $this->createPlan($merchantId, 'Growth', 50, 300, 2);
            $this->signIn($merchantId);

            $this->post(route('merchant.customers.store', ['merchantId' => $merchantId]), [
                'name' => 'New customer',
                'email' => 'new@example.test',
            ])->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#customers');

            $newCustomerId = (int) DB::table('customers')
                ->where('merchant_id', $merchantId)
                ->where('email', 'new@example.test')
                ->value('id');

            $this->put(route('merchant.customers.update', [
                'merchantId' => $merchantId,
                'customerId' => $newCustomerId,
            ]), [
                'name' => 'Updated customer',
                'email' => 'updated@example.test',
            ])->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#customers');

            $this->assertDatabaseHas('customers', [
                'id' => $newCustomerId,
                'name' => 'Updated customer',
                'email' => 'updated@example.test',
            ]);

            $this->post(route('merchant.plans.store', ['merchantId' => $merchantId]), [
                'name' => 'Scale',
                'base_price' => 80,
                'currency' => 'usd',
                'billing_interval_count' => 1,
                'billing_interval_unit' => 'month',
                'included_units' => 500,
                'overage_rate' => 0.75,
                'is_active' => 1,
            ])->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#plans');

            $newPlanCreatedId = (int) DB::table('plans')
                ->where('merchant_id', $merchantId)
                ->where('name', 'Scale')
                ->value('id');

            $this->put(route('merchant.plans.update', [
                'merchantId' => $merchantId,
                'planId' => $newPlanCreatedId,
            ]), [
                'name' => 'Scale Plus',
                'base_price' => 90,
                'currency' => 'USD',
                'billing_interval_count' => 1,
                'billing_interval_unit' => 'month',
                'included_units' => 600,
                'overage_rate' => 0.5,
                'is_active' => 1,
            ])->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#plans');

            $this->assertDatabaseHas('plans', [
                'id' => $newPlanCreatedId,
                'name' => 'Scale Plus',
                'base_price' => 90,
                'currency' => 'USD',
            ]);

            $this->post(route('merchant.subscriptions.store', ['merchantId' => $merchantId]), [
                'customer_id' => $customerId,
                'plan_id' => $oldPlanId,
                'started_at' => '2026-01-01T00:00',
            ])->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#subscriptions');

            $subscriptionId = (int) DB::table('subscriptions')
                ->where('merchant_id', $merchantId)
                ->where('customer_id', $customerId)
                ->value('id');

            $this->put(route('merchant.subscriptions.change-plan', [
                'merchantId' => $merchantId,
                'subscriptionId' => $subscriptionId,
            ]), [
                'plan_id' => $newPlanId,
                'effective_at' => '2026-01-09T00:00',
            ])->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#subscriptions');

            $this->assertDatabaseHas('subscription_segments', [
                'subscription_id' => $subscriptionId,
                'plan_id' => $oldPlanId,
                'effective_to' => '2026-01-09 00:00:00',
            ]);
            $this->assertDatabaseHas('subscription_segments', [
                'subscription_id' => $subscriptionId,
                'plan_id' => $newPlanId,
                'effective_from' => '2026-01-09 00:00:00',
            ]);

            $this->delete(route('merchant.customers.destroy', [
                'merchantId' => $merchantId,
                'customerId' => $customerId,
            ]))->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#customers');
            $this->assertDatabaseHas('customers', ['id' => $customerId]);

            $this->post(route('merchant.subscriptions.cancel', [
                'merchantId' => $merchantId,
                'subscriptionId' => $subscriptionId,
            ]))->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]).'#subscriptions');

            $this->assertDatabaseHas('subscriptions', [
                'id' => $subscriptionId,
                'status' => 'canceled',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_plan_pricing_cache_can_be_invalidated_after_a_plan_update(): void
    {
        Cache::flush();
        $merchantId = $this->createMerchant();
        $planId = $this->createPlan($merchantId, 'Cached', 10, 100, 1);
        $pricing = app(PlanPricingService::class);

        $this->assertSame(1.0, (float) $pricing->get($merchantId, $planId)->overage_rate);

        DB::table('plans')->where('id', $planId)->update(['overage_rate' => 3]);
        $this->assertSame(1.0, (float) $pricing->get($merchantId, $planId)->overage_rate);

        $pricing->invalidate($merchantId, $planId);
        $this->assertSame(3.0, (float) $pricing->get($merchantId, $planId)->overage_rate);
    }

    private function createMerchant(string $secret = 'test-secret'): int
    {
        return (int) DB::table('merchants')->insertGetId([
            'name' => 'Merchant',
            'api_key_hash' => Hash::make($secret),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function signIn(int $merchantId): void
    {
        $this->post(route('merchant.login.store', ['merchantId' => $merchantId]), [
            'api_key' => $merchantId === 1 ? 'test-secret' : 'other-secret',
        ])->assertRedirect(route('merchant.management', ['merchantId' => $merchantId]));
    }

    private function createCustomer(int $merchantId): int
    {
        return (int) DB::table('customers')->insertGetId([
            'merchant_id' => $merchantId,
            'name' => 'Customer',
            'email' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPlan(
        int $merchantId,
        string $name = 'Plan',
        int $basePrice = 10,
        int $includedUnits = 100,
        int $overageRate = 1
    ): int {
        return (int) DB::table('plans')->insertGetId([
            'merchant_id' => $merchantId,
            'name' => $name,
            'base_price' => $basePrice,
            'currency' => 'USD',
            'billing_interval_count' => 1,
            'billing_interval_unit' => 'month',
            'included_units' => $includedUnits,
            'overage_rate' => $overageRate,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSubscription(int $merchantId, int $customerId, int $planId): int
    {
        return (int) DB::table('subscriptions')->insertGetId([
            'merchant_id' => $merchantId,
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'status' => 'active',
            'started_at' => '2026-01-01 00:00:00',
            'current_period_start' => '2026-01-01 00:00:00',
            'current_period_end' => '2026-02-01 00:00:00',
            'billing_interval_count' => 1,
            'billing_interval_unit' => 'month',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSegment(
        int $subscriptionId,
        int $planId,
        string $from,
        string $to,
        int $basePrice = 10,
        int $includedUnits = 100,
        int $overageRate = 1
    ): int {
        return (int) DB::table('subscription_segments')->insertGetId([
            'subscription_id' => $subscriptionId,
            'plan_id' => $planId,
            'effective_from' => $from,
            'effective_to' => $to,
            'base_price' => $basePrice,
            'currency' => 'USD',
            'included_units' => $includedUnits,
            'overage_rate' => $overageRate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUsage(
        int $merchantId,
        int $customerId,
        int $subscriptionId,
        int $segmentId,
        string $key,
        int $units,
        string $occurredAt
    ): void {
        DB::table('usage_events')->insert([
            'merchant_id' => $merchantId,
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
            'segment_id' => $segmentId,
            'idempotency_key' => $key,
            'units' => $units,
            'occurred_at' => $occurredAt,
            'created_at' => now(),
        ]);
    }
}
