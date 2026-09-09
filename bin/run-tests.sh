#!/usr/bin/env bash
# Run unit tests inside the local WordPress container (has PHP).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

docker compose exec -T wordpress bash -lc '
set -e
cd /var/www/html/wp-content/plugins/plug-one
if [ ! -f vendor/bin/phpunit ]; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/tmp --filename=composer
  php /tmp/composer install --no-interaction
fi
vendor/bin/phpunit
'
