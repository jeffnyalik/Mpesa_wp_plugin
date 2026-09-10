=== Plug One Lipa Na M-Pesa ===
Contributors: plugone
Tags: woocommerce, m-pesa, kenya, payments, daraja
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0
WC tested up to: 10.1

Accept Lipa Na M-Pesa in WooCommerce. Free Manual Paybill/Till; optional Pro STK Push via Freemius.

== Description ==

Plug One Lipa Na M-Pesa is a WooCommerce payment gateway for Kenyan stores using Safaricom M-Pesa.

**Free:** Manual Paybill / Till — show your number at checkout and confirm payment in admin.

**Pro (optional upgrade):** Daraja STK Push PIN prompt, automatic order completion from Safaricom callbacks, and Resend STK.

This plugin is an independent product. It is not affiliated with, endorsed by, or sponsored by Safaricom PLC or Automattic. “M-Pesa”, “Safaricom”, and “Daraja” are trademarks of Safaricom PLC.

= Free features =

* Manual Paybill / Till checkout (no Daraja app required)
* Kenyan phone validation (07… / 2547…)
* Checkout Blocks + High-Performance Order Storage (HPOS)
* Transaction log table and admin order tools (query / simulate in sandbox)
* In-plugin docs & support under WooCommerce → Plug One Help

[//]: # fs_premium_only_begin
= Pro features =

* STK Push (Lipa Na M-Pesa Online) for Buy Goods / Till or Paybill
* Automatic order completion from the Safaricom callback (ACK-first)
* Idempotent STK initiation and callback handling
* STK status query if the callback is delayed
* Thank-you page polling
* Resend STK from the order screen
[//]: # fs_premium_only_end

= Requirements =

* WordPress 6.0+ and WooCommerce 8.0+
* Store currency: Kenyan Shilling (KES)
* For Pro STK Push: Safaricom Daraja app, Consumer Key / Secret, passkey, Paybill or Till
* Public HTTPS site in production (Safaricom rejects HTTP callbacks)

= Setup =

1. Install and activate WooCommerce, then this plugin.
2. WooCommerce → Settings → Payments → Plug One M-Pesa.
3. Set **Payment mode** to Manual (free) or STK Push (Pro).
4. Enter your Paybill/Till (and Daraja credentials for Pro).
5. For Pro, copy the callback URL (`/wc-api/plug_one_mpesa/`) into your Daraja app.
6. Place a test order.

== Installation ==

1. Install from Plugins → Add New, or upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Configure under WooCommerce → Settings → Payments → Plug One M-Pesa.
4. Optional: upgrade to Pro from the Freemius account / pricing screen for STK Push.

== Frequently Asked Questions ==

= Is this the official Safaricom plugin? =

No. You must use your own Paybill or Till. For Pro STK, create your own Daraja app.

= What is free vs Pro? =

Free is Manual Paybill/Till. Pro adds STK Push and automatic payment confirmation via Daraja.

= Why is my paid order “Processing”? =

That is WooCommerce’s normal paid status for physical products. Payment succeeded. Use Completed when you fulfill the order, or mark products Virtual to auto-complete.

= Why did the customer pay but the order is still pending? =

For Pro STK, callbacks must be reachable on HTTPS at `/wc-api/plug_one_mpesa/`. Use Query status on the order, or wait for the status query. Check WooCommerce → Status → Logs (`plug-one-mpesa`).

= Does it support Airtel or cards? =

Not in this version. It is M-Pesa (Safaricom) only.

== Changelog ==

= 1.4.2 =
* WordPress.org: replace languages/.gitkeep with index.php (no hidden files).

= 1.4.1 =
* WordPress.org scan fixes: Tested up to 7.1, text domain/slug `plug-one-lipa-na-m-pesa`, languages folder.

= 1.4.0 =
* WordPress.org–ready freemium: `is_org_compliant`, STK paths marked Freemius `__premium_only`.
* Free default payment mode is Manual; Pro unlock no longer defaults on when SDK is missing.
* Docs checklist for publishing free on WordPress.org + Pro via Freemius.

= 1.3.0 =
* Freemius official integration (`polnmp_fs`, product 39188).
* Composer dependency: freemius/wordpress-sdk.
* Pro gate uses Freemius `can_use_premium_code()`; secret key via wp-config only.

= 1.2.0 =
* Freemius-ready licensing (Free manual / Pro STK) with config/licensing.example.php.
* WooCommerce → Plug One Help (docs + support email).
* Public docs: getting started, callbacks, Till checklist, licensing, troubleshooting.
* Secrets hardened: licensing.php gitignored; LOCAL.md has no live keys.

= 1.1.0 =
* Idempotent STK push and callback processing (transient locks + receipt guard).
* Callback ACK-first (Action Scheduler or shutdown) to stop Safaricom retry loops.
* Default transaction type: Paybill (sandbox-friendly).
* Blocks checkout reads `payment_data` phone field.
* Unit tests for phone, Daraja password, callback parse, idempotency.

= 1.0.0 =
* Initial release: STK Push, callbacks, status query, Blocks, HPOS.
