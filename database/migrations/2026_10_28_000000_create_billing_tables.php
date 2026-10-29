<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_key_hash')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
            $table->unique(['merchant_id', 'email']);
            $table->index(['merchant_id', 'name']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('base_price', 12, 2);
            $table->char('currency', 3);
            $table->unsignedSmallInteger('billing_interval_count')->default(1);
            $table->string('billing_interval_unit', 10)->default('month');
            $table->unsignedBigInteger('included_units')->default(0);
            $table->decimal('overage_rate', 12, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['merchant_id', 'name']);
            $table->index(['merchant_id', 'is_active']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->index();
            $table->dateTime('started_at');
            $table->dateTime('current_period_start');
            $table->dateTime('current_period_end');
            $table->unsignedSmallInteger('billing_interval_count')->default(1);
            $table->string('billing_interval_unit', 10)->default('month');
            $table->timestamps();
            $table->index(['merchant_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'current_period_end']);
        });

        Schema::create('subscription_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->dateTime('effective_from');
            $table->dateTime('effective_to');
            $table->decimal('base_price', 12, 2);
            $table->char('currency', 3);
            $table->unsignedBigInteger('included_units')->default(0);
            $table->decimal('overage_rate', 12, 4)->default(0);
            $table->timestamps();
            $table->index(['subscription_id', 'effective_from', 'effective_to'], 'segments_period_idx');
        });

        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('segment_id')->constrained('subscription_segments')->restrictOnDelete();
            $table->string('idempotency_key', 100);
            $table->unsignedBigInteger('units');
            $table->dateTime('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['merchant_id', 'idempotency_key']);
            $table->index('occurred_at', 'usage_time_idx');
            $table->index(['merchant_id', 'occurred_at'], 'usage_merchant_time_idx');
            $table->index(['subscription_id', 'occurred_at'], 'usage_subscription_time_idx');
            $table->index(['segment_id', 'occurred_at'], 'usage_segment_time_idx');
            $table->index(['customer_id', 'occurred_at'], 'usage_customer_time_idx');
        });

        Schema::create('daily_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('segment_id')->constrained('subscription_segments')->restrictOnDelete();
            $table->date('usage_date');
            $table->unsignedBigInteger('total_units');
            $table->timestamps();
            $table->unique(['segment_id', 'usage_date']);
            $table->index('usage_date');
            $table->index(['merchant_id', 'usage_date', 'customer_id'], 'daily_merchant_date_customer_idx');
            $table->index(['customer_id', 'usage_date']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->char('currency', 3);
            $table->string('status', 20)->default('draft');
            $table->dateTime('issued_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['subscription_id', 'period_start', 'period_end'],
                'invoices_subscription_period_unique'
            );
            $table->index(['merchant_id', 'created_at']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segment_id')->constrained('subscription_segments')->restrictOnDelete();
            $table->string('description');
            $table->string('item_type', 30);
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->index(['invoice_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('daily_usage');
        Schema::dropIfExists('usage_events');
        Schema::dropIfExists('subscription_segments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('merchants');
    }
};
