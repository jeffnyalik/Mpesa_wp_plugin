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

```bash
docker compose up -d
docker compose run --rm wpcli wp plugin list
docker compose down          # stop
docker compose down -v       # wipe DB
```

## Configure the gateway

1. WooCommerce → Settings → Payments → **Plug One M-Pesa**
2. Enable · Environment **Sandbox** · Transaction type **Paybill**
3. Paste your own Daraja sandbox credentials (Consumer key/secret, passkey)
4. Shortcode / Party B: `174379` (public Safaricom sandbox Paybill)
5. Callback override (with ngrok):

```text
https://YOUR-SUBDOMAIN.ngrok-free.app/wc-api/plug_one_mpesa/
```

6. **Test Daraja credentials**, then checkout a product

Do **not** commit real keys. Use environment variables or the WP admin UI only.

### Example sandbox field mapping (values from your Daraja app)

| Gateway field | Typical sandbox |
|---|---|
| Environment | Sandbox |
| Transaction type | Paybill (`CustomerPayBillOnline`) |
| Shortcode | `174379` |
| Party B | `174379` |
| Callback | `https://…/wc-api/plug_one_mpesa/` |

Till / Buy Goods needs a real Till after go-live — there is no public Till STK sandbox like `174379`.

## Callbacks

```bash
ngrok http 8080
```

Path must include **`/wc-api/`**. Nest’s `/Bsing/mpesa/callback` is a different app.

Paid physical products end as **Processing** (normal WooCommerce). That means payment succeeded.

## Licensing (Freemius)

Until you add Freemius credentials, **Pro stays unlocked** for local testing.

1. Copy `config/licensing.example.php` → `config/licensing.php`
2. Create a product in the [Freemius dashboard](https://dashboard.freemius.com/)
3. `composer require freemius/wordpress-sdk`
4. See `docs/licensing.md`

## Docs & support in WP

**WooCommerce → Plug One Help**

Markdown docs live in `/docs` (publish to your site and set `PLUG_ONE_DOCS_URL`).

## Production Till

Follow `docs/production-till.md` when you have a real Till (not sandbox `174379`).


## Useful URLs

| What | URL |
|---|---|
| Store | http://localhost:8080 |
| Admin | http://localhost:8080/wp-admin |
| Logs | WooCommerce → Status → Logs → `plug-one-mpesa` |
