<?php
/**
 * Transient-based idempotency (WordPress stand-in for Nest Redis locks).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Idempotency {

	const PREFIX = 'plug_one_idem_';

	/**
	 * Try to claim a key. Returns false if already pending or done.
	 *
	 * @param string $key Logical key (e.g. stk:123, callback:ws_CO_…).
	 * @param int    $ttl Seconds to hold the pending lock.
	 * @return bool True if this caller owns the work.
	 */
	public static function claim( $key, $ttl = 120 ) {
		$storage = self::storage_key( $key );
		$existing = get_transient( $storage );

		if ( false !== $existing ) {
			return false;
		}

		// add_option-style race: set_transient is not atomic across requests,
		// so we double-check with a short-lived "claiming" marker via options API when available.
		if ( function_exists( 'wp_cache_add' ) ) {
			$cache_key = self::PREFIX . 'lock_' . md5( $key );
			if ( ! wp_cache_add( $cache_key, 1, 'plug_one', $ttl ) ) {
				return false;
			}
		}

		set_transient( $storage, 'pending', $ttl );
		return true;
	}

	/**
	 * Mark work finished so retries short-circuit as duplicates.
	 *
	 * @param string $key Logical key.
	 * @param int    $ttl Keep "done" marker (default 24h).
	 */
	public static function complete( $key, $ttl = null ) {
		if ( null === $ttl ) {
			$ttl = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
		}
		set_transient( self::storage_key( $key ), 'done', (int) $ttl );
	}

	/**
	 * Drop lock so a failed attempt can retry (Nest deletes Redis key on throw).
	 *
	 * @param string $key Logical key.
	 */
	public static function release( $key ) {
		delete_transient( self::storage_key( $key ) );
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( self::PREFIX . 'lock_' . md5( $key ), 'plug_one' );
		}
	}

	/**
	 * @param string $key Logical key.
	 * @return bool
	 */
	public static function is_done( $key ) {
		return 'done' === get_transient( self::storage_key( $key ) );
	}

	/**
	 * @param string $key Logical key.
	 * @return bool
	 */
	public static function is_pending( $key ) {
		return 'pending' === get_transient( self::storage_key( $key ) );
	}

	/**
	 * @param string $key Logical key.
	 * @return string
	 */
	protected static function storage_key( $key ) {
		return self::PREFIX . md5( (string) $key );
	}
}
