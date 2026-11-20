# accountant_appamora

**Accountant by AppAmora** — a calculation for daily expenses in small businesses and individuals.

Track income and expenses, set budgets, get alerts and see monthly summaries.
Live at <https://accountant.appamora.com/>.

## Stack

- Laravel 13 (PHP 8.3) — API and public pages
- React + TypeScript + Vite + Tailwind — logged-in app
- MySQL / MariaDB
- Hosting: GoDaddy cPanel

## Local setup

```bash
composer install
npm install
cp .env.example .env      # then set DB_* values
php artisan key:generate
php artisan migrate --seed   # --seed adds demo data (local only)
composer run dev             # app at http://localhost:8000
```

Demo login (local only): `demo@example.com` / `password`.
Emails (verification, password reset, budget alerts) are written to `storage/logs/laravel.log` locally.

## Tests

```bash
php artisan test --compact   # uses the accountant_testing database
npx tsc --noEmit             # type-check the React app
```

## What is built

- Public pages: home, features, pricing, sitemap.xml, robots.txt
- Accounts: register, login, email verification, password reset
- Workspaces (personal / business) with roles: owner, admin, finance manager, contributor, viewer
- Financial accounts with live balances, categories, income / expense / transfer transactions
- Monthly budgets with one-time alerts at chosen thresholds (in-app + email)
- Dashboard: totals, previous-month comparison, 6-month trend, spending by category, top merchants
- Audit log of every change to accounts, categories, transactions and budgets
- API: `/api/v1/...` (Sanctum cookie auth, same origin)
