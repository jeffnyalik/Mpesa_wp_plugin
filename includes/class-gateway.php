<?php
/**
 * WooCommerce payment gateway: Lipa Na M-Pesa STK Push or manual Paybill/Till.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Gateway extends WC_Payment_Gateway {

	/**
	 * Instructions shown on thank-you page and emails.
	 *
	 * @var string
	 */
	public $instructions = '';

	public function __construct() {
		$this->id                 = PLUG_ONE_GATEWAY_ID;
		$this->method_title       = __( 'Plug One M-Pesa', 'plug-one' );
		$this->method_description = __( 'Accept Lipa Na M-Pesa via Safaricom Daraja STK Push (Buy Goods or Paybill).', 'plug-one' );
		$this->has_fields         = true;
		$this->icon               = '';
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->get_option( 'title', __( 'M-Pesa', 'plug-one' ) );
		$this->description  = $this->get_option( 'description' );
		$this->enabled      = $this->get_option( 'enabled' );
		$this->instructions = $this->get_option( 'instructions' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_instructions' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'           => array(
				'title'   => __( 'Enable/Disable', 'plug-one' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Plug One M-Pesa', 'plug-one' ),
				'default' => 'no',
			),
			'title'             => array(
				'title'       => __( 'Title', 'plug-one' ),
				'type'        => 'text',
				'description' => __( 'Payment method name at checkout.', 'plug-one' ),
				'default'     => __( 'M-Pesa', 'plug-one' ),
				'desc_tip'    => true,
			),
			'description'       => array(
				'title'       => __( 'Description', 'plug-one' ),
				'type'        => 'textarea',
				'default'     => __( 'Pay with M-Pesa. You will receive a PIN prompt on your phone.', 'plug-one' ),
			),
			'instructions'      => array(
				'title'       => __( 'Instructions', 'plug-one' ),
				'type'        => 'textarea',
				'description' => __( 'Shown on the thank-you page and in emails for manual payments.', 'plug-one' ),
				'default'     => __( 'Complete the M-Pesa prompt on your phone to finish this order.', 'plug-one' ),
			),
			'payment_mode'      => array(
				'title'       => __( 'Payment mode', 'plug-one' ),
				'type'        => 'select',
				'description' => __( 'STK Push sends a PIN prompt. Manual shows your Paybill/Till and waits for you to confirm.', 'plug-one' ),
				'default'     => 'stk',
				'options'     => array(
					'stk'    => __( 'STK Push (automated)', 'plug-one' ),
					'manual' => __( 'Manual Paybill / Till', 'plug-one' ),
				),
			),
			'environment'       => array(
				'title'   => __( 'Daraja environment', 'plug-one' ),
				'type'    => 'select',
				'default' => 'sandbox',
				'options' => array(
					'sandbox'    => __( 'Sandbox (testing)', 'plug-one' ),
					'production' => __( 'Production (live)', 'plug-one' ),
				),
			),
			'transaction_type'  => array(
				'title'       => __( 'Transaction type', 'plug-one' ),
				'type'        => 'select',
				'description' => __( 'Buy Goods uses your Till as Party B. Paybill uses CustomerPayBillOnline.', 'plug-one' ),
				'default'     => 'CustomerBuyGoodsOnline',
				'options'     => array(
					'CustomerBuyGoodsOnline' => __( 'Buy Goods / Till (CustomerBuyGoodsOnline)', 'plug-one' ),
					'CustomerPayBillOnline'  => __( 'Paybill (CustomerPayBillOnline)', 'plug-one' ),
				),
			),
			'consumer_key'      => array(
				'title' => __( 'Consumer key', 'plug-one' ),
				'type'  => 'text',
			),
			'consumer_secret'   => array(
				'title'       => __( 'Consumer secret', 'plug-one' ),
				'type'        => 'password',
				'description' => __( 'Leave blank to keep the current secret.', 'plug-one' ),
			),
			'shortcode'         => array(
				'title'       => __( 'Business shortcode', 'plug-one' ),
				'type'        => 'text',
				'description' => __( 'Used in the Daraja password (BusinessShortCode).', 'plug-one' ),
			),
			'party_b'           => array(
				'title'       => __( 'Party B (Till / Paybill)', 'plug-one' ),
				'type'        => 'text',
				'description' => __( 'Where funds land. For Buy Goods this is your Till. Leave blank to use the shortcode.', 'plug-one' ),
			),
			'passkey'           => array(
				'title'       => __( 'Lipa Na M-Pesa Online passkey', 'plug-one' ),
				'type'        => 'password',
				'description' => __( 'Leave blank to keep the current passkey.', 'plug-one' ),
			),
			'callback_url'      => array(
				'title'       => __( 'Callback URL override', 'plug-one' ),
				'type'        => 'text',
				'description' => __( 'Optional. Leave blank to use the built-in WC-API URL. Must be public HTTPS in production.', 'plug-one' ),
				'placeholder' => Plug_One_Callback::url(),
			),
		);
	}

	public function admin_options() {
		echo '<h2>' . esc_html( $this->method_title ) . '</h2>';
		echo wp_kses_post( wpautop( $this->method_description ) );

		echo '<div class="plug-one-admin-panel">';
		echo '<h3>' . esc_html__( 'Callback URLs (register these in the Daraja portal)', 'plug-one' ) . '</h3>';
		echo '<p><code>' . esc_html( Plug_One_Callback::url() ) . '</code></p>';
		echo '<p class="description">' . esc_html__( 'REST fallback:', 'plug-one' ) . ' <code>' . esc_html( Plug_One_Callback::rest_url() ) . '</code></p>';
		echo '<p><button type="button" class="button" id="plug-one-test-connection">' . esc_html__( 'Test Daraja credentials', 'plug-one' ) . '</button> <span id="plug-one-test-result"></span></p>';
		if ( 'KES' !== get_woocommerce_currency() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Store currency is not KES. This gateway will stay hidden at checkout until currency is Kenyan Shilling.', 'plug-one' ) . '</p></div>';
		}
		if ( ! is_ssl() && 'production' === $this->get_option( 'environment' ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Production STK Push requires HTTPS. Safaricom will reject HTTP callback URLs.', 'plug-one' ) . '</p></div>';
		}
		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Pretty permalinks should be enabled so the /wc-api/ callback URL works reliably.', 'plug-one' ) . '</p></div>';
		}
		echo '</div>';

		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}

	public function process_admin_options() {
		$secrets = array( 'consumer_secret', 'passkey' );
		foreach ( $secrets as $key ) {
			$field_key = $this->get_field_key( $key );
			if ( isset( $_POST[ $field_key ] ) && '' === $_POST[ $field_key ] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$_POST[ $field_key ] = $this->get_option( $key );
			}
		}
		parent::process_admin_options();
	}

	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		$allowed = apply_filters( 'plug_one_allowed_currencies', array( 'KES' ) );
		if ( ! in_array( get_woocommerce_currency(), $allowed, true ) ) {
			return false;
		}

		if ( 'stk' === $this->get_option( 'payment_mode', 'stk' ) ) {
			if ( ! $this->get_option( 'consumer_key' ) || ! $this->get_option( 'shortcode' ) ) {
				return false;
			}
		}

		return parent::is_available();
	}

	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}

		if ( 'manual' === $this->get_option( 'payment_mode', 'stk' ) ) {
			$pay_to = $this->get_option( 'party_b' ) ? $this->get_option( 'party_b' ) : $this->get_option( 'shortcode' );
			echo '<p>' . esc_html__( 'Paybill / Till:', 'plug-one' ) . ' <strong>' . esc_html( $pay_to ) . '</strong></p>';
		}

		$phone = '';
		if ( WC()->customer ) {
			$phone = WC()->customer->get_billing_phone();
		}

		woocommerce_form_field(
			'plug_one_phone',
			array(
				'type'              => 'tel',
				'label'             => __( 'M-Pesa phone number', 'plug-one' ),
				'placeholder'       => '0712 345 678',
				'required'          => true,
				'class'             => array( 'form-row-wide', 'plug-one-phone-field' ),
				'custom_attributes' => array(
					'autocomplete' => 'tel',
					'inputmode'    => 'tel',
				),
			),
			$phone
		);
	}

	public function validate_fields() {
		$phone = Plug_One_Phone::normalize( $this->posted_phone() );
		if ( ! Plug_One_Phone::is_valid( $phone ) ) {
			wc_add_notice( __( 'Enter a valid Kenyan M-Pesa mobile number (07… or 2547…).', 'plug-one' ), 'error' );
			return false;
		}
		return true;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Order not found.', 'plug-one' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$phone = Plug_One_Phone::normalize( $this->posted_phone() );
		if ( ! Plug_One_Phone::is_valid( $phone ) ) {
			wc_add_notice( __( 'Enter a valid Kenyan M-Pesa mobile number.', 'plug-one' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_meta_data( Plug_One_Order_Service::META_PHONE, $phone );
		$order->save();

		if ( 'manual' === $this->get_option( 'payment_mode', 'stk' ) ) {
			$order->update_meta_data( Plug_One_Order_Service::META_STATUS, 'pending' );
			$order->update_status(
				'on-hold',
				sprintf(
					/* translators: %s phone number */
					__( 'Awaiting manual M-Pesa payment from %s.', 'plug-one' ),
					$phone
				)
			);
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		$amount = Plug_One_Order_Service::order_amount( $order );
		if ( $amount < 1 ) {
			wc_add_notice( __( 'M-Pesa amount must be at least KES 1.', 'plug-one' ), 'error' );
			return array( 'result' => 'failure' );
		}

		try {
			$result = Plug_One_Order_Service::initiate_stk( $order, $phone );
			$order->update_status(
				'pending',
				$result['customer_message']
			);
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} catch ( Exception $e ) {
			$order->update_meta_data( Plug_One_Order_Service::META_STATUS, 'failed' );
			$order->update_status( 'failed', $e->getMessage() );
			Plug_One_Logger::log(
				'payment_stk_failed',
				array(
					'orderId' => $order_id,
					'phone'   => $phone,
					'error'   => $e->getMessage(),
				)
			);
			wc_add_notice( __( 'Could not start M-Pesa payment. Please try again or contact the store.', 'plug-one' ), 'error' );
			return array( 'result' => 'failure' );
		}
	}

	public function thankyou_instructions( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}

		if ( $order->is_paid() ) {
			$receipt = $order->get_meta( Plug_One_Order_Service::META_RECEIPT );
			if ( $receipt ) {
				echo '<p>' . esc_html__( 'M-Pesa receipt:', 'plug-one' ) . ' <strong>' . esc_html( $receipt ) . '</strong></p>';
			}
			return;
		}

		if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
			return;
		}

		echo '<div class="plug-one-waiting" data-order-id="' . esc_attr( $order->get_id() ) . '">';
		echo '<p class="plug-one-waiting__status">' . esc_html__( 'Waiting for M-Pesa payment. Check your phone and enter your PIN.', 'plug-one' ) . '</p>';
		echo '</div>';
	}

	/**
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Admin email.
	 * @param bool     $plain_text    Plain text email.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || ! $this->instructions || $this->id !== $order->get_payment_method() ) {
			return;
		}
		if ( $plain_text ) {
			echo wp_strip_all_tags( $this->instructions ) . "\n\n";
			return;
		}
		echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
	}

	/**
	 * Phone from classic checkout POST or Blocks paymentMethodData.
	 *
	 * @return string
	 */
	protected function posted_phone() {
		if ( isset( $_POST['plug_one_phone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return sanitize_text_field( wp_unslash( $_POST['plug_one_phone'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return '';
	}
}
