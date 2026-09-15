<?php
/**
 * Plugin Name: Plug One Payment Gateway for M-Pesa
 * Plugin URI: https://github.com/jeffnyalik/Mpesa_wp_plugin
 * Description: WooCommerce payment gateway for M-Pesa — free Manual Paybill/Till; optional Pro STK Push via separate premium build.
 * Version: 1.5.1
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
 *
 * @fs_ignore /vendor/
 * @fs_premium_only /includes/class-daraja-client.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'polnmp_fs' ) ) {
	$polnmp_fs = polnmp_fs();
	if ( is_object( $polnmp_fs ) && method_exists( $polnmp_fs, 'set_basename' ) ) {
		$polnmp_fs->set_basename( true, __FILE__ );
	}
} else {
	/**
	 * DO NOT REMOVE THIS IF — required for Freemius free/premium basename handling.
	 */
	if ( ! function_exists( 'polnmp_fs' ) ) {
		/**
		 * Freemius SDK accessor.
		 *
		 * @return Freemius|false
		 */
		function polnmp_fs() {
			global $polnmp_fs;

			if ( ! isset( $polnmp_fs ) ) {
				$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
				if ( file_exists( $autoload ) ) {
					require_once $autoload;
				}

				if ( ! function_exists( 'fs_dynamic_init' ) ) {
					$polnmp_fs = false;
					return $polnmp_fs;
				}

				$polnmp_fs = fs_dynamic_init(
					array(
						'id'                  => '39188',
						'slug'                => 'plug-one-payment-gateway-m-pesa',
						'premium_slug'        => 'plug-one-payment-gateway-m-pesa-premium',
						'type'                => 'plugin',
						'public_key'          => 'pk_852cd4cf63b0100e24aa0ec63d65c',
						// Source tree is the premium codebase; Freemius free ZIP rewrites this to false.
						'is_premium'          => true,
						'premium_suffix'      => 'Pro',
						'has_premium_version' => true,
						'has_addons'          => false,
						'has_paid_plans'      => true,
						'is_org_compliant'    => true,
						'menu'                => array(
							'slug'    => 'plug-one-support',
							'parent'  => array(
								'slug' => 'woocommerce',
							),
							'account' => true,
							'contact' => true,
							'support' => false,
							'pricing' => true,
						),
					)
				);
			}

			return $polnmp_fs;
		}

		polnmp_fs();
		do_action( 'polnmp_fs_loaded' );
	}

	define( 'PLUG_ONE_VERSION', '1.5.1' );
	define( 'PLUG_ONE_FILE', __FILE__ );
	define( 'PLUG_ONE_PATH', plugin_dir_path( __FILE__ ) );
	define( 'PLUG_ONE_URL', plugin_dir_url( __FILE__ ) );
	define( 'PLUG_ONE_GATEWAY_ID', 'plug_one_mpesa' );

	require_once PLUG_ONE_PATH . 'includes/class-plugin.php';

	register_activation_hook( __FILE__, array( 'Plug_One_Plugin', 'activate' ) );

	$polnmp_fs_instance = polnmp_fs();
	if ( is_object( $polnmp_fs_instance ) && method_exists( $polnmp_fs_instance, 'add_action' ) ) {
		$polnmp_fs_instance->add_action( 'after_uninstall', array( 'Plug_One_Plugin', 'uninstall' ) );
	}

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
}
