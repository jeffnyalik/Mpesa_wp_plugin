<?php
/**
 * REST: Safaricom callback + customer order-status polling.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'plug-one/v1',
			'/mpesa/callback',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'Plug_One_Callback', 'handle_stk_rest' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'plug-one/v1',
			'/mpesa/validation',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'stub_c2b' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'plug-one/v1',
			'/mpesa/confirmation',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'stub_c2b' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'plug-one/v1',
			'/orders/(?P<id>\d+)/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'order_status' ),
				'permission_callback' => array( __CLASS__, 'can_view_order' ),
				'args'                => array(
					'id'  => array(
						'required' => true,
						'type'     => 'integer',
					),
					'key' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_view_order( WP_REST_Request $request ) {
		$order = wc_get_order( (int) $request['id'] );
		if ( ! $order ) {
			return false;
		}
		$key = (string) $request->get_param( 'key' );
		return hash_equals( $order->get_order_key(), $key );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function order_status( WP_REST_Request $request ) {
		$order = wc_get_order( (int) $request['id'] );
		if ( ! $order || PLUG_ONE_GATEWAY_ID !== $order->get_payment_method() ) {
			return new WP_REST_Response( array( 'message' => 'Not found' ), 404 );
		}

		$status = (string) $order->get_meta( Plug_One_Order_Service::META_STATUS );
		if ( $order->is_paid() ) {
			$status = 'success';
		} elseif ( in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) && $order->get_meta( Plug_One_Order_Service::META_CHECKOUT_ID ) ) {
			$lock_key = 'plug_one_query_lock_' . $order->get_id();
			if ( ! get_transient( $lock_key ) ) {
				set_transient( $lock_key, 1, 5 );
				$status = Plug_One_Order_Service::query_and_update( $order );
				$order  = wc_get_order( $order->get_id() );
			}
		}

		$paid = $order && $order->is_paid();
		$msg  = __( 'Waiting for M-Pesa payment…', 'plug-one-lipa-na-m-pesa' );
		if ( $paid ) {
			$msg = __( 'Payment received.', 'plug-one-lipa-na-m-pesa' );
		} elseif ( 'cancelled' === $status ) {
			$msg = __( 'Payment was cancelled on the phone.', 'plug-one-lipa-na-m-pesa' );
		} elseif ( 'timed_out' === $status ) {
			$msg = __( 'The M-Pesa prompt timed out. Place the order again.', 'plug-one-lipa-na-m-pesa' );
		} elseif ( 'failed' === $status ) {
			$msg = $order->get_meta( Plug_One_Order_Service::META_RESULT_DESC );
			if ( ! $msg ) {
				$msg = __( 'Payment failed.', 'plug-one-lipa-na-m-pesa' );
			}
		}

		return new WP_REST_Response(
			array(
				'status'    => $status ? $status : 'pending',
				'wc_status' => $order->get_status(),
				'paid'      => $paid,
				'receipt'   => $order->get_meta( Plug_One_Order_Service::META_RECEIPT ),
				'message'   => $msg,
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function stub_c2b( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		Plug_One_Logger::log( 'http_c2b', array( 'payload' => $payload ) );
		return new WP_REST_Response(
			array(
				'ResultCode' => '0',
				'ResultDesc' => 'Accepted',
			),
			200
		);
	}
}
