<?php
/**
 * Audit log of STK attempts (mirrors sing_africa mpesa_transactions).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Transactions {

	/**
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'plug_one_transactions';
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			phone varchar(20) NOT NULL DEFAULT '',
			amount decimal(12,2) NOT NULL DEFAULT 0,
			account_reference varchar(20) NOT NULL DEFAULT '',
			merchant_request_id varchar(100) DEFAULT NULL,
			checkout_request_id varchar(100) DEFAULT NULL,
			receipt_number varchar(50) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			result_code varchar(20) DEFAULT NULL,
			result_desc text,
			stk_raw longtext,
			callback_raw longtext,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY checkout_request_id (checkout_request_id),
			KEY receipt_number (receipt_number),
			KEY status (status)
		) {$collate};";

		dbDelta( $sql );
	}

	/**
	 * @param array $row Column => value.
	 * @return int Insert ID.
	 */
	public static function insert( $row ) {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$row = array_merge(
			array(
				'created_at' => $now,
				'updated_at' => $now,
				'status'     => 'pending',
			),
			$row
		);

		$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param string $checkout_request_id Daraja CheckoutRequestID.
	 * @param array  $row                 Columns to update.
	 */
	public static function update_by_checkout( $checkout_request_id, $row ) {
		global $wpdb;

		if ( ! $checkout_request_id ) {
			return;
		}

		$row['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			$row,
			array( 'checkout_request_id' => $checkout_request_id )
		);
	}

	/**
	 * @param string $checkout_request_id Daraja CheckoutRequestID.
	 * @return object|null
	 */
	public static function find_by_checkout( $checkout_request_id ) {
		global $wpdb;

		if ( ! $checkout_request_id ) {
			return null;
		}

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE checkout_request_id = %s ORDER BY id DESC LIMIT 1',
				$checkout_request_id
			)
		);
	}
}
