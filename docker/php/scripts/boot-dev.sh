#!/usr/bin/env bash
set -e

cd /var/www/html
if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

exec apache2-foreground
