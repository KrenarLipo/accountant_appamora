#!/usr/bin/env bash
# Builds a ready-to-upload zip of the committed code for GoDaddy cPanel.
# Usage: deploy/build-release.sh            (run from the project root; commit first)
# Output: ../accountant-release-<date>-<commit>.zip  (outside the repository)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [ -n "$(git status --porcelain)" ]; then
    echo "Commit or stash your changes first: the release is built from the last commit." >&2
    exit 1
fi

COMMIT="$(git rev-parse --short HEAD)"
OUT="$(dirname "$ROOT")/accountant-release-$(date +%Y%m%d)-$COMMIT.zip"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
APP="$WORK/accountant-app"

echo "→ Exporting commit $COMMIT"
mkdir -p "$APP"
git archive HEAD | tar -x -C "$APP"

cd "$APP"
echo "→ Installing PHP packages (production only)"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet

echo "→ Building the frontend"
npm ci --no-audit --no-fund --silent
npm run build >/dev/null

echo "→ Writing .env with a new APP_KEY"
cp deploy/env.production.example .env
KEY="$(php artisan key:generate --show --no-interaction)"
sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" .env

echo "→ Removing development-only files"
rm -rf node_modules tests .claude .ai .editorconfig .npmrc AGENTS.md CLAUDE.md boost.json phpunit.xml \
       public/hot storage/logs/*.log bootstrap/cache/*.php
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/private bootstrap/cache

echo "→ Zipping"
cd "$WORK"
rm -f "$OUT"
zip -qr "$OUT" accountant-app
echo "✓ Release ready: $OUT ($(du -h "$OUT" | cut -f1))"
