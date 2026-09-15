<?php
/**
 * Plugin Name: Plug One Payment Gateway for M-Pesa
 * Plugin URI: https://github.com/jeffnyalik/Mpesa_wp_plugin
 * Description: WooCommerce payment gateway for M-Pesa — Manual Paybill/Till and Daraja STK Push (open source).
 * Version: 2.0.0
 * Author: Jeff Nyalik
 * Author URI: https://github.com/jeffnyalik
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plug-one-payment-gateway-m-pesa
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 10.1
 *
 * Independent third-party plugin. Not affiliated with, endorsed by, or sponsored by Safaricom PLC or Automattic.
 * “M-Pesa”, “Safaricom”, and “Daraja” are trademarks of their respective owners.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PLUG_ONE_VERSION', '2.0.0' );
define( 'PLUG_ONE_FILE', __FILE__ );
define( 'PLUG_ONE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PLUG_ONE_URL', plugin_dir_url( __FILE__ ) );
define( 'PLUG_ONE_GATEWAY_ID', 'plug_one_mpesa' );

require_once PLUG_ONE_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Plug_One_Plugin', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'Plug_One_Plugin', 'uninstall' ) );

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
