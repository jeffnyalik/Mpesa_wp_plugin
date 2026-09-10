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
		$this->method_title       = __( 'Plug One M-Pesa', 'plug-one-lipa-na-m-pesa' );
		$this->method_description = __( 'Accept Lipa Na M-Pesa via Manual Paybill/Till (free) or Daraja STK Push (Pro).', 'plug-one-lipa-na-m-pesa' );
		$this->has_fields         = true;
		$this->icon               = '';
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->get_option( 'title', __( 'M-Pesa', 'plug-one-lipa-na-m-pesa' ) );
		$this->description  = $this->get_option( 'description' );
		$this->enabled      = $this->get_option( 'enabled' );
		$this->instructions = $this->get_option( 'instructions' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_instructions' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	public function init_form_fields() {
		$mode_options = array(
			'manual' => __( 'Manual Paybill / Till — Free', 'plug-one-lipa-na-m-pesa' ),
		);
		$mode_description = __( 'Manual shows your Paybill/Till and waits for you to confirm payment. Upgrade to Pro for STK Push.', 'plug-one-lipa-na-m-pesa' );
		$mode_default     = 'manual';

		// Freemius strips this block from the WordPress.org free ZIP.
		if ( function_exists( 'polnmp_fs' ) && is_object( polnmp_fs() ) && polnmp_fs()->is__premium_only() ) {
			$mode_options = array(
				'stk'    => __( 'STK Push (automated) — Pro', 'plug-one-lipa-na-m-pesa' ),
				'manual' => __( 'Manual Paybill / Till — Free', 'plug-one-lipa-na-m-pesa' ),
			);
			$mode_description = __( 'STK Push sends a PIN prompt. Manual shows your Paybill/Till and waits for you to confirm.', 'plug-one-lipa-na-m-pesa' );
			$mode_default     = Plug_One_Licensing::can_use_pro() ? 'stk' : 'manual';
		}

		$this->form_fields = array(
			'enabled'           => array(
				'title'   => __( 'Enable/Disable', 'plug-one-lipa-na-m-pesa' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Plug One M-Pesa', 'plug-one-lipa-na-m-pesa' ),
				'default' => 'no',
			),
			'title'             => array(
				'title'       => __( 'Title', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'text',
				'description' => __( 'Payment method name at checkout.', 'plug-one-lipa-na-m-pesa' ),
				'default'     => __( 'M-Pesa', 'plug-one-lipa-na-m-pesa' ),
				'desc_tip'    => true,
			),
			'description'       => array(
				'title'       => __( 'Description', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'textarea',
				'default'     => __( 'Pay with M-Pesa using the Paybill/Till shown at checkout, or wait for the PIN prompt if STK is enabled.', 'plug-one-lipa-na-m-pesa' ),
			),
			'instructions'      => array(
				'title'       => __( 'Instructions', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'textarea',
				'description' => __( 'Shown on the thank-you page and in emails for manual payments.', 'plug-one-lipa-na-m-pesa' ),
				'default'     => __( 'Complete the M-Pesa payment to finish this order.', 'plug-one-lipa-na-m-pesa' ),
			),
			'payment_mode'      => array(
				'title'       => __( 'Payment mode', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'select',
				'description' => $mode_description,
				'default'     => $mode_default,
				'options'     => $mode_options,
			),
			'environment'       => array(
				'title'   => __( 'Daraja environment', 'plug-one-lipa-na-m-pesa' ),
				'type'    => 'select',
				'default' => 'sandbox',
				'options' => array(
					'sandbox'    => __( 'Sandbox (testing)', 'plug-one-lipa-na-m-pesa' ),
					'production' => __( 'Production (live)', 'plug-one-lipa-na-m-pesa' ),
				),
			),
			'transaction_type'  => array(
				'title'       => __( 'Transaction type', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'select',
				'description' => __( 'Buy Goods uses your Till as Party B. Paybill uses CustomerPayBillOnline.', 'plug-one-lipa-na-m-pesa' ),
				'default'     => 'CustomerPayBillOnline',
				'options'     => array(
					'CustomerPayBillOnline'  => __( 'Paybill (CustomerPayBillOnline)', 'plug-one-lipa-na-m-pesa' ),
					'CustomerBuyGoodsOnline' => __( 'Buy Goods / Till (CustomerBuyGoodsOnline)', 'plug-one-lipa-na-m-pesa' ),
				),
			),
			'consumer_key'      => array(
				'title' => __( 'Consumer key', 'plug-one-lipa-na-m-pesa' ),
				'type'  => 'text',
			),
			'consumer_secret'   => array(
				'title'       => __( 'Consumer secret', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'password',
				'description' => __( 'Leave blank to keep the current secret.', 'plug-one-lipa-na-m-pesa' ),
			),
			'shortcode'         => array(
				'title'       => __( 'Business shortcode', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'text',
				'description' => __( 'Used in the Daraja password (BusinessShortCode).', 'plug-one-lipa-na-m-pesa' ),
			),
			'party_b'           => array(
				'title'       => __( 'Party B (Till / Paybill)', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'text',
				'description' => __( 'Where funds land. For Buy Goods this is your Till. Leave blank to use the shortcode.', 'plug-one-lipa-na-m-pesa' ),
			),
			'passkey'           => array(
				'title'       => __( 'Lipa Na M-Pesa Online passkey', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'password',
				'description' => __( 'Leave blank to keep the current passkey.', 'plug-one-lipa-na-m-pesa' ),
			),
			'callback_url'      => array(
				'title'       => __( 'Callback URL override', 'plug-one-lipa-na-m-pesa' ),
				'type'        => 'text',
				'description' => __( 'Optional. Leave blank to use the built-in WC-API URL. Must be public HTTPS in production.', 'plug-one-lipa-na-m-pesa' ),
				'placeholder' => Plug_One_Callback::url(),
			),
		);
	}

	public function admin_options() {
		echo '<h2>' . esc_html( $this->method_title ) . '</h2>';
		echo wp_kses_post( wpautop( $this->method_description ) );

		echo '<div class="plug-one-admin-panel">';
		echo '<h3>' . esc_html__( 'Callback URLs (register these in the Daraja portal)', 'plug-one-lipa-na-m-pesa' ) . '</h3>';
		echo '<p><code>' . esc_html( Plug_One_Callback::url() ) . '</code></p>';
		echo '<p class="description">' . esc_html__( 'REST fallback:', 'plug-one-lipa-na-m-pesa' ) . ' <code>' . esc_html( Plug_One_Callback::rest_url() ) . '</code></p>';
		echo '<p><button type="button" class="button" id="plug-one-test-connection">' . esc_html__( 'Test Daraja credentials', 'plug-one-lipa-na-m-pesa' ) . '</button> <span id="plug-one-test-result"></span></p>';
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=plug-one-support' ) ) . '">' . esc_html__( 'Docs, license & support →', 'plug-one-lipa-na-m-pesa' ) . '</a></p>';
		if ( ! Plug_One_Licensing::can_use_pro() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Pro license required for STK Push. Manual mode still works.', 'plug-one-lipa-na-m-pesa' ) . '</p></div>';
		}
		if ( 'KES' !== get_woocommerce_currency() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Store currency is not KES. This gateway will stay hidden at checkout until currency is Kenyan Shilling.', 'plug-one-lipa-na-m-pesa' ) . '</p></div>';
		}
		if ( ! is_ssl() && 'production' === $this->get_option( 'environment' ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Production STK Push requires HTTPS. Safaricom will reject HTTP callback URLs.', 'plug-one-lipa-na-m-pesa' ) . '</p></div>';
		}
		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Pretty permalinks should be enabled so the /wc-api/ callback URL works reliably.', 'plug-one-lipa-na-m-pesa' ) . '</p></div>';
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

		$mode = $this->get_option( 'payment_mode', 'manual' );
		if ( 'stk' === $mode && Plug_One_Licensing::can_use_pro() ) {
			if ( ! $this->get_option( 'consumer_key' ) || ! $this->get_option( 'shortcode' ) ) {
				return false;
			}
		} elseif ( 'stk' === $mode && ! Plug_One_Licensing::can_use_pro() ) {
			// Stay available; process_payment falls back to manual.
			if ( ! $this->get_option( 'shortcode' ) && ! $this->get_option( 'party_b' ) ) {
				return false;
			}
		} elseif ( 'manual' === $mode ) {
			if ( ! $this->get_option( 'shortcode' ) && ! $this->get_option( 'party_b' ) ) {
				return false;
			}
		}

		return parent::is_available();
	}

	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}

		if ( 'manual' === $this->get_option( 'payment_mode', 'manual' ) ) {
			$pay_to = $this->get_option( 'party_b' ) ? $this->get_option( 'party_b' ) : $this->get_option( 'shortcode' );
			echo '<p>' . esc_html__( 'Paybill / Till:', 'plug-one-lipa-na-m-pesa' ) . ' <strong>' . esc_html( $pay_to ) . '</strong></p>';
		}

		$phone = '';
		if ( WC()->customer ) {
			$phone = WC()->customer->get_billing_phone();
		}

		woocommerce_form_field(
			'plug_one_phone',
			array(
				'type'              => 'tel',
				'label'             => __( 'M-Pesa phone number', 'plug-one-lipa-na-m-pesa' ),
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
			wc_add_notice( __( 'Enter a valid Kenyan M-Pesa mobile number (07… or 2547…).', 'plug-one-lipa-na-m-pesa' ), 'error' );
			return false;
		}
		return true;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Order not found.', 'plug-one-lipa-na-m-pesa' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$phone = Plug_One_Phone::normalize( $this->posted_phone() );
		if ( ! Plug_One_Phone::is_valid( $phone ) ) {
			wc_add_notice( __( 'Enter a valid Kenyan M-Pesa mobile number.', 'plug-one-lipa-na-m-pesa' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_meta_data( Plug_One_Order_Service::META_PHONE, $phone );
		$order->save();

		$mode = $this->get_option( 'payment_mode', 'manual' );
		if ( 'stk' === $mode && ! Plug_One_Licensing::can_use_pro() ) {
			$mode = 'manual';
			wc_add_notice( __( 'STK Push requires Pro. Falling back to manual M-Pesa instructions.', 'plug-one-lipa-na-m-pesa' ), 'notice' );
		}

		if ( 'manual' === $mode ) {
			$order->update_meta_data( Plug_One_Order_Service::META_STATUS, 'pending' );
			$order->update_status(
				'on-hold',
				sprintf(
					/* translators: %s phone number */
					__( 'Awaiting manual M-Pesa payment from %s.', 'plug-one-lipa-na-m-pesa' ),
					$phone
				)
			);
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		// Freemius strips STK initiation from the WordPress.org free ZIP.
		if ( function_exists( 'polnmp_fs' ) && is_object( polnmp_fs() ) && polnmp_fs()->can_use_premium_code__premium_only() ) {
			$amount = Plug_One_Order_Service::order_amount( $order );
			if ( $amount < 1 ) {
				wc_add_notice( __( 'M-Pesa amount must be at least KES 1.', 'plug-one-lipa-na-m-pesa' ), 'error' );
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
				wc_add_notice( __( 'Could not start M-Pesa payment. Please try again or contact the store.', 'plug-one-lipa-na-m-pesa' ), 'error' );
				return array( 'result' => 'failure' );
			}
		}

		wc_add_notice( __( 'STK Push is not available in this build. Use Manual mode or upgrade to Pro.', 'plug-one-lipa-na-m-pesa' ), 'error' );
		return array( 'result' => 'failure' );
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
				echo '<p>' . esc_html__( 'M-Pesa receipt:', 'plug-one-lipa-na-m-pesa' ) . ' <strong>' . esc_html( $receipt ) . '</strong></p>';
			}
			return;
		}

		if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
			return;
		}

		echo '<div class="plug-one-waiting" data-order-id="' . esc_attr( $order->get_id() ) . '">';
		echo '<p class="plug-one-waiting__status">' . esc_html__( 'Waiting for M-Pesa payment. Check your phone and enter your PIN.', 'plug-one-lipa-na-m-pesa' ) . '</p>';
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
	 * Phone from classic checkout POST or Blocks paymentMethodData / payment_data.
	 *
	 * @return string
	 */
	protected function posted_phone() {
		if ( isset( $_POST['plug_one_phone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return sanitize_text_field( wp_unslash( $_POST['plug_one_phone'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// WooCommerce Blocks / Store API: payment_data[{key,value}, …].
		if ( isset( $_POST['payment_data'] ) && is_array( $_POST['payment_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			foreach ( wp_unslash( $_POST['payment_data'] ) as $row ) { // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( ! is_array( $row ) ) {
					continue;
				}
				$key = isset( $row['key'] ) ? $row['key'] : '';
				if ( 'plug_one_phone' === $key && isset( $row['value'] ) ) {
					return sanitize_text_field( $row['value'] );
				}
			}
		}

		return '';
	}
}
