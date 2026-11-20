# accountant_appamora

**Accountant by AppAmora** — a calculation for daily expenses in small businesses and individuals.

Track income and expenses, set budgets, get alerts and see monthly summaries.
Live at <https://accountant.appamora.com/>.

## Stack

- Laravel 13 (PHP 8.3) — API and public pages
- React + TypeScript + Vite + Tailwind — logged-in app
- MySQL / MariaDB
- Hosting: GoDaddy cPanel

## Setting up on a Linux computer

Tested on Ubuntu/Debian. You need PHP 8.3, Composer, Node.js 20+ and MariaDB or MySQL.

```bash
# 1. System packages (Ubuntu/Debian)
sudo apt install php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
                 php8.3-zip php8.3-gd php8.3-intl mariadb-server git unzip
# Composer: https://getcomposer.org/download/   Node.js 20+: https://nodejs.org or nvm

# 2. Get the code
git clone https://github.com/KrenarLipo/accountant_appamora.git
cd accountant_appamora
composer install
npm install

# 3. Create the local databases (choose your own password)
sudo mysql -e "
CREATE DATABASE accountant_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE accountant_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'accountant'@'localhost' IDENTIFIED BY 'your-local-password';
GRANT ALL ON accountant_local.* TO 'accountant'@'localhost';
GRANT ALL ON accountant_testing.* TO 'accountant'@'localhost';"

# 4. Configuration (.env is never committed)
cp .env.example .env
#    edit .env: DB_PASSWORD="your-local-password"
php artisan key:generate

# 5. Load the data — pick ONE:
mysql -u accountant -p accountant_local < database/sql/accountant_demo.sql   # same demo data as before
# or: php artisan migrate --seed                                             # fresh, randomly generated demo data

# 6. Run it
npm run build
composer run dev             # http://localhost:8000
```

Demo login (local only): `demo@example.com` / `password`.
Emails (verification, password reset, alerts) are written to `storage/logs/laravel.log` locally.

After pulling new changes later: `composer install && npm install && php artisan migrate && npm run build`.

## Database file for GoDaddy

`database/sql/accountant_demo.sql` holds the full table structure plus the demo data. Import it with phpMyAdmin
(select the database → Import). Remove or change the demo user before going public. This repository is public:
never commit real passwords or customer data to that file.

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
