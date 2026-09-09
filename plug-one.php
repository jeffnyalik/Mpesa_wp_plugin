<?php
/**
 * Plugin Name: Plug One — M-Pesa for WooCommerce
 * Plugin URI: https://example.com/plug-one
 * Description: Accept Lipa Na M-Pesa payments in WooCommerce via Safaricom Daraja STK Push (Buy Goods / Paybill).
 * Version: 1.0.0
 * Author: Plug One
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plug-one
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 10.1
 *
 * Independent third-party plugin. Not affiliated with Safaricom PLC, M-Pesa, or Automattic.
 */

defined( 'ABSPATH' ) || exit;

define( 'PLUG_ONE_VERSION', '1.0.0' );
define( 'PLUG_ONE_FILE', __FILE__ );
define( 'PLUG_ONE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PLUG_ONE_URL', plugin_dir_url( __FILE__ ) );
define( 'PLUG_ONE_GATEWAY_ID', 'plug_one_mpesa' );

require_once PLUG_ONE_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Plug_One_Plugin', 'activate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PLUG_ONE_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PLUG_ONE_FILE, true );
		}
	}
);

add_action( 'plugins_loaded', array( 'Plug_One_Plugin', 'boot' ), 5 );
