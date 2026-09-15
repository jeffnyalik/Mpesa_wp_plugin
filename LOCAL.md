# Local WordPress testing

Docker stack for testing Plug One against WooCommerce.

## Prerequisites

- Docker + Docker Compose
- Optional (Safaricom callbacks): [ngrok](https://ngrok.com/)

## Setup

```bash
./bin/setup-local.sh
```

Opens **http://localhost:8080** (admin / admin), installs WooCommerce, activates this plugin, sets **KES**.

PHP upload limit is raised via `docker/uploads.ini` (64M) for plugin ZIP uploads.

```bash
docker compose up -d
docker compose run --rm wpcli wp plugin list
docker compose down          # stop
docker compose down -v       # wipe DB
```

## Configure the gateway

1. WooCommerce → Settings → Payments → **Plug One Payment Gateway for M-Pesa**
2. Enable · Environment **Sandbox** · Mode **STK Push** or **Manual**
3. Paste Daraja sandbox credentials for STK
4. Shortcode / Party B: `174379` (public Safaricom sandbox Paybill)
5. Callback override (with ngrok):

```text
https://YOUR-SUBDOMAIN.ngrok-free.app/wc-api/plug_one_mpesa/
```

6. **Test Daraja credentials**, then checkout a product

Do **not** commit real keys.

## Callbacks

```bash
ngrok http 8080
```

Path must include **`/wc-api/`**.

## Docs & support in WP

**WooCommerce → Plug One Help**
