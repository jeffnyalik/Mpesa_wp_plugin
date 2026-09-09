#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SITE_URL="${SITE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"

echo "==> Starting WordPress + MySQL…"
docker compose up -d db wordpress

echo "==> Waiting for WordPress…"
for i in $(seq 1 60); do
  if curl -fsS "$SITE_URL" >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

wp() {
  docker compose run --rm wpcli wp "$@"
}

if ! wp core is-installed >/dev/null 2>&1; then
  echo "==> Installing WordPress…"
  wp core install \
    --url="$SITE_URL" \
    --title="Plug One Dev" \
    --admin_user="$ADMIN_USER" \
    --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" \
    --skip-email

  echo "==> Configuring store…"
  wp rewrite structure '/%postname%/' --hard
  wp option update blogdescription 'Local M-Pesa gateway testing'
  wp option update woocommerce_default_country 'KE'
  wp option update woocommerce_currency 'KES' || true
fi

echo "==> Installing WooCommerce…"
wp plugin install woocommerce --activate || true
wp plugin activate plug-one || true

# WooCommerce often needs a second pass for currency after install.
wp option update woocommerce_currency 'KES' || true
wp option update woocommerce_default_country 'KE:KE' || true
wp option update woocommerce_allowed_countries 'specific' || true
wp option update woocommerce_specific_allowed_countries --format=json '["KE"]' || true

# Skip setup wizard noise for local testing.
wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json || true
wp option update woocommerce_task_list_hidden 'yes' || true

if ! wp post list --post_type=product --field=ID | grep -q .; then
  echo "==> Creating a sample product…"
  PRODUCT_ID="$(wp wc product create --user=1 --name='Test Item' --type=simple --regular_price=10 --status=publish --porcelain)"
  echo "    Product ID: $PRODUCT_ID"
fi

echo
echo "Ready."
echo "  Site:     $SITE_URL"
echo "  Admin:    $SITE_URL/wp-admin  ($ADMIN_USER / $ADMIN_PASS)"
echo "  Payments: WooCommerce → Settings → Payments → Plug One M-Pesa"
echo
echo "For Safaricom callbacks from outside, expose the site:"
echo "  ngrok http 8080"
echo "  then set WP site URL / callback override to the https://….ngrok URL"
echo
