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
php artisan migrate
composer run dev          # app at http://localhost:8000
```
