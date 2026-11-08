# AGENTS.md

Kamsya is a Laravel 13 Blade monolith for goat-farm inventory and finance. All UI copy is Bahasa Indonesia; currency uses `App\Support\Format` (`rupiah`, `date`, etc.).

## Stack & layout

- PHP 8.3, Laravel 13, MySQL database `kamsya_db`. No SPA and no API layer — controllers return Blade views.
- No Node/Vite/Tailwind step exists. Frontend assets are hand-written in `public/assets/css|js` — edit those directly and do not reintroduce npm tooling unless explicitly requested.
- The old frontend prototype is preserved by the git tag `prototype-v1`; it is not in the working tree. Do not copy code from it.
- Domain logic belongs in `app/Services/*` (Goat, Purchase, Sale, Payment, Expense, Report, Backup, Sequence, ActivityLogger). Controllers stay thin; don't duplicate stock/payment/report calculations.
- `README.md` documents setup and features for humans; keep it in sync when changing setup or reference figures.

## Commands

```bash
composer install
copy .env.example .env && php artisan key:generate   # PowerShell
mysql -u root -e "CREATE DATABASE kamsya_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed                     # destructive; demo data only
php artisan serve
php artisan test                                     # SQLite :memory:, no MySQL needed
php artisan test tests/Feature/ReportTest.php --filter test_report_summary_matches_seed_reference_values
```

- `tests/TestCase.php` applies `RefreshDatabase` and runs `db:seed` in `setUp()`. Keep that; tests depend on the demo dataset.
- After changing Blade/layout composers, run `php artisan view:clear` if compiled views look stale.
- `php -l <file>` is the quick syntax check; `php artisan test` is the real verification.

## Demo data & examples

- Accounts (password `kamsya123`): `owner@kamsya.id`, `admin@kamsya.id`, `petugas@kamsya.id`.
- `DemoDataSeeder` defines the fixed dataset and `clearData()` must keep its child-first delete order (null `goats.mother_id` first) for SQLite/MySQL portability.
- Reference totals asserted by `ReportTest`: sales 12.400.000; COGS 7.550.000; gross 4.850.000; expenses 1.150.000; net 3.700.000; inventory 19.575.000; AR 1.400.000; AP 2.400.000.

## Architecture gotchas

- Three fixed roles in `App\Enums\UserRole` (`owner|admin|keeper`); authorization is policies + gates in `AppServiceProvider` (`manage-transactions`, `manage-settings`, `view-reports`, …). No dynamic permission package.
- URLs are Indonesian but route names are English: `/cetak-barcode`=`goats.labels`, `/aktivitas`=`activity.index`, `/kambing`=`goats.index`, `/pembelian`=`purchases.*`, `/penjualan`=`sales.*`, `/beban`=`expenses.*`, `/laporan`=`reports.*`, `/pengaturan`=`settings.*`, `/pengguna`=`users.*`, `/profil`=`profile.edit`. Check `routes/web.php` before naming routes in tests.
- Route-model binding uses document codes, not PKs (`Goat`, `Purchase`, `Sale`, `Supplier`, `Customer` override `getRouteKeyName()` to `code`). Use `KMB-0001`, `TRX-B-2026-001`, `TRX-J-2026-001` in tests/URLs.
- `$settings` is shared with **all** views through the `kamsya.settings` scoped binding + `View::composer('*')`. Don't revert to per-layout composers: child views render before layouts and would lose it.
- Money columns are decimal casts returning strings such as `"500000.00"`. In tests compare `(float)` values, not raw strings. Enum casts mean compare `GoatStatus::Terjual`, not `GoatStatus::Terjual->value`.
- Financial data is never hard-deleted: goats/master data use `SoftDeletes`, transactions/payments/expenses use `voided_at`. Sale `cost_price` is an immutable snapshot of `purchase.total` (price + additional cost).
- Payments are polymorphic with enforced morph map types `purchase` and `sale`.
- Report tabs: `laba-rugi`, `arus-kas`, `piutang`, `utang`, `laba-kambing`, `penjualan`, `pembelian`, `stok`. CSV/PDF exports use `StreamedResponse`, so tests must call `$response->streamedContent()`, not `getContent()`. PDF uses `barryvdh/laravel-dompdf` A4 landscape.
- `BackupService::import()` contains SQLite-specific `PRAGMA defer_foreign_keys` handling so round-trips work under `RefreshDatabase`; preserve it.

## Test-writing traps

- `/` redirects to `/dashboard`; asserting 200 on `/` fails.
- `--filter` matches the PHP method name (`test_...`), not the human-readable test description.
- Role smoke tests use 403 expectations for owner-only routes; check `PageSmokeTest` / `AuthorizationTest` before adding routes.
- Demo seeder already contains sold/unsold goats and partial payments; pick records by code in tests to avoid order-dependent failures.
