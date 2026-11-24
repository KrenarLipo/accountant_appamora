# Putting Accountant online (GoDaddy cPanel)

Target: `https://accountant.appamora.com` · database `app_accountant` · user `app_accountant_user`.

## 0. Build the package (on your Linux computer)

```bash
git pull
deploy/build-release.sh
```

This creates `../accountant-release-<date>-<commit>.zip` next to the project folder. It contains the app,
the PHP packages, the built website files and a `.env` with a new secret `APP_KEY`.
**Keep this zip private** — the `.env` inside it is the live site's configuration.

## 1. Database (once)

Already done if you imported `database/sql/accountant_demo.sql` in phpMyAdmin.
Then remove the demo login (phpMyAdmin → your database → SQL):

```sql
DELETE FROM users WHERE email = 'demo@example.com';
```

## 2. PHP settings (once)

cPanel → **Select PHP Version** (or **MultiPHP Manager**) for `accountant.appamora.com`:

- Version **8.3**.
- Extensions ticked: `pdo_mysql`, `mbstring`, `intl`, `zip`, `gd`, `fileinfo`, `xml`, `curl`, `openssl`, `tokenizer`, `ctype`.

## 3. Upload the app

1. cPanel → **File Manager** → open your home folder (`/home/<cpanel-user>/`, the level *above* `public_html`).
2. **Upload** the zip there, select it → **Extract**. You now have `/home/<cpanel-user>/accountant-app/`.
3. Delete the zip from the server after extracting.

## 4. Point the subdomain at the app's `public` folder

cPanel → **Domains** → `accountant.appamora.com` → **Manage** → set **Document Root** to:

```
accountant-app/public
```

(Old cPanel: **Subdomains** → pencil icon next to the document root.)

If cPanel does not allow that path, use the fallback: copy everything inside `accountant-app/public/`
into the subdomain's current folder, then replace that folder's `index.php` with
`accountant-app/deploy/public_html-index.php` and check the `$app` path at the top of it.

## 5. Fill in the secrets

File Manager → `accountant-app/.env` → **Edit** (enable "Show Hidden Files" in Settings if you can't see it):

- `DB_PASSWORD` — the database user's password.
- `MAIL_*` — your appamora.com mailbox (the file lists the right host for cPanel, GoDaddy and Microsoft 365 mail).
- `ANTHROPIC_API_KEY` — optional, for AI explanations.

## 6. HTTPS

cPanel → **SSL/TLS Status** → make sure `accountant.appamora.com` has a valid certificate (run **AutoSSL** if not).
The app redirects all traffic to HTTPS by itself.

## 7. Scheduled task (month-end alerts)

cPanel → **Cron Jobs** → Common setting **Once Per Minute** → command:

```
/usr/local/bin/php /home/<cpanel-user>/accountant-app/artisan schedule:run >> /dev/null 2>&1
```

If that PHP path is wrong, try `/opt/cpanel/ea-php83/root/usr/bin/php`.

## 8. Check

- `https://accountant.appamora.com` shows the homepage.
- Register a new account → the verification email arrives → the dashboard opens.
- If you see "500 Server Error": read `accountant-app/storage/logs/laravel-<date>.log` (usually a wrong DB password or a missing PHP extension).

## Updating later

1. `git pull` and `deploy/build-release.sh` on your computer.
2. Upload and extract the new zip **in a temporary folder**, then copy everything into `accountant-app/`
   **except** `.env` and `storage/` (those hold the live configuration and logs).
3. If the release adds database changes, they will be listed in the release notes as SQL to run in phpMyAdmin
   (or run `php artisan migrate --force` if your plan has Terminal/SSH).
