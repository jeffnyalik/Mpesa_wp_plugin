#!/usr/bin/env bash
# Install Composer deps (Freemius SDK) inside the WordPress container.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

docker compose exec -T wordpress bash -lc '
set -e
cd /var/www/html/wp-content/plugins/plug-one
if [ ! -f /tmp/composer ]; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/tmp --filename=composer
fi
php /tmp/composer install --no-interaction --prefer-dist
ls -la vendor/freemius 2>/dev/null || ls -la vendor | head
'
