#!/usr/bin/env bash
# Build a Freemius-ready ZIP (folder slug must match Freemius free slug).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="plug-one-lipa-na-m-pesa"
OUT_DIR="${ROOT}/dist"
STAGE="${OUT_DIR}/${SLUG}"
ZIP="${OUT_DIR}/${SLUG}.zip"

cd "$ROOT"

if [[ ! -f vendor/autoload.php ]]; then
  echo "Installing Composer deps (no-dev)…"
  composer install --no-dev --optimize-autoloader --ignore-platform-reqs
fi

rm -rf "$OUT_DIR"
mkdir -p "$STAGE"

# Copy only shippable paths (avoid rsync/sandbox hangs).
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

# Never ship local overrides or hidden files (WordPress.org forbids them).
rm -f "$STAGE/config/licensing.php"
find "$STAGE" -name '.*' -not -path '*/vendor/*' -type f -delete 2>/dev/null || true
find "$STAGE" -name '.*' -not -path '*/vendor/*' -type d -empty -delete 2>/dev/null || true

(
  cd "$OUT_DIR"
  zip -rq "${SLUG}.zip" "$SLUG" -x '*/.*' '*/*/.*'
)

echo "OK: ${ZIP}"
echo "Upload this ZIP in Freemius → Deployment → Add New Version"
echo "Then set status to Released when ready."
