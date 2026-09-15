<?php
/**
 * Optional config: support contact + docs URL (gitignored as config/licensing.php).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Config {

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
	public static function get( $key, $default = '' ) {
		$map = array(
			'support_email' => defined( 'PLUG_ONE_SUPPORT_EMAIL' ) ? PLUG_ONE_SUPPORT_EMAIL : 'jeffnyak@gmail.com',
			'support_phone' => defined( 'PLUG_ONE_SUPPORT_PHONE' ) ? PLUG_ONE_SUPPORT_PHONE : '0716431039',
			'docs_url'      => defined( 'PLUG_ONE_DOCS_URL' ) ? PLUG_ONE_DOCS_URL : '',
		);
		return isset( $map[ $key ] ) && '' !== $map[ $key ] && null !== $map[ $key ] ? $map[ $key ] : $default;
	}
}
