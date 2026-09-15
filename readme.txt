=== Plug One Payment Gateway for M-Pesa ===
Contributors: jeffnyake
Tags: woocommerce, m-pesa, kenya, payments, daraja
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0
WC tested up to: 10.1

Open-source WooCommerce payment gateway for M-Pesa: Manual Paybill/Till and Daraja STK Push.

== Description ==

**Plug One Payment Gateway for M-Pesa** lets Kenyan WooCommerce stores accept M-Pesa via Manual Paybill/Till or Safaricom Daraja STK Push.

This is an independent third-party plugin. It is **not** affiliated with, endorsed by, or sponsored by Safaricom PLC or Automattic. “M-Pesa”, “Safaricom”, “Lipa Na M-Pesa”, and “Daraja” are trademarks of their respective owners.

Source: https://github.com/jeffnyalik/Mpesa_wp_plugin

= Features =

* Manual Paybill / Till checkout
* Daraja STK Push PIN prompt and automatic order completion from callbacks
* Resend STK / status query / sandbox simulate
* Kenyan phone validation
* Checkout Blocks + High-Performance Order Storage (HPOS)

= Requirements =

* WordPress 6.0+ and WooCommerce 8.0+
* Store currency: Kenyan Shilling (KES)
* For STK: Safaricom Daraja credentials and public HTTPS

== External services ==

= Safaricom Daraja =

Used to authenticate (OAuth), start Lipa Na M-Pesa Online STK Push, and query payment status when STK mode is enabled.

* Developer docs: https://developer.safaricom.co.ke/docs
* Privacy: https://www.safaricom.co.ke/privacy-statement

== Installation ==

1. Install from GitHub or upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate the plugin.
3. Configure under WooCommerce → Settings → Payments.

== Frequently Asked Questions ==

= Is this an official Safaricom plugin? =

No. Use your own Paybill or Till and your own Daraja app for STK.

= Does it support Airtel or cards? =

Not in this version. It is M-Pesa (Safaricom) only.

== Changelog ==

= 2.0.0 =
* Fully open source: removed Freemius licensing and Free/Pro gates. STK Push included for everyone.

= 1.5.1 =
* Distinct Plugin URI vs Author URI.

= 1.5.0 =
* WordPress.org review prep (later paused in favour of GitHub).

= 1.0.0 =
* Initial release.
