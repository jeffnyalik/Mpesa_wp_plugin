# Getting started

## Requirements

- WordPress 6.0+ and WooCommerce 8.0+
- Store currency **KES**
- HTTPS on production
- For STK Push: Safaricom Daraja app + Paybill or Till

## Install

1. Upload the plugin folder to `wp-content/plugins/` and activate.
2. WooCommerce → Settings → Payments → **Plug One Payment Gateway for M-Pesa** → Enable.
3. Choose **STK Push** or **Manual**.
4. For STK: Sandbox or Production, paste Consumer key, secret, passkey, shortcode.
5. Transaction type: **Paybill** for sandbox `174379`, or **Buy Goods** with a real Till.
6. Copy the callback URL (`/wc-api/plug_one_mpesa/`) into Daraja / ngrok override (STK).
7. Click **Test Daraja credentials** (STK).
8. Place a test order.

## Modes

| Feature | Manual | STK |
|---|---|---|
| Paybill/Till shown at checkout | Yes | Optional |
| PIN prompt on phone | No | Yes |
| Auto-complete from callback | No (mark paid in admin) | Yes |
| Resend STK / Query status | — | Yes |
