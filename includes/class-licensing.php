<?php
/**
 * Licensing + Freemius bootstrap.
 *
 * Free: Manual Paybill/Till.
 * Pro: STK Push, status query automation, admin resend/query helpers.
 *
 * Copy config/licensing.example.php → config/licensing.php (gitignored) after
 * creating a Freemius product, then: composer require freemius/wordpress-sdk
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Licensing {

	/**
	 * @var object|null Freemius instance.
	 */
	protected static $fs = null;

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
		self::maybe_boot_freemius();

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Whether STK Push and Pro admin tools are allowed.
	 *
	 * @return bool
	 */
	public static function can_use_pro() {
		/**
		 * Force Pro unlock (local QA). Example: add_filter( 'plug_one_force_pro', '__return_true' );
		 *
		 * @param bool $force Default false.
		 */
		if ( apply_filters( 'plug_one_force_pro', false ) ) {
			return true;
		}

		// No Freemius product configured yet → unlock for development / soft launch.
		if ( ! self::is_freemius_configured() ) {
			return (bool) apply_filters( 'plug_one_pro_unlocked_without_license', true );
		}

		if ( self::$fs && is_object( self::$fs ) && method_exists( self::$fs, 'can_use_premium_code' ) ) {
			return (bool) self::$fs->can_use_premium_code();
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function is_freemius_configured() {
		$id  = self::config( 'id' );
		$key = self::config( 'public_key' );
		if ( ! $id || ! $key ) {
			return false;
		}
		if ( '0000' === (string) $id || 0 === strpos( (string) $key, 'pk_YOUR' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @return object|null
	 */
	public static function fs() {
		return self::$fs;
	}

	/**
	 * Account / pricing URL when Freemius is live.
	 *
	 * @return string
	 */
	public static function account_url() {
		if ( self::$fs && method_exists( self::$fs, 'get_account_url' ) ) {
			return (string) self::$fs->get_account_url();
		}
		return '';
	}

	/**
	 * Upgrade / pricing URL.
	 *
	 * @return string
	 */
	public static function pricing_url() {
		if ( self::$fs && method_exists( self::$fs, 'get_upgrade_url' ) ) {
			return (string) self::$fs->get_upgrade_url();
		}
		$docs = self::config( 'pricing_url', '' );
		return is_string( $docs ) ? $docs : '';
	}

	public static function admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( false === strpos( (string) $screen->id, 'woocommerce' ) && 'plugins' !== $screen->id ) ) {
			return;
		}

		if ( self::is_freemius_configured() && ! self::can_use_pro() ) {
			$url = self::pricing_url() ? self::pricing_url() : admin_url( 'admin.php?page=plug-one-support' );
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'Plug One: STK Push requires an active Pro license. Manual Paybill/Till remains available.', 'plug-one' );
			echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Activate or upgrade', 'plug-one' ) . '</a>';
			echo '</p></div>';
		}
	}

	protected static function maybe_load_config() {
		$file = PLUG_ONE_PATH . 'config/licensing.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}

	protected static function maybe_boot_freemius() {
		if ( ! self::is_freemius_configured() ) {
			return;
		}

		$autoload = PLUG_ONE_PATH . 'vendor/autoload.php';
		$start    = PLUG_ONE_PATH . 'vendor/freemius/wordpress-sdk/start.php';
		$legacy   = PLUG_ONE_PATH . 'includes/sdk/freemius/start.php';

		if ( is_readable( $autoload ) ) {
			require_once $autoload;
		} elseif ( is_readable( $start ) ) {
			require_once $start;
		} elseif ( is_readable( $legacy ) ) {
			require_once $legacy;
		}

		if ( ! function_exists( 'fs_dynamic_init' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( ! current_user_can( 'manage_options' ) ) {
						return;
					}
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'Plug One: Freemius credentials are set but the SDK is missing. Run: composer require freemius/wordpress-sdk', 'plug-one' );
					echo '</p></div>';
				}
			);
			return;
		}

		if ( ! function_exists( 'plug_one_fs' ) ) {
			/**
			 * @return Freemius
			 */
			function plug_one_fs() {
				global $plug_one_fs;
				if ( ! isset( $plug_one_fs ) ) {
					$plug_one_fs = fs_dynamic_init(
						array(
							'id'                  => Plug_One_Licensing::config( 'id' ),
							'slug'                => 'plug-one',
							'premium_slug'        => 'plug-one-pro',
							'type'                => 'plugin',
							'public_key'          => Plug_One_Licensing::config( 'public_key' ),
							'is_premium'          => true,
							'has_premium_version' => true,
							'has_paid_plans'      => true,
							'has_addons'          => false,
							'is_org_compliant'    => true,
							'menu'                => array(
								'slug'    => 'plug-one-support',
								'contact' => true,
								'support' => false,
								'pricing' => true,
								'account' => true,
								'parent'  => array(
									'slug' => 'woocommerce',
								),
							),
						)
					);
				}
				return $plug_one_fs;
			}
		}

		self::$fs = plug_one_fs();
		do_action( 'plug_one_fs_loaded' );
	}

	/**
	 * @param string $key     Config key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function config( $key, $default = '' ) {
		$map = array(
			'id'          => defined( 'PLUG_ONE_FS_ID' ) ? PLUG_ONE_FS_ID : '',
			'public_key'  => defined( 'PLUG_ONE_FS_PUBLIC_KEY' ) ? PLUG_ONE_FS_PUBLIC_KEY : '',
			'support_email' => defined( 'PLUG_ONE_SUPPORT_EMAIL' ) ? PLUG_ONE_SUPPORT_EMAIL : 'support@example.com',
			'docs_url'    => defined( 'PLUG_ONE_DOCS_URL' ) ? PLUG_ONE_DOCS_URL : '',
			'pricing_url' => defined( 'PLUG_ONE_PRICING_URL' ) ? PLUG_ONE_PRICING_URL : '',
		);
		return isset( $map[ $key ] ) && '' !== $map[ $key ] && null !== $map[ $key ] ? $map[ $key ] : $default;
	}
}
