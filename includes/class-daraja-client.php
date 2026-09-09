<?php
/**
 * Safaricom Daraja client — OAuth, STK Push, STK query.
 * Ported from sing_africa `src/mpesa/services/mpesa.service.ts`.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Daraja_Client {

	/**
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	public function __construct( WC_Payment_Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Daraja password timestamp: yyyyMMddHHmmss in Africa/Nairobi.
	 *
	 * @return string
	 */
	public static function timestamp() {
		$dt = new DateTime( 'now', new DateTimeZone( 'Africa/Nairobi' ) );
		return $dt->format( 'YmdHis' );
	}

	/**
	 * @param string $shortcode Business shortcode.
	 * @param string $passkey   Lipa Na M-Pesa Online passkey.
	 * @param string $timestamp yyyyMMddHHmmss.
	 * @return string
	 */
	public static function password( $shortcode, $passkey, $timestamp ) {
		return base64_encode( $shortcode . $passkey . $timestamp );
	}

	/**
	 * @param array $params phone, amount, account_reference, transaction_desc.
	 * @return array Daraja STK response (must include CheckoutRequestID).
	 * @throws Exception If Daraja rejects or credentials are missing.
	 */
	public function stk_push( array $params ) {
		$token     = $this->get_access_token();
		$shortcode = $this->require_option( 'shortcode', 'M-Pesa shortcode is not configured.' );
		$passkey   = $this->require_option( 'passkey', 'M-Pesa passkey is not configured.' );
		$timestamp = self::timestamp();
		$amount    = (int) round( (float) $params['amount'] );

		$payload = array(
			'BusinessShortCode' => $shortcode,
			'Password'          => self::password( $shortcode, $passkey, $timestamp ),
			'Timestamp'         => $timestamp,
			'TransactionType'   => $this->transaction_type(),
			'Amount'            => $amount,
			'PartyA'            => $params['phone'],
			'PartyB'            => $this->party_b(),
			'PhoneNumber'       => $params['phone'],
			'CallBackURL'       => $this->callback_url(),
			'AccountReference'  => substr( (string) $params['account_reference'], 0, 12 ),
			'TransactionDesc'   => substr( (string) $params['transaction_desc'], 0, 13 ),
		);

		Plug_One_Logger::log(
			'stk_request',
			array(
				'phone'             => $params['phone'],
				'amount'            => $amount,
				'accountReference'  => $payload['AccountReference'],
				'type'              => $payload['TransactionType'],
				'partyB'            => $payload['PartyB'],
				'callback'          => $payload['CallBackURL'],
			)
		);

		$data = $this->post_json( $this->stk_url(), $token, $payload );

		if ( empty( $data['CheckoutRequestID'] ) ) {
			Plug_One_Logger::log( 'stk_error', array( 'phone' => $params['phone'], 'reason' => 'missing CheckoutRequestID', 'raw' => $data ) );
			throw new Exception( 'M-Pesa STK did not return CheckoutRequestID.' );
		}

		Plug_One_Logger::log(
			'stk_accepted',
			array(
				'phone'             => $params['phone'],
				'amount'            => $amount,
				'checkoutRequestId' => $data['CheckoutRequestID'],
				'merchantRequestId' => isset( $data['MerchantRequestID'] ) ? $data['MerchantRequestID'] : '',
				'responseCode'      => isset( $data['ResponseCode'] ) ? $data['ResponseCode'] : '',
				'customerMessage'   => isset( $data['CustomerMessage'] ) ? $data['CustomerMessage'] : '',
			)
		);

		return $data;
	}

	/**
	 * @param string $checkout_request_id CheckoutRequestID from STK.
	 * @return array
	 * @throws Exception On HTTP/parse failure.
	 */
	public function query_transaction_status( $checkout_request_id ) {
		$token     = $this->get_access_token();
		$shortcode = $this->require_option( 'shortcode', 'M-Pesa shortcode is not configured.' );
		$timestamp = self::timestamp();

		$result = $this->post_json(
			$this->query_url(),
			$token,
			array(
				'BusinessShortCode' => $shortcode,
				'Password'          => self::password( $shortcode, $this->require_option( 'passkey', 'M-Pesa passkey is not configured.' ), $timestamp ),
				'Timestamp'         => $timestamp,
				'CheckoutRequestID' => $checkout_request_id,
			)
		);

		Plug_One_Logger::log(
			'stk_query',
			array(
				'checkoutRequestId' => $checkout_request_id,
				'resultCode'        => isset( $result['ResultCode'] ) ? $result['ResultCode'] : '',
				'resultDesc'        => isset( $result['ResultDesc'] ) ? $result['ResultDesc'] : '',
				'responseCode'      => isset( $result['ResponseCode'] ) ? $result['ResponseCode'] : '',
			)
		);

		return $result;
	}

	/**
	 * Fetch and cache OAuth token. TTL = max(60, expires_in - 120) like NestJS.
	 *
	 * @return string
	 * @throws Exception If credentials missing or Daraja fails.
	 */
	public function get_access_token() {
		$cache_key = 'plug_one_mpesa_token_' . md5( $this->get_option( 'consumer_key' ) . $this->env() );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) && $cached ) {
			return $cached;
		}

		$key    = $this->require_option( 'consumer_key', 'M-Pesa consumer key is not configured.' );
		$secret = $this->require_option( 'consumer_secret', 'M-Pesa consumer secret is not configured.' );
		$url    = $this->access_token_url();

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $key . ':' . $secret ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( 'M-Pesa auth error: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['access_token'] ) ) {
			Plug_One_Logger::log(
				'oauth_parse_error',
				array(
					'env'    => $this->env(),
					'status' => $code,
					'body'   => substr( $body, 0, 300 ),
				)
			);
			throw new Exception( 'Failed to get M-Pesa access token (HTTP ' . $code . ').' );
		}

		$ttl = max( 60, (int) ( isset( $data['expires_in'] ) ? $data['expires_in'] : 3599 ) - 120 );
		set_transient( $cache_key, $data['access_token'], $ttl );

		return $data['access_token'];
	}

	/**
	 * @return string sandbox|production
	 */
	public function env() {
		$env = $this->get_option( 'environment', 'sandbox' );
		return 'production' === $env ? 'production' : 'sandbox';
	}

	public function callback_url() {
		$custom = $this->get_option( 'callback_url' );
		if ( $custom ) {
			return $custom;
		}
		return Plug_One_Callback::url();
	}

	protected function transaction_type() {
		$type = $this->get_option( 'transaction_type', 'CustomerPayBillOnline' );
		return 'CustomerBuyGoodsOnline' === $type ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline';
	}

	protected function party_b() {
		$party_b = $this->get_option( 'party_b' );
		return $party_b ? $party_b : $this->get_option( 'shortcode' );
	}

	protected function access_token_url() {
		return 'sandbox' === $this->env()
			? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
			: 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
	}

	protected function stk_url() {
		return 'sandbox' === $this->env()
			? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
			: 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
	}

	protected function query_url() {
		return 'sandbox' === $this->env()
			? 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query'
			: 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query';
	}

	/**
	 * @param string $url   Endpoint.
	 * @param string $token Bearer token.
	 * @param array  $body  JSON body.
	 * @return array
	 * @throws Exception On HTTP or parse failure.
	 */
	protected function post_json( $url, $token, array $body ) {
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( 'M-Pesa request error: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			throw new Exception( 'Failed to parse M-Pesa response.' );
		}

		if ( $code < 200 || $code >= 300 ) {
			Plug_One_Logger::log( 'stk_http_error', array( 'status' => $code, 'body' => substr( $raw, 0, 300 ) ) );
			throw new Exception( 'M-Pesa API error: ' . substr( $raw, 0, 300 ) );
		}

		return $data;
	}

	protected function get_option( $key, $default = '' ) {
		$value = $this->gateway->get_option( $key, $default );
		if ( is_string( $value ) ) {
			return trim( $value );
		}
		return $default;
	}

	protected function require_option( $key, $message ) {
		$value = $this->get_option( $key );
		if ( '' === $value ) {
			throw new Exception( $message );
		}
		return $value;
	}
}
