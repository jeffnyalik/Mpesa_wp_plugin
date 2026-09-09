<?php
/**
 * Pure helpers for STK callback payloads (easy to unit-test without WooCommerce).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Callback_Payload {

	/**
	 * @param array $payload Raw Daraja JSON.
	 * @return array|null stkCallback object or null.
	 */
	public static function stk_callback( array $payload ) {
		if ( empty( $payload['Body']['stkCallback'] ) || ! is_array( $payload['Body']['stkCallback'] ) ) {
			return null;
		}
		return $payload['Body']['stkCallback'];
	}

	/**
	 * @param array  $items CallbackMetadata.Item list.
	 * @param string $name  Item Name.
	 * @return mixed|string
	 */
	public static function metadata_item( array $items, $name ) {
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['Name'] ) && $item['Name'] === $name ) {
				return isset( $item['Value'] ) ? $item['Value'] : '';
			}
		}
		return '';
	}

	/**
	 * @param array $stk stkCallback object.
	 * @return array{checkout_id:string,result_code:int,result_desc:string,receipt:string,amount:mixed,txn_date:mixed}
	 */
	public static function summarize( array $stk ) {
		$items = isset( $stk['CallbackMetadata']['Item'] ) && is_array( $stk['CallbackMetadata']['Item'] )
			? $stk['CallbackMetadata']['Item']
			: array();

		return array(
			'checkout_id' => isset( $stk['CheckoutRequestID'] ) ? (string) $stk['CheckoutRequestID'] : '',
			'result_code' => isset( $stk['ResultCode'] ) ? (int) $stk['ResultCode'] : -1,
			'result_desc' => isset( $stk['ResultDesc'] ) ? (string) $stk['ResultDesc'] : '',
			'receipt'     => (string) self::metadata_item( $items, 'MpesaReceiptNumber' ),
			'amount'      => self::metadata_item( $items, 'Amount' ),
			'txn_date'    => self::metadata_item( $items, 'TransactionDate' ),
		);
	}

	/**
	 * Map Daraja ResultCode to plugin status for non-success outcomes.
	 *
	 * @param int $result_code Daraja ResultCode.
	 * @return string cancelled|failed|timed_out
	 */
	public static function failure_status( $result_code ) {
		$code = (int) $result_code;
		if ( 1032 === $code ) {
			return 'cancelled';
		}
		if ( 1037 === $code ) {
			return 'timed_out';
		}
		return 'failed';
	}
}
