<?php
/**
 * Complete / fail WooCommerce orders from STK callback or query.
 * Ported from sing_africa `PaymentsService.handleMpesaCallback` / `getOwnedPaymentStatus`.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Order_Service {

	const META_PHONE           = '_plug_one_phone';
	const META_CHECKOUT_ID     = '_plug_one_checkout_request_id';
	const META_MERCHANT_ID     = '_plug_one_merchant_request_id';
	const META_RECEIPT         = '_plug_one_receipt';
	const META_TXN_DATE        = '_plug_one_transaction_date';
	const META_RESULT_CODE     = '_plug_one_result_code';
	const META_RESULT_DESC     = '_plug_one_result_desc';
	const META_STATUS          = '_plug_one_status';
	const META_STK_AT          = '_plug_one_stk_pushed_at';
	const META_CALLBACK_AT     = '_plug_one_callback_at';

	/**
	 * @param WC_Order $order Order.
	 * @param string   $phone Normalized 2547XXXXXXXX.
	 * @return array { checkout_request_id, customer_message }
	 * @throws Exception If STK fails.
	 */
	public static function initiate_stk( WC_Order $order, $phone ) {
		$gateway = self::gateway();
		if ( ! $gateway ) {
			throw new Exception( 'M-Pesa gateway is not available.' );
		}

		$idem_key = 'stk:' . $order->get_id();
		$existing = $order->get_meta( self::META_CHECKOUT_ID );
		$pushed   = (int) $order->get_meta( self::META_STK_AT );

		// Replay within 90s (client double-submit / Blocks retry) — Nest Idempotency-Key pattern.
		if ( $existing && $pushed && ( time() - $pushed ) < 90 && 'pending' === $order->get_meta( self::META_STATUS ) ) {
			return array(
				'checkout_request_id' => $existing,
				'customer_message'    => __( 'Please complete M-Pesa payment on your phone.', 'plug-one' ),
			);
		}

		if ( ! Plug_One_Idempotency::claim( $idem_key, 90 ) ) {
			if ( $existing ) {
				return array(
					'checkout_request_id' => $existing,
					'customer_message'    => __( 'Please complete M-Pesa payment on your phone.', 'plug-one' ),
				);
			}
			throw new Exception( __( 'A payment request is already in progress for this order. Please wait.', 'plug-one' ) );
		}

		try {
			$amount  = self::order_amount( $order );
			$account = self::account_reference( $order );
			$desc    = self::transaction_desc( $order );
			$client  = new Plug_One_Daraja_Client( $gateway );
			$stk     = $client->stk_push(
				array(
					'phone'             => $phone,
					'amount'            => $amount,
					'account_reference' => $account,
					'transaction_desc'  => $desc,
				)
			);

			$order->update_meta_data( self::META_PHONE, $phone );
			$order->update_meta_data( self::META_CHECKOUT_ID, $stk['CheckoutRequestID'] );
			$order->update_meta_data( self::META_MERCHANT_ID, isset( $stk['MerchantRequestID'] ) ? $stk['MerchantRequestID'] : '' );
			$order->update_meta_data( self::META_STATUS, 'pending' );
			$order->update_meta_data( self::META_STK_AT, time() );
			$order->save();

			Plug_One_Transactions::insert(
				array(
					'order_id'            => $order->get_id(),
					'phone'               => $phone,
					'amount'              => $amount,
					'account_reference'   => $account,
					'merchant_request_id' => isset( $stk['MerchantRequestID'] ) ? $stk['MerchantRequestID'] : '',
					'checkout_request_id' => $stk['CheckoutRequestID'],
					'status'              => 'pending',
					'stk_raw'             => wp_json_encode( $stk ),
				)
			);

			if ( function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action( time() + 70, 'plug_one_query_stk', array( $order->get_id() ), 'plug-one' );
			} else {
				wp_schedule_single_event( time() + 70, 'plug_one_query_stk', array( $order->get_id() ) );
			}

			$order->add_order_note(
				sprintf(
					/* translators: 1: phone, 2: checkout request id */
					__( 'M-Pesa STK Push sent to %1$s. CheckoutRequestID: %2$s', 'plug-one' ),
					$phone,
					$stk['CheckoutRequestID']
				)
			);

			Plug_One_Idempotency::complete( $idem_key, 90 );

			return array(
				'checkout_request_id' => $stk['CheckoutRequestID'],
				'customer_message'    => ! empty( $stk['CustomerMessage'] )
					? $stk['CustomerMessage']
					: __( 'Please complete M-Pesa payment on your phone.', 'plug-one' ),
			);
		} catch ( Exception $e ) {
			Plug_One_Idempotency::release( $idem_key );
			throw $e;
		}
	}

	/**
	 * @param array $payload Raw Daraja callback JSON.
	 */
	public static function handle_callback( array $payload ) {
		$stk = Plug_One_Callback_Payload::stk_callback( $payload );
		if ( ! $stk ) {
			Plug_One_Logger::log( 'callback_invalid', array( 'payload' => $payload ) );
			return;
		}

		$summary     = Plug_One_Callback_Payload::summarize( $stk );
		$checkout_id = $summary['checkout_id'];
		$result_code = $summary['result_code'];
		$result_desc = $summary['result_desc'];

		Plug_One_Logger::log(
			'callback_received',
			array(
				'checkoutRequestId' => $checkout_id,
				'resultCode'        => $result_code,
				'resultDesc'        => $result_desc,
			)
		);

		if ( ! $checkout_id ) {
			return;
		}

		$idem_key = 'callback:' . $checkout_id;
		if ( Plug_One_Idempotency::is_done( $idem_key ) ) {
			Plug_One_Logger::log( 'callback_duplicate', array( 'checkoutRequestId' => $checkout_id, 'reason' => 'idem_done' ) );
			return;
		}

		$order = self::find_order_by_checkout_id( $checkout_id );
		if ( ! $order ) {
			Plug_One_Logger::log( 'callback_orphan', array( 'checkoutRequestId' => $checkout_id, 'resultCode' => $result_code ) );
			return;
		}

		if ( $order->is_paid() ) {
			Plug_One_Idempotency::complete( $idem_key );
			Plug_One_Logger::log( 'callback_duplicate', array( 'orderId' => $order->get_id(), 'checkoutRequestId' => $checkout_id ) );
			return;
		}

		if ( ! Plug_One_Idempotency::claim( $idem_key, 120 ) ) {
			Plug_One_Logger::log( 'callback_in_progress', array( 'orderId' => $order->get_id(), 'checkoutRequestId' => $checkout_id ) );
			return;
		}

		try {
			// Re-load in case a parallel request completed.
			$order = wc_get_order( $order->get_id() );
			if ( ! $order || $order->is_paid() ) {
				Plug_One_Idempotency::complete( $idem_key );
				return;
			}

			$order->update_meta_data( self::META_RESULT_CODE, (string) $result_code );
			$order->update_meta_data( self::META_RESULT_DESC, (string) $result_desc );
			$order->update_meta_data( self::META_CALLBACK_AT, time() );

			if ( 0 === $result_code ) {
				$receipt  = $summary['receipt'];
				$txn_date = $summary['txn_date'];
				$amount   = $summary['amount'];

				if ( $receipt && self::receipt_already_used( $receipt, $order->get_id() ) ) {
					Plug_One_Logger::log(
						'callback_receipt_reuse',
						array(
							'orderId' => $order->get_id(),
							'receipt' => $receipt,
						)
					);
					Plug_One_Idempotency::complete( $idem_key );
					return;
				}

				self::complete( $order, $receipt, 'callback', $txn_date );

				Plug_One_Transactions::update_by_checkout(
					$checkout_id,
					array(
						'status'         => 'success',
						'receipt_number' => $receipt,
						'result_code'    => (string) $result_code,
						'result_desc'    => $result_desc,
						'callback_raw'   => wp_json_encode( $payload ),
					)
				);

				Plug_One_Logger::log(
					'payment_success',
					array(
						'orderId'           => $order->get_id(),
						'checkoutRequestId' => $checkout_id,
						'receipt'           => $receipt,
						'amount'            => $amount,
						'phone'             => $order->get_meta( self::META_PHONE ),
					)
				);
				Plug_One_Idempotency::complete( $idem_key );
				return;
			}

			$status = Plug_One_Callback_Payload::failure_status( $result_code );
			self::fail( $order, $status, $result_desc );

			Plug_One_Transactions::update_by_checkout(
				$checkout_id,
				array(
					'status'       => $status,
					'result_code'  => (string) $result_code,
					'result_desc'  => $result_desc,
					'callback_raw' => wp_json_encode( $payload ),
				)
			);

			Plug_One_Logger::log(
				'payment_failed',
				array(
					'orderId'           => $order->get_id(),
					'checkoutRequestId' => $checkout_id,
					'status'            => $status,
					'resultCode'        => $result_code,
					'resultDesc'        => $result_desc,
				)
			);
			Plug_One_Idempotency::complete( $idem_key );
		} catch ( Exception $e ) {
			Plug_One_Idempotency::release( $idem_key );
			Plug_One_Logger::log( 'callback_error', array( 'error' => $e->getMessage(), 'checkoutRequestId' => $checkout_id ) );
			throw $e;
		}
	}

	/**
	 * Query Daraja while order is still pending.
	 *
	 * @param int|WC_Order $order Order or ID.
	 * @return string Plugin status: pending|success|cancelled|failed|timed_out
	 */
	public static function query_and_update( $order ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
		if ( ! $order ) {
			return 'pending';
		}

		if ( $order->is_paid() ) {
			return 'success';
		}

		$checkout_id = $order->get_meta( self::META_CHECKOUT_ID );
		if ( ! $checkout_id ) {
			return (string) $order->get_meta( self::META_STATUS );
		}

		$gateway = self::gateway();
		if ( ! $gateway ) {
			return (string) $order->get_meta( self::META_STATUS );
		}

		$idem_key = 'query:' . $checkout_id;
		if ( ! Plug_One_Idempotency::claim( $idem_key, 15 ) ) {
			$status = $order->get_meta( self::META_STATUS );
			return $status ? $status : 'pending';
		}

		try {
			$client = new Plug_One_Daraja_Client( $gateway );
			$query  = $client->query_transaction_status( $checkout_id );
			$code   = isset( $query['ResultCode'] ) ? (int) $query['ResultCode'] : -1;
			$desc   = isset( $query['ResultDesc'] ) ? $query['ResultDesc'] : '';

			if ( 0 === $code ) {
				self::complete( $order, $order->get_meta( self::META_RECEIPT ), 'query' );
				Plug_One_Transactions::update_by_checkout(
					$checkout_id,
					array(
						'status'      => 'success',
						'result_code' => '0',
						'result_desc' => $desc,
					)
				);
				Plug_One_Idempotency::complete( 'callback:' . $checkout_id );
				Plug_One_Idempotency::release( $idem_key );
				return 'success';
			}

			if ( 1032 === $code ) {
				self::fail( $order, 'cancelled', $desc ? $desc : __( 'Request cancelled by user', 'plug-one' ) );
				Plug_One_Logger::log( 'payment_query_cancelled', array( 'orderId' => $order->get_id(), 'resultDesc' => $desc ) );
				Plug_One_Idempotency::complete( 'callback:' . $checkout_id );
				Plug_One_Idempotency::release( $idem_key );
				return 'cancelled';
			}

			if ( 1037 === $code ) {
				self::fail( $order, 'timed_out', $desc ? $desc : __( 'STK prompt timed out', 'plug-one' ) );
				Plug_One_Logger::log( 'payment_query_timeout', array( 'orderId' => $order->get_id(), 'resultDesc' => $desc ) );
				Plug_One_Idempotency::complete( 'callback:' . $checkout_id );
				Plug_One_Idempotency::release( $idem_key );
				return 'timed_out';
			}

			Plug_One_Idempotency::release( $idem_key );
		} catch ( Exception $e ) {
			Plug_One_Idempotency::release( $idem_key );
			Plug_One_Logger::log( 'stk_query_skipped', array( 'orderId' => $order->get_id(), 'error' => $e->getMessage() ) );
		}

		$latest = wc_get_order( $order->get_id() );
		$status = $latest ? $latest->get_meta( self::META_STATUS ) : 'pending';
		return $status ? $status : 'pending';
	}

	/**
	 * Fake a successful Daraja callback for local testing (sandbox / WP_DEBUG).
	 *
	 * @param WC_Order $order Order.
	 * @return string Receipt number.
	 */
	public static function simulate_success( WC_Order $order ) {
		$checkout = $order->get_meta( self::META_CHECKOUT_ID );
		if ( ! $checkout ) {
			$checkout = 'SIM' . $order->get_id() . time();
			$order->update_meta_data( self::META_CHECKOUT_ID, $checkout );
			$order->save();
		}

		$receipt = 'SIM' . strtoupper( wp_generate_password( 8, false, false ) );
		$now     = new DateTime( 'now', new DateTimeZone( 'Africa/Nairobi' ) );

		self::handle_callback(
			array(
				'Body' => array(
					'stkCallback' => array(
						'MerchantRequestID' => 'sim-' . $order->get_id(),
						'CheckoutRequestID' => $checkout,
						'ResultCode'        => 0,
						'ResultDesc'        => 'The service request is processed successfully',
						'CallbackMetadata'  => array(
							'Item' => array(
								array(
									'Name'  => 'Amount',
									'Value' => self::order_amount( $order ),
								),
								array(
									'Name'  => 'MpesaReceiptNumber',
									'Value' => $receipt,
								),
								array(
									'Name'  => 'TransactionDate',
									'Value' => $now->format( 'YmdHis' ),
								),
							),
						),
					),
				),
			)
		);

		return $receipt;
	}

	/**
	 * @param WC_Order $order   Order.
	 * @param string   $receipt M-Pesa receipt.
	 * @param string   $source  callback|query.
	 * @param mixed    $txn_date Optional Daraja TransactionDate.
	 */
	public static function complete( WC_Order $order, $receipt, $source = 'callback', $txn_date = '' ) {
		if ( $order->is_paid() ) {
			return;
		}

		if ( $receipt ) {
			$order->update_meta_data( self::META_RECEIPT, sanitize_text_field( (string) $receipt ) );
			$order->set_transaction_id( sanitize_text_field( (string) $receipt ) );
		}
		if ( $txn_date ) {
			$order->update_meta_data( self::META_TXN_DATE, sanitize_text_field( (string) $txn_date ) );
		}
		$order->update_meta_data( self::META_STATUS, 'success' );
		$order->save();

		$order->payment_complete( $receipt ? $receipt : $order->get_meta( self::META_CHECKOUT_ID ) );
		$order->add_order_note(
			sprintf(
				/* translators: 1: source, 2: receipt */
				__( 'M-Pesa payment completed via %1$s. Receipt: %2$s', 'plug-one' ),
				$source,
				$receipt ? $receipt : '—'
			)
		);
	}

	/**
	 * @param WC_Order $order  Order.
	 * @param string   $status cancelled|failed|timed_out.
	 * @param string   $reason Human-readable reason.
	 */
	public static function fail( WC_Order $order, $status, $reason ) {
		if ( $order->is_paid() ) {
			return;
		}

		$order->update_meta_data( self::META_STATUS, $status );
		$order->update_meta_data( self::META_RESULT_DESC, sanitize_text_field( (string) $reason ) );
		$order->save();

		$wc_status = 'cancelled' === $status ? 'cancelled' : 'failed';
		$order->update_status(
			$wc_status,
			sprintf(
				/* translators: 1: plugin status, 2: reason */
				__( 'M-Pesa %1$s: %2$s', 'plug-one' ),
				$status,
				$reason
			)
		);
	}

	/**
	 * @param string $checkout_id CheckoutRequestID.
	 * @return WC_Order|null
	 */
	public static function find_order_by_checkout_id( $checkout_id ) {
		if ( ! $checkout_id ) {
			return null;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => self::META_CHECKOUT_ID, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => $checkout_id, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		if ( ! empty( $orders ) ) {
			return $orders[0];
		}

		$row = Plug_One_Transactions::find_by_checkout( $checkout_id );
		if ( $row && ! empty( $row->order_id ) ) {
			return wc_get_order( (int) $row->order_id );
		}

		return null;
	}

	/**
	 * Integer KES for Daraja.
	 *
	 * @param WC_Order $order Order.
	 * @return int
	 */
	public static function order_amount( WC_Order $order ) {
		return (int) round( (float) $order->get_total() );
	}

	/**
	 * Max 12 chars — Daraja AccountReference limit.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public static function account_reference( WC_Order $order ) {
		$ref = 'ORD' . $order->get_id();
		return substr( $ref, 0, 12 );
	}

	/**
	 * Max 13 chars — Daraja TransactionDesc limit.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public static function transaction_desc( WC_Order $order ) {
		return substr( 'Order ' . $order->get_id(), 0, 13 );
	}

	/**
	 * Prevent applying the same M-Pesa receipt to two orders.
	 *
	 * @param string $receipt  Receipt number.
	 * @param int    $order_id Current order ID.
	 * @return bool
	 */
	protected static function receipt_already_used( $receipt, $order_id ) {
		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => self::META_RECEIPT, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => $receipt, // phpcs:ignore WordPress.DB.SlowDBQuery
				'exclude'    => array( (int) $order_id ),
			)
		);
		return ! empty( $orders );
	}

	/**
	 * @return WC_Payment_Gateway|null
	 */
	protected static function gateway() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return null;
		}
		$gateways = WC()->payment_gateways()->payment_gateways();
		return isset( $gateways[ PLUG_ONE_GATEWAY_ID ] ) ? $gateways[ PLUG_ONE_GATEWAY_ID ] : null;
	}
}
