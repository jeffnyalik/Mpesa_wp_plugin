# Local WordPress testing

This repo includes a Docker stack so you can test Plug One against real WooCommerce without touching a live store.

## Prerequisites

- Docker + Docker Compose
- Optional (for live Safaricom callbacks): [ngrok](https://ngrok.com/) or Cloudflare Tunnel

## One-command setup

From this folder:

```bash
./bin/setup-local.sh
```

That will:

1. Start WordPress on **http://localhost:8080**
2. Install WordPress (admin / admin)
3. Install & activate WooCommerce
4. Activate **plug-one** (this folder is mounted into the container)
5. Set currency to **KES**
6. Create a **KES 10** sample product

## Manual commands

```bash
docker compose up -d
docker compose run --rm wpcli wp plugin list
docker compose logs -f wordpress
docker compose down          # stop
docker compose down -v       # stop and wipe DB
```

## Configure the gateway

1. Open http://localhost:8080/wp-admin (`admin` / `admin`)
2. WooCommerce → Settings → Payments → **Plug One M-Pesa**
3. Enable it, choose **Sandbox**
4. Paste Daraja sandbox credentials:
   - Consumer key / secret
   - Shortcode (sandbox: `174379` for the public test shortcode)
   - Passkey (sandbox passkey from Daraja docs / your app)
   - Transaction type: Buy Goods or Paybill
5. Click **Test Daraja credentials**



## sample test:
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=NBrlvMjX2rrDaICRC6tvsHhbXJpECWSOG7AmFUyPLctMu6uQ
MPESA_CONSUMER_SECRET=4pGwbpusF9oAhR9NUVG10Lmsmv1Z1xBh6mbJ6UGNheetTR2nQLNkUps5Y9ESrCPC
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
MPESA_SHORTCODE=174379
MPESA_PARTY_B=174379
MPESA_TRANSACTION_TYPE=CustomerPayBillOnline
# Ngrok tunnel → Nest. Path must be /Bsing/mpesa/callback (not mini-app).
MPESA_CALLBACK_URL=https://entire-matriarch-showy.ngrok-free.dev/Bsing/mpesa/callback
MPESA_VALIDATION_URL=
MPESA_CONFIRMATION_URL=
# Must be empty in sandbox so hosts default to sandbox.safaricom.co.ke
MPESA_ACCESS_TOKEN_URL=
MPESA_STK_URL=
MPESA_QUERY_URL=

## 

## M-Pesa callbacks on localhost

Safaricom cannot reach `http://localhost:8080`. For full STK → callback testing:

```bash
ngrok http 8080
```

Then either:

- Temporarily set WordPress site URL to the ngrok HTTPS URL, **or**
- Leave the site on localhost and paste the ngrok URL into **Callback URL override** in gateway settings:

```
https://YOUR-SUBDOMAIN.ngrok-free.app/wc-api/plug_one_mpesa/
https://entire-matriarch-showy.ngrok-free.dev/plug_one_mpesa/
```

Also register that same callback in the Daraja portal.

You can still test **OAuth + STK initiation** without a tunnel. The phone prompt will fire; only auto-complete needs the callback (or use **Query status** on the order).

## Useful URLs

| What | URL |
|---|---|
| Store | http://localhost:8080 |
| Admin | http://localhost:8080/wp-admin |
| Checkout | add the sample product → checkout |
| Logs | WooCommerce → Status → Logs → `plug-one-mpesa` |

## Notes

- Edits under this folder are live in the container (plugin path is bind-mounted).
- Pretty permalinks are enabled by the setup script (needed for `/wc-api/`).
- Default admin password is for local use only — change it if you expose the stack.
