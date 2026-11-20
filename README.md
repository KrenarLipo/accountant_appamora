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

## Scheduled tasks and AI

- Cron (every minute): `php artisan schedule:run` — runs the daily month-end category check at 07:00.
- AI explanations: set `ANTHROPIC_API_KEY` in `.env` (model: `ANTHROPIC_MODEL`, default `claude-opus-5-5`), then turn on "AI explanations" in the workspace Settings. Without a key, alerts still work with standard wording.

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
- Calendar: daily income, paid out, money earned back and profit/loss; add or edit any day
- Profit/loss per expense: "money earned back" on an expense (e.g. stock bought for 300, sold for 450 → +150)
- Normal monthly amount per category with an allowed ± %; alerts when a category goes too far over (immediately) or under (after the month ends)
- Optional AI explanations of those alerts by Claude (per-workspace opt-in; figures always calculated by the app)
- Excel import (template with your categories/accounts, row-by-row preview, duplicate and same-file protection) and Excel/CSV export with a summary sheet
- Audit log of every change to accounts, categories, transactions and budgets
- API: `/api/v1/...` (Sanctum cookie auth, same origin)
