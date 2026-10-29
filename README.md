 Mallow Billing

 Architecture summary

This Laravel 12 application provides a merchant-scoped usage-metering and
subscription-billing workflow:

1. A merchant owns its customers, plans, subscriptions, usage, and invoices.
2. A subscription snapshots a plan's price, currency, included units, and
   overage rate into effective-dated subscription segments.
3. The authenticated usage API validates a subscription and assigns each event
   to the segment effective at its occurrence time. A merchant-scoped
   idempotency key prevents duplicate usage records.
4. A queued job aggregates raw usage events by date into derived daily totals.
   The job is retryable and writes grouped data in batches of at most 500.
5. Invoice generation calculates prorated base charges and allowances, then
   applies each segment's overage rate. Invoice and line-item writes are
   transactional, with row locking and a unique subscription-period
   constraint to protect retries.
6. Merchants can manage their workspace and view dashboard metrics. The
   invoice list has a merchant-protected detail page with printable,
   itemized charges.

Main code areas:

app/Http/ — web and API controllers, middleware, and request validation.
app/Services/ — billing periods, pricing, subscription segments, usage
  projections, and invoice calculations.
app/Jobs/ and app/Console/Commands/ — queued work and operational
  commands.
routes/ — web, API, and scheduler definitions.
database/migrations/ — fresh-install schema.
resources/views/ — merchant dashboard, management, navigation, and
  invoice pages.
tests/Feature/BillingWorkflowTest.php — end-to-end feature coverage.

 Requirements

PHP 8.2 or newer.
Composer.
PHP BCMath extension.
MySQL 8+

 Fresh setup and run (PowerShell)

Run these commands from the repository root:

step1)

composer install
php artisan key:generate

new login 
php artisan migrate
php artisan billing:merchant-create "Acme Corp"


if existing login detailslogin key:

php artisan tinker

<?php
DB::table('merchants')->where('id', 1)->exists()

<?php
$key = Illuminate\Support\Str::random(64);
DB::table('merchants')->where('id', 1)->update(['api_key_hash' => Illuminate\Support\Facades\Hash::make($key), 'updated_at' => now()]);
echo $key;

<?php
exit

Example output:
<PLAINTEXT_MERCHANT_KEY_SHOWN_ONCE>

http://localhost/mallow-billing/public/merchants/1/dashboard 
paste the output key 


Assumptions and design decisions

Tenant ownership: Data belongs to a merchant. The authenticated merchant
  identity is authoritative; API request bodies cannot select another
  merchant's records.
API key storage: Keys are random secrets, displayed only at creation,
  stored as password hashes, and cannot be recovered in plaintext. If a key
  is lost or exposed, replace it; the old key will stop working.
Usage idempotency: The idempotency key is unique per merchant. Replaying
  the same payload returns the original event; using the key for a different
  payload returns HTTP 409.
Time ranges: Subscription segments and reporting intervals use
  half-open boundaries (start <= timestamp < end) to avoid assigning an
  event to two periods.
First billing cycle: The first period aligns to the calendar boundary of
  the billing interval. A mid-cycle start receives a prorated base fee and
  included allowance for the remaining period.
Plan changes: A plan change opens a new pricing segment. Historical
  segments and their pricing snapshots are retained so later plan edits do not
  rewrite prior billing.
Money: BCMath decimal arithmetic is used for billing. Monetary amounts
  are rounded half-up to two decimals at the documented persistence boundary.
Currency: A single invoice cannot combine different currencies. A
  currency-changing plan change within one invoice period is rejected.
Usage rollups: Raw usage events remain the detailed record. Daily
  aggregates are derived and can be safely rebuilt for a date. The current
  dashboard still reads indexed raw events rather than using daily aggregates
  as its primary source.
Invoices: Invoice generation records charges and line items. Payment
  collection, taxes, credits, and currency conversion are outside the current
  application scope.
Database migration: The included schema migration is for a fresh
  database. Do not run it blindly against an older database with existing
  billing tables. Back up existing data and use a separate empty database
  unless a reviewed upgrade migration is provided.
Scale claims: Indexes, bounded aggregation batches, and partitioning
  trade-offs are documented, but no five-million-row benchmark or true
  concurrent-connection test is claimed.

