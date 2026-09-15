#!/usr/bin/env bash
# Build a Freemius-ready ZIP (folder slug must match Freemius / WordPress.org free slug).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="plug-one-payment-gateway-m-pesa"
OUT_DIR="${ROOT}/dist"
STAGE="${OUT_DIR}/${SLUG}"
ZIP="${OUT_DIR}/${SLUG}.zip"

cd "$ROOT"

echo "Installing Composer production deps (no-dev)…"
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

rm -rf "$OUT_DIR"
mkdir -p "$STAGE"

for path in \
  plug-one.php \
  readme.txt \
  composer.json \
  assets \
  config \
  docs \
  includes \
  languages \
  vendor
do
  if [[ -e "$ROOT/$path" ]]; then
    cp -a "$ROOT/$path" "$STAGE/"
  fi
done

rm -f "$STAGE/config/licensing.php"
# Never ship PHPUnit / test tooling if somehow present.
rm -rf "$STAGE/vendor/phpunit" "$STAGE/vendor/nikic/php-parser" "$STAGE/vendor/sebastian" "$STAGE/vendor/phar-io" "$STAGE/vendor/theseer" "$STAGE/vendor/myclabs" 2>/dev/null || true

find "$STAGE" -name '.*' -not -path '*/vendor/*' -type f -delete 2>/dev/null || true
find "$STAGE" -name '.*' -not -path '*/vendor/*' -type d -empty -delete 2>/dev/null || true

(
  cd "$OUT_DIR"
  zip -rq "${SLUG}.zip" "$SLUG" -x '*/.*' '*/*/.*'
)

echo "OK: ${ZIP}"
echo "Upload this ZIP in Freemius → Deployment → Add New Version (then download Freemius FREE zip for wordpress.org)."
