# Mallow Billing

A Laravel 12 mini-application for tenant-scoped usage metering, subscription billing, and a merchant dashboard.

## Requirements and setup

- PHP 8.2+
- Composer
- PHP BCMath extension for exact decimal billing arithmetic
- SQLite for a local exercise, or MySQL 8+ for a shared deployment
- A queue worker and Laravel scheduler for background aggregation and cycle-end invoices

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan billing:merchant-create "Acme"
php artisan test
```

The application targets Laravel 12 and PHPUnit 11. Main code is organized under `app/Http` (web/API controllers, middleware, and request validation), `app/Services` (billing periods, segments, usage, pricing, and invoices), `app/Jobs` and `app/Console/Commands` (queued work and recovery commands), and `database/migrations` (fresh-install schema). Feature coverage is in `tests/Feature/BillingWorkflowTest.php`.

The merchant command prints a random API key once. Save it securely; only its hash is stored. Open `/`, enter the key to sign in, and then manage customers, plans and subscriptions.

The migrations create a fresh application schema. Use a new, empty database for this project; do not point it at a pre-existing billing database with tables from another schema version. For MySQL, create a separate database, update `DB_DATABASE` in `.env`, then run `php artisan migrate`. Back up any existing database before changing its schema.

For local use, configure `DB_CONNECTION=sqlite` and set `DB_DATABASE` to an existing SQLite file. Run a queue worker and scheduler in separate terminals:

```powershell
php artisan queue:work
php artisan schedule:work
```

The `billing:aggregate-previous-day-usage` command queues the daily rollup for the previous day at 00:10; it calculates the date when the scheduled command runs. Requeue a failed or stale date with `php artisan billing:aggregate-usage 2026-10-28`. Due invoices are queued at 00:20. A production deployment should run `php artisan schedule:run` every minute through its process scheduler and use a durable queue such as Redis or the database queue.

## Architecture

- `merchants` own customers, plans, subscriptions, usage, daily rollups, and invoices.
- `SubscriptionSegmentService::subscribe()` creates a tenant-validated subscription and snapshots its initial pricing. The first billing period is aligned to the calendar boundary for its interval (Monday for weeks, first of the month for months, January 1 for years); a mid-cycle signup is billed only for the remaining period and receives a proportionate included allowance. Subsequent periods follow the selected interval. `changePlan()` closes and opens segments without rewriting previously recorded usage.
- A subscription's `subscription_segments` store an immutable snapshot of the plan price, allowance, currency, and overage rate for each effective period. Plan changes close one segment and open another; usage events keep the segment ID selected by event time.
- `POST /api/usage` authenticates a merchant using `X-Merchant-Id` and `X-API-Key`, validates the active subscription and effective segment, and inserts one event. The API key is stored as a password hash in `merchants.api_key_hash`; provision it with Laravel `Hash::make()` and return the plaintext key only to the merchant once.
- The usage endpoint is limited to 120 requests per minute per authenticated merchant. Send an `idempotency_key` that is unique for the merchant. Replays with the same payload return the original event; reusing a key for a different payload returns HTTP 409.
- The usage request is validated by `RecordUsageRequest`. Configure the per-merchant usage rate limit with `BILLING_USAGE_RATE_LIMIT` (default: 120 requests per minute); the limiter identity comes from the authenticated merchant API key, not an untrusted request body field.
- The merchant dashboard and management pages require a session established with the merchant's API key. Sign in at `/merchants/{id}/login`; customers, plans, subscriptions, plan changes and cancellations are then scoped to that merchant.
- `AggregateDailyUsage` rolls raw events into `daily_usage` in grouped batches of at most 500 rows. It replaces that date's aggregates transactionally, so retrying the job does not double-count.
- `InvoiceGenerationService` calculates each segment's base fee by its fraction of the full billing period, prorates included units by that same fraction, and prices excess usage at the segment's saved overage rate. The period's segment snapshots ensure that plan changes do not retroactively alter charges. A unique subscription/period constraint and row lock prevent duplicate invoices.
- `GenerateDueInvoicesJob` runs daily at 00:20, scans due active subscriptions in batches of 100, invoices elapsed periods, and advances each subscription. `billing:generate-due-invoices` is also available for an explicit synchronous CLI run.
- `PlanPricingService` caches active merchant-scoped pricing for 10 minutes. Plan edits in the management UI invalidate the cache after saving; segment snapshots remain unchanged.
- Cache misses use `Cache::remember` with the configured store and a 10-minute TTL. Explicit plan edits invalidate the merchant/plan key. Cache is never the historical invoice source; pricing is snapshotted into subscription segments. A distributed cache lock is not used to suppress simultaneous cache misses in this exercise; consider one if production measurements show stampedes.
- `GET /merchants/{id}/dashboard` provides the browser dashboard after session authentication. The JSON API is also available at `GET /api/merchants/{id}/dashboard` with `X-Merchant-Id` and `X-API-Key` headers. Its top-five metric sums usage units in the current calendar month, its projection estimates full-cycle overage per current plan segment using observed usage pace, and churn compares month-to-date usage with the same elapsed duration in the previous month. The JSON response includes both comparison boundaries and identifies the application timezone.
- The default `/` landing page opens the first merchant's dashboard when a workspace exists; on a fresh install it shows a workspace setup page.
- Invoices in the dashboard list link to merchant-scoped invoice detail pages with customer, billing period, status, and itemized base/overage charges. The detail page supports browser printing; payment collection is not enabled.

## Data model and scale

The billing migration keeps merchant/customer/plan/subscription records separate from high-volume events. Foreign keys protect ownership relationships. Important indexes include:

| Query | Supporting index |
|---|---|
| Merchant customer directory | `customers(merchant_id, name)` |
| Merchant plan catalog | `plans(merchant_id, is_active)` and unique `plans(merchant_id, name)` |
| Customer subscription status | `subscriptions(customer_id, status)` |
| Due invoice scan | `subscriptions(status, current_period_end)` |
| Usage idempotency | Unique `usage_events(merchant_id, idempotency_key)` |
| Merchant dashboard usage range | `usage_events(merchant_id, occurred_at)` |
| Subscription cycle usage | `usage_events(subscription_id, occurred_at)` |
| Segment-specific usage | `usage_events(segment_id, occurred_at)` |
| Daily aggregate replacement and reporting | Unique `daily_usage(segment_id, usage_date)`, `daily_usage(merchant_id, usage_date, customer_id)` |
| Invoice uniqueness and merchant history | Unique `invoices(subscription_id, period_start, period_end)`, `invoices(merchant_id, created_at)` |

At five million events, the event table and its secondary indexes consume substantial disk and make every insert more expensive; measure real index sizes and throughput on production-like hardware instead of assuming a benchmark. The current design bounds aggregate result batches to 500 grouped rows and uses SQL `SUM/GROUP BY`. The dashboard currently reads indexed raw events; `daily_usage` is a derived/reconciliation table, not yet the dashboard read source. Reprocessing one date replaces its derived rows transactionally, which is retry-safe but can hold locks for a large date; monitor transaction time and queue lag. Consider monthly range partitioning or archiving when retention, index maintenance, or measured query latency justifies it. MySQL requires every unique key on a partitioned table to include the partition key, so the existing merchant-wide idempotency constraint cannot simply remain on a time-partitioned event table: preserve it in a separate unpartitioned idempotency ledger before partitioning. Keep raw events for invoice reconciliation; daily aggregates can become the primary source for completed-day dashboard queries after a consistency/reconciliation policy is added. Read replicas, summary tables, or more queue workers should be introduced based on measured query latency, replication lag, and queue backlog. No five-million-row load test is claimed.

Money is calculated with BCMath decimal strings; the database persists decimal amounts. Monetary line amounts and invoice totals are rounded half-up to two decimal places. Usage and prorated allowances use up to 12 decimal places internally; invoice-line quantities are stored to four places. Values are rounded only at the documented persistence/display boundary, avoiding binary floating-point billing calculations.

## Assumptions and trade-offs

- Usage is an integer count greater than zero; each event belongs to one active subscription and exactly one effective plan segment.
- Billing periods are half-open (`start <= timestamp < end`). A first mid-cycle signup is prorated from its effective start through the aligned cycle end; plan-segment base price and included allowance are prorated by segment duration, with overage calculated independently for each segment.
- A plan's billing interval is copied to the subscription. A mid-cycle plan change changes price/allowance snapshots, not the current cycle boundary.
- A single invoice cannot mix currencies; a mid-cycle change to a different currency is rejected rather than silently summing unlike amounts.
- Invoice generation currently issues the invoice record; payment collection, tax, credits, and currency conversion are outside this exercise.
- Plan changes through the management UI invalidate the active pricing cache after saving; subscription segments keep their historical pricing snapshots.
- Native table partitioning is a deployment decision, not enabled by default, so local SQLite tests and portable migrations remain straightforward.
- Billing timestamps are interpreted in the configured Laravel application timezone (`APP_TIMEZONE`) and stored as date-times; configure application and database hosts consistently to UTC for production. Calendar-day proration is measured as elapsed seconds across actual calendar boundaries, including daylight-saving changes if a non-UTC timezone is configured.

## API examples

Usage ingestion is `POST /api/usage`. The authenticated merchant is identified by the headers, while the subscription determines its customer and active pricing segment:

```http
POST /api/usage
X-Merchant-Id: 1
X-API-Key: <merchant-api-key>
Content-Type: application/json

{
  "subscription_id": 501,
  "idempotency_key": "evt_abc123",
  "occurred_at": "2026-10-28T10:30:00Z",
  "units": 1
}
```

A new event returns `201`; a replay with the identical payload returns `200`; reuse of the key with a different payload returns `409`; invalid payload returns `422`; unauthenticated calls return `401`; and excessive calls return `429`. The JSON dashboard uses `GET /api/merchants/{id}/dashboard` with the same headers and returns `top_customers_by_usage`, `projected_overage_revenue`, and `customers_with_usage_drop_over_50_percent` under `data`. A merchant ID in the URL must match the authenticated merchant.

Example dashboard response shape:

```json
{
  "data": {
    "merchant": {"id": 1, "name": "Acme"},
    "periods": {
      "timezone": "UTC",
      "current_month": {"start": "2026-10-01T00:00:00+00:00", "end": "2026-11-01T00:00:00+00:00"},
      "previous_month_comparison": {"start": "2026-09-01T00:00:00+00:00", "end": "2026-09-29T00:00:00+00:00"}
    },
    "top_customers_by_usage": [
      {"customer_id": 101, "customer_name": "Example customer", "usage_units": 1200}
    ],
    "projected_overage_revenue": [
      {"currency": "USD", "amount": "25.00"}
    ],
    "customers_with_usage_drop_over_50_percent": [
      {"customer_id": 102, "customer_name": "Another customer", "previous_usage_units": 1000, "current_usage_units": 400, "drop_percentage": 60}
    ]
  }
}
```

The JSON is illustrative; values depend on stored merchant data. Current-month and previous-month comparison bounds are half-open. The churn comparison uses only the elapsed portion of the current month against the same elapsed duration of the prior month, avoiding a misleading full-month comparison during an incomplete month. Customers with no prior-period usage are excluded.

## Requirement and verification checklist

| Assignment requirement | Implementation | Verification/status |
|---|---|---|
| Normalized multi-tenant schema, foreign keys, indexes | `database/migrations/2026_10_28_000000_create_billing_tables.php` | Migration creates schema on a fresh database; index strategy above |
| Usage endpoint and durable idempotency | `UsageController`, unique merchant/key index | Tests cover auth, accepted event, identical replay, conflicting replay, and tenant mismatch |
| Per-merchant rate limiting | `BILLING_USAGE_RATE_LIMIT`, authenticated middleware priority before `throttle:usage` | Test verifies the third request is `429` at a test limit of two |
| Chunked daily aggregation and retries | `AggregateDailyUsage`, scheduled previous-day command, and `billing:aggregate-usage {date}` recovery command | Tests verify repeat processing does not double-count, scheduled/recovery dispatch, and invalid-date rejection; chunk batches are 500 rows |
| Cycle invoice generation and plan-segment pricing | `InvoiceGenerationService`, `GenerateDueInvoicesJob` | Tests cover plan changes, proration, allowances, idempotency, cycle advancement, and manual UI scope |
| Pricing cache and invalidation | `PlanPricingService`, plan update controller | Cache invalidation test |
| Browser dashboard and authenticated JSON dashboard | `DashboardController`, `/api/merchants/{id}/dashboard` | Tests verify dashboard metrics and cross-merchant rejection |
| Scalability analysis | This README | Design analysis documented; no load benchmark claimed |
| Prompt evidence and narrated video | Candidate-captured artifacts | Not generated in this coding session; see [prompts/README.md](./prompts/README.md) |

Run `php artisan test` for the automated suite. The automated suite currently includes explicit cases for month-end calendar boundaries, mid-cycle subscription proration, overage, and duplicate invoice generation. It does not simulate true concurrent database connections, perform a five-million-event benchmark, or inject a database failure between invoice writes; those are remaining validation opportunities before production.

## Walkthrough outline (5–10 minutes)

1. Show the merchant sign-in, dashboard navigation, and the manual due-invoice control.
2. Explain the merchant-scoped schema, usage indexes, and unique idempotency key.
3. Demonstrate `POST /api/usage`, the API-key middleware, `201` creation, identical retry (`200`), and conflicting retry (`409`).
4. Describe the scheduled daily rollup job, its grouped 500-row batches, idempotent replacement, retries, and queue worker.
5. Walk through plan pricing snapshots and a mid-cycle change that assigns usage to distinct effective segments.
6. Explain elapsed-seconds proration, per-segment allowance and overage, decimal arithmetic, and invoice uniqueness.
7. Show the dashboard JSON panels and clarify that projected overage is a forecast, not an issued charge.
8. Run `php artisan test`, then explain the five-million-row trade-offs and what production load testing and partitioning would require.

## Assignment submission items

Capture actual AI prompt screenshots from the IDE and record the narrated walkthrough yourself. No screenshots, benchmark results, GitHub sharing, or recording are claimed by this repository.
