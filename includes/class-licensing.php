<?php
/**
 * Licensing helpers on top of Freemius (`polnmp_fs`).
 *
 * WordPress.org free build: Manual Paybill/Till only (no locked features in this package).
 * Pro STK ships in the separate Freemius premium package.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Licensing {

	/**
	 * @var bool
	 */
	protected static $booted = false;

	public static function init() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		self::maybe_load_config();

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Whether the premium package may run STK (premium build + valid license / trial).
	 * Not used to lock features in the WordPress.org free package (STK code is absent there).
	 *
	 * @return bool
	 */
	public static function can_use_pro() {
		if ( apply_filters( 'plug_one_force_pro', false ) ) {
			return true;
		}

		$fs = self::fs();
		if ( ! $fs ) {
			return (bool) apply_filters( 'plug_one_pro_unlocked_without_sdk', false );
		}

		if ( is_object( $fs ) && method_exists( $fs, 'can_use_premium_code' ) ) {
			return (bool) $fs->can_use_premium_code();
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function is_freemius_configured() {
		return (bool) self::fs();
	}

	/**
	 * @return Freemius|false|null
	 */
	public static function fs() {
		if ( function_exists( 'polnmp_fs' ) ) {
			return polnmp_fs();
		}
		return null;
	}

	/**
	 * @return string
	 */
	public static function account_url() {
		$fs = self::fs();
		if ( $fs && method_exists( $fs, 'get_account_url' ) ) {
			return (string) $fs->get_account_url();
		}
		return '';
	}

	/**
	 * @return string
	 */
	public static function pricing_url() {
		$fs = self::fs();
		if ( $fs && method_exists( $fs, 'get_upgrade_url' ) ) {
			return (string) $fs->get_upgrade_url();
		}
		$url = self::config( 'pricing_url', '' );
		return is_string( $url ) ? $url : '';
	}

	public static function admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Premium package only: nudge to activate license (STK code exists only there).
		if ( function_exists( 'polnmp_fs' ) && is_object( polnmp_fs() ) && polnmp_fs()->is__premium_only() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && ( false !== strpos( (string) $screen->id, 'woocommerce' ) || 'plugins' === $screen->id ) ) {
				if ( self::is_freemius_configured() && ! self::can_use_pro() ) {
					$url = self::pricing_url() ? self::pricing_url() : admin_url( 'admin.php?page=plug-one-support' );
					echo '<div class="notice notice-warning"><p>';
					echo esc_html__( 'Plug One Pro: activate your license to enable STK Push.', 'plug-one-payment-gateway-m-pesa' );
					echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Activate or upgrade', 'plug-one-payment-gateway-m-pesa' ) . '</a>';
					echo '</p></div>';
				}
			}
		}
	}

	protected static function maybe_load_config() {
		$file = PLUG_ONE_PATH . 'config/licensing.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * @param string $key     Config key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function config( $key, $default = '' ) {
		$map = array(
			'support_email' => defined( 'PLUG_ONE_SUPPORT_EMAIL' ) ? PLUG_ONE_SUPPORT_EMAIL : 'jeffnyak@gmail.com',
			'support_phone' => defined( 'PLUG_ONE_SUPPORT_PHONE' ) ? PLUG_ONE_SUPPORT_PHONE : '0716431039',
			'docs_url'      => defined( 'PLUG_ONE_DOCS_URL' ) ? PLUG_ONE_DOCS_URL : '',
			'pricing_url'   => defined( 'PLUG_ONE_PRICING_URL' ) ? PLUG_ONE_PRICING_URL : '',
		);
		return isset( $map[ $key ] ) && '' !== $map[ $key ] && null !== $map[ $key ] ? $map[ $key ] : $default;
	}
}
