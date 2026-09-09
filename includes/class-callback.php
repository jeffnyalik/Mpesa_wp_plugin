<?php
/**
 * Public Safaricom webhooks (plaintext JSON, no auth) — same contract as NestJS MpesaController.
 *
 * ACK first so Safaricom stops retrying, then process (shutdown / Action Scheduler).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Callback {

	public static function init() {
		add_action( 'woocommerce_api_' . PLUG_ONE_GATEWAY_ID, array( __CLASS__, 'handle_stk' ) );
		add_action( 'woocommerce_api_' . PLUG_ONE_GATEWAY_ID . '_validation', array( __CLASS__, 'handle_validation' ) );
		add_action( 'woocommerce_api_' . PLUG_ONE_GATEWAY_ID . '_confirmation', array( __CLASS__, 'handle_confirmation' ) );
		add_action( 'plug_one_process_callback', array( __CLASS__, 'process_queued' ), 10, 1 );
	}

	/**
	 * WC-API URL Safaricom should POST to.
	 *
	 * @return string
	 */
	public static function url() {
		return home_url( '/wc-api/' . PLUG_ONE_GATEWAY_ID . '/' );
	}

	public static function validation_url() {
		return home_url( '/wc-api/' . PLUG_ONE_GATEWAY_ID . '_validation/' );
	}

	public static function confirmation_url() {
		return home_url( '/wc-api/' . PLUG_ONE_GATEWAY_ID . '_confirmation/' );
	}

	public static function rest_url() {
		return rest_url( 'plug-one/v1/mpesa/callback' );
	}

	public static function handle_stk() {
		$payload = self::read_json();
		self::ack_then_process( $payload );
	}

	/**
	 * REST callback (same body as WC-API).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle_stk_rest( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			$payload = $request->get_body_params();
		}
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		self::queue_or_process( $payload );

		return new WP_REST_Response(
			array(
				'ResultCode' => 0,
				'ResultDesc' => 'Accepted',
			),
			200
		);
	}

	public static function handle_validation() {
		$payload = self::read_json();
		Plug_One_Logger::log( 'http_validation', array( 'payload' => $payload ) );
		self::send_json( array( 'ResultCode' => '0', 'ResultDesc' => 'Accepted' ) );
	}

	public static function handle_confirmation() {
		$payload = self::read_json();
		Plug_One_Logger::log( 'http_confirmation', array( 'payload' => $payload ) );
		self::send_json( array( 'ResultCode' => '0', 'ResultDesc' => 'Accepted' ) );
	}

	/**
	 * @param array $payload Callback body (may be stored as JSON string from AS).
	 */
	public static function process_queued( $payload ) {
		if ( is_string( $payload ) ) {
			$decoded = json_decode( $payload, true );
			$payload = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $payload ) ) {
			return;
		}
		self::process( $payload );
	}

	/**
	 * Respond 200 Accepted immediately, then process.
	 *
	 * @param array $payload Callback body.
	 */
	protected static function ack_then_process( array $payload ) {
		self::queue_or_process( $payload );
		self::respond_accepted();
	}

	/**
	 * Prefer Action Scheduler; otherwise run after the HTTP response via shutdown.
	 *
	 * @param array $payload Callback body.
	 */
	protected static function queue_or_process( array $payload ) {
		$checkout = isset( $payload['Body']['stkCallback']['CheckoutRequestID'] ) ? $payload['Body']['stkCallback']['CheckoutRequestID'] : '';
		Plug_One_Logger::log(
			'http_callback',
			array(
				'checkoutRequestId' => $checkout,
				'resultCode'        => isset( $payload['Body']['stkCallback']['ResultCode'] ) ? $payload['Body']['stkCallback']['ResultCode'] : '',
			)
		);

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				'plug_one_process_callback',
				array( wp_json_encode( $payload ) ),
				'plug-one'
			);
			return;
		}

		register_shutdown_function(
			static function () use ( $payload ) {
				self::process( $payload );
			}
		);
	}

	/**
	 * @param array $payload Callback body.
	 */
	protected static function process( array $payload ) {
		try {
			Plug_One_Order_Service::handle_callback( $payload );
		} catch ( Throwable $e ) {
			Plug_One_Logger::log(
				'callback_error',
				array(
					'error' => $e->getMessage(),
					'type'  => get_class( $e ),
				)
			);
		}
	}

	protected static function respond_accepted() {
		self::send_json(
			array(
				'ResultCode' => 0,
				'ResultDesc' => 'Accepted',
			)
		);
	}

	protected static function read_json() {
		$raw = file_get_contents( 'php://input' );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : array();
	}

	protected static function send_json( array $body ) {
		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $body );
		exit;
	}
}
