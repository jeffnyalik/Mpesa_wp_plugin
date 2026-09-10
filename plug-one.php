<?php
/**
 * Plugin Name: Plug One — M-Pesa for WooCommerce
 * Plugin URI: https://github.com/jeffnyalik/Mpesa_wp_plugin
 * Description: Accept Lipa Na M-Pesa in WooCommerce (Manual Paybill/Till free; STK Push with Pro).
 * Version: 1.4.0
 * Author: Plug One
 * Author URI: https://checkout.freemius.com/plugin/39188/plan/65728/
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
 *
 * @fs_ignore /vendor/
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
		 * Freemius SDK accessor (Plug One Lipa Na M-Pesa).
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
						'slug'                => 'plug-one-lipa-na-m-pesa',
						'premium_slug'        => 'plug-one-lipa-na-m-pesa-premium',
						'type'                => 'plugin',
						'public_key'          => 'pk_852cd4cf63b0100e24aa0ec63d65c',
						'is_premium'          => true,
						'premium_suffix'      => 'Pro',
						'has_premium_version' => true,
						'has_addons'          => false,
						'has_paid_plans'      => true,
						// Free ZIP from Freemius is WordPress.org–safe (premium_only STK stripped).
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

	define( 'PLUG_ONE_VERSION', '1.4.0' );
	define( 'PLUG_ONE_FILE', __FILE__ );
	define( 'PLUG_ONE_PATH', plugin_dir_path( __FILE__ ) );
	define( 'PLUG_ONE_URL', plugin_dir_url( __FILE__ ) );
	define( 'PLUG_ONE_GATEWAY_ID', 'plug_one_mpesa' );

	require_once PLUG_ONE_PATH . 'includes/class-plugin.php';

	register_activation_hook( __FILE__, array( 'Plug_One_Plugin', 'activate' ) );

	// Freemius forbids uninstall.php; cleanup runs after Freemius reports uninstall.
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
