<?php
/**
 * WooCommerce logger wrapper. Never writes secrets (passkey, consumer secret, password).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Logger {

	const SOURCE = 'plug-one-mpesa';

	/**
	 * @param string $event   Short event name (stk_request, callback_received, …).
	 * @param array  $details Key/value context.
	 */
	public static function log( $event, $details = array() ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$blocked = array( 'password', 'passkey', 'consumer_secret', 'secret', 'authorization' );
		$parts   = array();

		foreach ( $details as $key => $value ) {
			if ( in_array( strtolower( (string) $key ), $blocked, true ) ) {
				continue;
			}
			if ( null === $value || '' === $value ) {
				continue;
			}
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = wp_json_encode( $value );
			}
			$parts[] = $key . '=' . $value;
		}

		$line = '[MPESA] ' . $event;
		if ( $parts ) {
			$line .= '  ' . implode( '  ', $parts );
		}

		wc_get_logger()->info( $line, array( 'source' => self::SOURCE ) );
	}
}
