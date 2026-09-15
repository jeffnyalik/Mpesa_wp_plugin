=== Plug One Payment Gateway for M-Pesa ===
Contributors: jeffnyake
Tags: woocommerce, m-pesa, kenya, payments, daraja
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0
WC tested up to: 10.1

WooCommerce payment gateway for M-Pesa. Free Manual Paybill/Till; optional Pro STK Push as a separate premium package.

== Description ==

**Plug One Payment Gateway for M-Pesa** lets Kenyan WooCommerce stores accept M-Pesa.

This is an independent third-party plugin. It is **not** affiliated with, endorsed by, or sponsored by Safaricom PLC or Automattic. “M-Pesa”, “Safaricom”, “Lipa Na M-Pesa”, and “Daraja” are trademarks of their respective owners.

= Free features (this WordPress.org plugin) =

* Manual Paybill / Till checkout
* Kenyan phone validation (07… / 2547…)
* Checkout Blocks + High-Performance Order Storage (HPOS)
* Transaction log helpers and admin order notes
* Docs under WooCommerce → Plug One Help

= Optional Pro package (distributed separately via Freemius) =

* Daraja STK Push PIN prompt
* Automatic order completion from Safaricom callbacks
* Resend STK / status query tools

The Pro package is a separate download. This free plugin does not lock or disable its own Manual features.

= Requirements =

* WordPress 6.0+ and WooCommerce 8.0+
* Store currency: Kenyan Shilling (KES)
* For Pro STK: Safaricom Daraja app credentials and public HTTPS

= Setup =

1. Install and activate WooCommerce, then this plugin.
2. WooCommerce → Settings → Payments → Plug One Payment Gateway for M-Pesa.
3. Enter your Paybill/Till and enable the gateway.
4. Optional: upgrade to the Pro package for STK Push.

== External services ==

This plugin can connect to third-party services:

= Safaricom Daraja (Pro / STK only) =

Used to authenticate (OAuth), start Lipa Na M-Pesa Online STK Push, and query payment status. Requests are sent when a store admin configures Daraja credentials and a customer pays with STK (Pro), or when Safaricom posts a payment callback to your site.

Data typically sent: merchant consumer key/secret (for OAuth), business shortcode, passkey-derived password, customer phone, amount, account reference, and CheckoutRequestID.

* Safaricom / M-Pesa developer terms: https://developer.safaricom.co.ke/docs
* Safaricom privacy: https://www.safaricom.co.ke/privacy-statement

= Freemius =

Used for optional license activation, account management, and delivery of the separate Pro package / updates. Data sent may include site URL, admin email, and license keys when you opt in or purchase.

* Freemius terms: https://freemius.com/terms/
* Freemius privacy: https://freemius.com/privacy/

== Installation ==

1. Install from Plugins → Add New, or upload to `/wp-content/plugins/`.
2. Activate the plugin.
3. Configure under WooCommerce → Settings → Payments.

== Frequently Asked Questions ==

= Is this an official Safaricom plugin? =

No. Use your own Paybill or Till. For Pro STK, create your own Daraja app.

= What is free vs Pro? =

This free plugin provides Manual Paybill/Till. STK Push ships in a separate Pro package via Freemius.

= Does it support Airtel or cards? =

Not in this version. It is M-Pesa (Safaricom) only.

== Changelog ==

= 1.5.0 =
* WordPress.org review: rename for trademark clarity; free package is Manual-only (no locked STK).
* Sanitize Daraja callback payloads; verify success with Daraja query before marking paid.
* Document Safaricom Daraja + Freemius external services; contributors jeffnyake (WordPress.org); Author Jeff Nyalik; production Composer deps only.
* Remove load_plugin_textdomain (WP.org auto-loads translations).

= 1.4.2 =
* WordPress.org: replace languages/.gitkeep with index.php (no hidden files).

= 1.4.1 =
* WordPress.org scan fixes: Tested up to 7.1, text domain alignment, languages folder.

= 1.4.0 =
* Freemium packaging for WordPress.org + Freemius Pro.

= 1.3.0 =
* Freemius official integration.

= 1.0.0 =
* Initial release.
