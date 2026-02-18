#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache \
  /var/www/shared/public

chown -R www-data:www-data storage bootstrap/cache /var/www/shared || true
chmod -R ug+rwx storage bootstrap/cache || true

if [ ! -L public/storage ]; then
  rm -rf public/storage
  ln -s /var/www/html/storage/app/public public/storage
fi

if [ -d /var/www/shared/public ]; then
  rm -rf /var/www/shared/public/*
  cp -a /var/www/html/public/. /var/www/shared/public/
  printf "ok\n" > /var/www/shared/public/healthz.txt
fi

exec "$@"
