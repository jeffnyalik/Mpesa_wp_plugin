# Getting started

## Requirements

- WordPress 6.0+ and WooCommerce 8.0+
- Store currency **KES**
- HTTPS on production
- For STK Push: Safaricom Daraja app + Paybill or Till
- Pro license for STK Push (Manual Paybill/Till works on Free)

## Install

1. Upload the plugin folder to `wp-content/plugins/` and activate (or install a Freemius ZIP).
2. WooCommerce → Settings → Payments → **Plug One Payment Gateway for M-Pesa** → Enable.
3. For **Manual** (free): enter Paybill/Till (shortcode / Party B).
4. For **Pro STK**: choose Sandbox or Production, paste Consumer key, secret, passkey, shortcode.
5. Transaction type: **Paybill** for sandbox `174379`, or **Buy Goods** with a real Till.
6. Copy the callback URL (`/wc-api/plug_one_mpesa/`) into Daraja / ngrok override (Pro).
7. Click **Test Daraja credentials** (Pro).
8. Place a test order.

## Free vs Pro

| Feature | Free | Pro |
|---|---|---|
| Manual Paybill / Till at checkout | Yes | Yes |
| STK Push (PIN prompt) | No | Yes |
| Auto-complete from callback | — | Yes |
| Resend STK / Query status | Limited | Yes |

## After payment

Physical products move to **Processing** when paid. That means success. Mark **Completed** when you fulfill, or set products to Virtual to auto-complete.
