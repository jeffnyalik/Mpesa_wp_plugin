#!/usr/bin/env bash
# Build a release ZIP for GitHub / manual install.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="plug-one-payment-gateway-m-pesa"
OUT_DIR="${ROOT}/dist"
STAGE="${OUT_DIR}/${SLUG}"
ZIP="${OUT_DIR}/${SLUG}.zip"

cd "$ROOT"

rm -rf "$OUT_DIR"
mkdir -p "$STAGE"

for path in \
  plug-one.php \
  readme.txt \
  README.md \
  LICENSE \
  composer.json \
  assets \
  config \
  docs \
  includes \
  languages
do
  if [[ -e "$ROOT/$path" ]]; then
    cp -a "$ROOT/$path" "$STAGE/"
  fi
done

rm -f "$STAGE/config/licensing.php"
find "$STAGE" -name '.*' -type f -delete 2>/dev/null || true

(
  cd "$OUT_DIR"
  zip -rq "${SLUG}.zip" "$SLUG" -x '*/.*'
)

echo "OK: ${ZIP}"
echo "Install by unzipping into wp-content/plugins/"
