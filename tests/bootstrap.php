<?php
/**
 * PHPUnit bootstrap — lightweight stubs so pure helpers run without WordPress.
 */

define( 'ABSPATH', __DIR__ . '/../' );

$GLOBALS['plug_one_transients'] = array();
$GLOBALS['plug_one_cache']      = array();

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		if ( ! isset( $GLOBALS['plug_one_transients'][ $key ] ) ) {
			return false;
		}
		$entry = $GLOBALS['plug_one_transients'][ $key ];
		if ( $entry['expires'] > 0 && $entry['expires'] < time() ) {
			unset( $GLOBALS['plug_one_transients'][ $key ] );
			return false;
		}
		return $entry['value'];
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $ttl = 0 ) {
		$GLOBALS['plug_one_transients'][ $key ] = array(
			'value'   => $value,
			'expires' => $ttl > 0 ? time() + (int) $ttl : 0,
		);
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		unset( $GLOBALS['plug_one_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_cache_add' ) ) {
	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
		$full = $group . ':' . $key;
		if ( isset( $GLOBALS['plug_one_cache'][ $full ] ) ) {
			return false;
		}
		$GLOBALS['plug_one_cache'][ $full ] = $data;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, $group = '' ) {
		unset( $GLOBALS['plug_one_cache'][ $group . ':' . $key ] );
		return true;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-phone.php';
require_once dirname( __DIR__ ) . '/includes/class-callback-payload.php';
require_once dirname( __DIR__ ) . '/includes/class-idempotency.php';

// Daraja static helpers only — avoid loading WC_Payment_Gateway subclass.
require_once dirname( __DIR__ ) . '/includes/class-daraja-client.php';
