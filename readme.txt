=== Plug One — M-Pesa for WooCommerce ===
Contributors: plugone
Tags: woocommerce, m-pesa, kenya, payments, daraja
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0
WC tested up to: 10.1

Accept Lipa Na M-Pesa in WooCommerce via Safaricom Daraja STK Push (Buy Goods or Paybill).

== Description ==

Plug One is a WooCommerce payment gateway for Kenyan stores. Customers enter their Safaricom number at checkout, receive an STK Push PIN prompt, and the order is marked paid when Daraja confirms the payment.

This plugin is an independent product. It is not affiliated with, endorsed by, or sponsored by Safaricom PLC or Automattic. “M-Pesa”, “Safaricom”, and “Daraja” are trademarks of Safaricom PLC.

= What it does =

* STK Push (Lipa Na M-Pesa Online) for Buy Goods / Till or Paybill
* Automatic order completion from the Safaricom callback
* STK status query if the callback is delayed or missed (cancel, timeout, or success)
* Thank-you page polling so the customer sees payment succeed
* Checkout Blocks + High-Performance Order Storage (HPOS)
* Manual Paybill / Till mode (no Daraja app required)
* Admin tools: resend STK, query status, test OAuth credentials
* Transaction log table for support

= Requirements =

* WordPress 6.0+ and WooCommerce 8.0+
* Store currency: Kenyan Shilling (KES)
* For STK Push: Safaricom Daraja app, Consumer Key / Secret, passkey, Paybill or Till
* Public HTTPS site in production (Safaricom rejects HTTP callbacks)

= Setup =

1. Install and activate WooCommerce, then this plugin.
2. WooCommerce → Settings → Payments → Plug One M-Pesa.
3. Choose Sandbox or Production and paste Daraja credentials.
4. Set transaction type (Buy Goods or Paybill) and Party B (Till) if it differs from the shortcode.
5. Copy the callback URL into your Daraja app.
6. Use **Test Daraja credentials**, then place a test order.

== Installation ==

1. Upload the `plug-one` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Configure it under WooCommerce → Settings → Payments.

== Frequently Asked Questions ==

= Is this the official Safaricom plugin? =

No. You must create your own Daraja app and use your own Paybill or Till number.

= Why did the customer pay but the order is still pending? =

Callbacks must be reachable on HTTPS. Use Query status on the order, or wait for the 70-second status query. Check WooCommerce → Status → Logs (`plug-one-mpesa`).

= Does it support Airtel or cards? =

Not in this version. It is M-Pesa (Safaricom) only.

== Changelog ==

= 1.0.0 =
* Initial release: STK Push, callbacks, status query, Blocks, HPOS.
