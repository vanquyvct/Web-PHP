# GreenFood

A student e-commerce application built with Laravel 12, Sanctum, and an existing HTML/CSS/JavaScript storefront. The foundation milestone makes local setup portable; the Blade/Tailwind redesign and commerce hardening are still planned.

## Requirements

- PHP 8.2+ with Composer 2 and Laravel's standard extensions, including PDO SQLite, fileinfo, mbstring, XML, curl, and zip.
- Node.js 22 LTS or newer and npm (verified with Node 24).
- SQLite is the local default: no MySQL, Redis, Docker, or external account required.
- On Windows, enable `extension=pdo_sqlite` and `extension=sqlite3` in the PHP CLI's php.ini if needed. Run `php --ini` to find it.

## First-time setup

From the repository directory:

```sh
composer install
npm ci
```

Copy `.env.example` to `.env` if `.env` does not already exist. Use `Copy-Item .env.example .env` in PowerShell or `cp .env.example .env` on macOS/Linux. Never overwrite an existing environment file blindly.

```sh
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan storage:link
```

These instructions are for a new local SQLite database. Check `DB_CONNECTION` and `DB_DATABASE` before migrating any existing installation. Do not use `migrate:fresh` or `db:wipe` on a database you need to keep. Only generate a key for a new installation; changing an existing key invalidates encrypted data.

Before seeding, set `DEMO_ADMIN_PASSWORD` in your private `.env` to a unique password of at least 12 characters, then run:

```sh
php artisan db:seed
npm run build
composer run dev
```

Open http://127.0.0.1:8000 (not the Vite port). `composer run dev` runs Laravel, the queue listener, and Vite; Ctrl+C stops them. Alternatively run `php artisan serve` and `npm run dev` in separate terminals. Pail is omitted from the combined command because it requires Unix process-control support unavailable in native Windows PHP.

The existing static pages load their CSS/JS directly; Vite builds the existing Laravel/Tailwind scaffold for future work. Serving the app still works after `npm run build` without a running Vite server.

If Windows refuses `storage:link`, enable Developer Mode or run that command in an elevated terminal. Product photos are copied into `storage/app/public/demo` by the seeder and served through this link. Do not serve the repository root through Apache: its document root must be `public`. APIs and pages now use the same origin; there is no required `/GreenFood/public` installation path.

## Demo data and accounts

The seeder provides eight products, four categories matching the existing filters, varied stock, four discounts, and local images. Categories retain the existing string field; no schema changes were needed.

| Account | Email | Password |
| --- | --- | --- |
| Customer | customer@greenfood.test | GreenFood-demo-2026 |
| Administrator | admin@greenfood.test | Your private DEMO_ADMIN_PASSWORD |

Demo seeding refuses environments other than `local`/`testing`. It creates missing records without resetting existing passwords, prices, stock, or roles. If the admin email already belongs to a customer, seeding refuses to promote it. Re-running the seed is safe for existing records; it does not reset the demo. These are local demo accounts, not deployment credentials. Remove `DEMO_ADMIN_PASSWORD` from `.env` after initial seeding if desired.

For a separately provisioned administrator, use the CLI-only command:

```sh
php artisan greenfood:create-admin owner@example.com --name="Store Owner"
```

It prompts for a hidden password and confirmation, requires 12 characters, and refuses any existing email. Passwords are never command-line arguments. Public signup always creates customers, including signup with the former special administrator email.

## Verification

```sh
php artisan test
npm run build
php artisan route:list
```

Tests use an in-memory SQLite database via `phpunit.xml`. Foundation tests cover signup privileges, safe admin creation, guarded/repeatable demo seeding, and the root route. They do not certify checkout correctness. After startup, visit `/`, `/Product-list.html`, and `/api/products`; log in with each demo account to reach customer/admin pages.

Use `composer install` and `npm ci` for subsequent reproducible installs. Use `npm install` only when intentionally changing the dependency lockfile. PHP dependencies remain pinned by `composer.lock`.

## Structure

- `routes/api.php`: existing public/customer/admin JSON endpoints.
- `routes/web.php`: serves the existing landing page at `/`.
- `app/Http/Controllers/Api`: existing commerce and account behavior.
- `app/Console/Commands/CreateAdmin.php`: safe CLI administrator provisioning.
- `database/seeders/DatabaseSeeder.php`: local-only demo dataset.
- `public/*.html`, `public/assets`: current storefront and admin screens.
- `resources`: Laravel Blade/Tailwind/Vite scaffold, not yet the main storefront.

The unused, syntactically corrupted `product-list-user.js` was removed; the catalog page retains its working inline implementation. Asset path casing and the missing image fallback were corrected.

## Known limitations / next milestone

This foundation is not production-ready. Stored-XSS risks, localStorage tokens, missing account-lock enforcement/throttling, checkout/cancellation races, stale order totals, destructive historical foreign-key cascades, and SQLite-incompatible monthly/yearly revenue queries remain for the security/commerce milestone. Bank transfer is a manual placeholder workflow with legacy instructions, not verified payment processing. No real payment should be made during a demo. The storefront has not been redesigned.
