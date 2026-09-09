<?php
/**
 * Kenyan M-Pesa MSISDN helpers — same rules as sing_africa `normalizeMpesaMsisdn`.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Phone {

	/**
	 * Normalize a Kenyan mobile to 2547XXXXXXXX (digits only).
	 *
	 * @param string $mobile Raw input (07…, 7…, 2547…, +2547…).
	 * @return string
	 */
	public static function normalize( $mobile ) {
		$cleaned = preg_replace( '/\D/', '', (string) $mobile );

		if ( 0 === strpos( $cleaned, '0' ) && 10 === strlen( $cleaned ) ) {
			return '254' . substr( $cleaned, 1 );
		}

		if ( 0 === strpos( $cleaned, '254' ) && 12 === strlen( $cleaned ) ) {
			return $cleaned;
		}

		if ( 0 === strpos( $cleaned, '7' ) && 9 === strlen( $cleaned ) ) {
			return '254' . $cleaned;
		}

		return $cleaned;
	}

	/**
	 * Safaricom mobile only: 2547 + 8 digits.
	 *
	 * @param string $normalized Output of normalize().
	 * @return bool
	 */
	public static function is_valid( $normalized ) {
		return (bool) preg_match( '/^2547\d{8}$/', (string) $normalized );
	}

	/**
	 * @param string $mobile Raw input.
	 * @return string Valid 2547XXXXXXXX or empty string.
	 */
	public static function validate_or_empty( $mobile ) {
		$normalized = self::normalize( $mobile );
		return self::is_valid( $normalized ) ? $normalized : '';
	}
}
