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
  vendor
do
  if [[ -e "$ROOT/$path" ]]; then
    cp -a "$ROOT/$path" "$STAGE/"
  fi
done

# Never ship local overrides.
rm -f "$STAGE/config/licensing.php"

(
  cd "$OUT_DIR"
  zip -rq "${SLUG}.zip" "$SLUG"
)

echo "OK: ${ZIP}"
echo "Upload this ZIP in Freemius → Deployment → Add New Version"
echo "Then set status to Released when ready."
