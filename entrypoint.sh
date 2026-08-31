#!/bin/sh
set -e
cd /var/www/html/krayin

# ensure .env present (process env overrides via immutable Dotenv)
[ -f .env ] || cp .env.example .env
# force https URL base for app.url (proxy terminates TLS)
APP_BASE="${APP_URL:-https://poweredge.altlabdev.com}"
sed -i "s|^APP_URL=.*|APP_URL=$APP_BASE|" .env
if grep -q '^ASSET_URL=' .env; then
  sed -i "s|^ASSET_URL=.*|ASSET_URL=$APP_BASE|" .env
else
  echo "ASSET_URL=$APP_BASE" >> .env
fi

# wait for DB
i=0
until php artisan migrate:status >/dev/null 2>&1; do
  echo "waiting for database..."
  i=$((i+1))
  [ "$i" -ge 60 ] && break
  sleep 2
done

# install / migrate
if [ ! -f storage/app/.installed ]; then
  php artisan migrate --force
  php artisan db:seed --force || true
  touch storage/app/.installed
else
  php artisan migrate --force
fi

php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true

exec apache2-foreground
