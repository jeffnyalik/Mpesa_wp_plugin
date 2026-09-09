<?php
/**
 * Example Freemius / support config.
 *
 * Copy to config/licensing.php (gitignored) and fill in values from
 * https://dashboard.freemius.com/ after creating your product.
 *
 * Then install the SDK:
 *   composer require freemius/wordpress-sdk
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

// Freemius product (leave placeholders until you create the product).
define( 'PLUG_ONE_FS_ID', '0000' );
define( 'PLUG_ONE_FS_PUBLIC_KEY', 'pk_YOUR_PUBLIC_KEY' );

// Support / docs (shown in WooCommerce → Plug One Help).
define( 'PLUG_ONE_SUPPORT_EMAIL', 'support@yourdomain.com' );
define( 'PLUG_ONE_DOCS_URL', 'https://yourdomain.com/docs/plug-one/' ); // or leave empty to use in-plugin /docs
define( 'PLUG_ONE_PRICING_URL', 'https://yourdomain.com/pricing/' );
