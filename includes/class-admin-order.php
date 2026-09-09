<?php
/**
 * Order metabox + AJAX: resend STK, query status, test credentials.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Admin_Order {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_plug_one_resend_stk', array( __CLASS__, 'ajax_resend' ) );
		add_action( 'wp_ajax_plug_one_query_status', array( __CLASS__, 'ajax_query' ) );
		add_action( 'wp_ajax_plug_one_test_connection', array( __CLASS__, 'ajax_test' ) );
		add_action( 'wp_ajax_plug_one_simulate_payment', array( __CLASS__, 'ajax_simulate' ) );
	}

	public static function meta_boxes() {
		$screens = array( 'shop_order', 'woocommerce_page_wc-orders' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'plug-one-mpesa',
				__( 'M-Pesa (Plug One)', 'plug-one' ),
				array( __CLASS__, 'render' ),
				$screen,
				'side',
				'high'
			);
		}
	}

	public static function assets( $hook ) {
		$is_order = in_array( $hook, array( 'post.php', 'woocommerce_page_wc-orders' ), true );
		$is_wc    = false !== strpos( $hook, 'woocommerce' );

		if ( ! $is_order && ! $is_wc ) {
			return;
		}

		wp_enqueue_style(
			'plug-one-admin',
			PLUG_ONE_URL . 'assets/css/admin.css',
			array(),
			PLUG_ONE_VERSION
		);

		wp_enqueue_script(
			'plug-one-admin',
			PLUG_ONE_URL . 'assets/js/admin-order.js',
			array( 'jquery' ),
			PLUG_ONE_VERSION,
			true
		);

		wp_localize_script(
			'plug-one-admin',
			'plugOneAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'plug_one_admin' ),
			)
		);
	}

	/**
	 * @param WP_Post|WC_Order $post_or_order Post or HPOS order.
	 */
	public static function render( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order || PLUG_ONE_GATEWAY_ID !== $order->get_payment_method() ) {
			echo '<p>' . esc_html__( 'This order did not use Plug One M-Pesa.', 'plug-one' ) . '</p>';
			return;
		}

		$status  = $order->get_meta( Plug_One_Order_Service::META_STATUS );
		$phone   = $order->get_meta( Plug_One_Order_Service::META_PHONE );
		$receipt = $order->get_meta( Plug_One_Order_Service::META_RECEIPT );
		$chk     = $order->get_meta( Plug_One_Order_Service::META_CHECKOUT_ID );
		$desc    = $order->get_meta( Plug_One_Order_Service::META_RESULT_DESC );

		echo '<p><strong>' . esc_html__( 'Status', 'plug-one' ) . ':</strong> ' . esc_html( $status ? $status : '—' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Phone', 'plug-one' ) . ':</strong> ' . esc_html( $phone ? $phone : '—' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Receipt', 'plug-one' ) . ':</strong> ' . esc_html( $receipt ? $receipt : '—' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'CheckoutRequestID', 'plug-one' ) . ':</strong><br><code>' . esc_html( $chk ? $chk : '—' ) . '</code></p>';
		if ( $desc ) {
			echo '<p>' . esc_html( $desc ) . '</p>';
		}

		if ( ! $order->is_paid() ) {
			echo '<p>';
			echo '<button type="button" class="button button-primary plug-one-admin-action" data-action="plug_one_resend_stk" data-order="' . esc_attr( $order->get_id() ) . '">' . esc_html__( 'Resend STK', 'plug-one' ) . '</button> ';
			echo '<button type="button" class="button plug-one-admin-action" data-action="plug_one_query_status" data-order="' . esc_attr( $order->get_id() ) . '">' . esc_html__( 'Query status', 'plug-one' ) . '</button>';
			if ( self::can_simulate() ) {
				echo ' <button type="button" class="button plug-one-admin-action" data-action="plug_one_simulate_payment" data-order="' . esc_attr( $order->get_id() ) . '">' . esc_html__( 'Simulate payment', 'plug-one' ) . '</button>';
			}
			echo '</p>';
			echo '<p class="plug-one-admin-result"></p>';
		}
	}

	public static function ajax_resend() {
		self::guard();
		if ( ! Plug_One_Licensing::can_use_pro() ) {
			wp_send_json_error( array( 'message' => __( 'Resend STK requires a Pro license.', 'plug-one' ) ) );
		}
		$order = self::order_from_request();
		$phone = $order->get_meta( Plug_One_Order_Service::META_PHONE );
		if ( ! Plug_One_Phone::is_valid( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid M-Pesa phone on this order.', 'plug-one' ) ) );
		}

		try {
			$order->update_meta_data( Plug_One_Order_Service::META_STK_AT, 0 );
			$order->update_meta_data( Plug_One_Order_Service::META_STATUS, 'pending' );
			$order->save();
			Plug_One_Idempotency::release( 'stk:' . $order->get_id() );
			$result = Plug_One_Order_Service::initiate_stk( $order, $phone );
			if ( in_array( $order->get_status(), array( 'failed', 'cancelled' ), true ) ) {
				$order->update_status( 'pending', __( 'M-Pesa STK Push resent.', 'plug-one' ) );
			}
			wp_send_json_success( array( 'message' => $result['customer_message'] ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function ajax_query() {
		self::guard();
		$order  = self::order_from_request();
		$status = Plug_One_Order_Service::query_and_update( $order );
		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s status */
					__( 'Status: %s', 'plug-one' ),
					$status
				),
			)
		);
	}

	public static function ajax_test() {
		self::guard();
		if ( ! function_exists( 'WC' ) ) {
			wp_send_json_error( array( 'message' => 'WooCommerce is not loaded.' ) );
		}
		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( empty( $gateways[ PLUG_ONE_GATEWAY_ID ] ) ) {
			wp_send_json_error( array( 'message' => 'Gateway not found.' ) );
		}
		try {
			$client = new Plug_One_Daraja_Client( $gateways[ PLUG_ONE_GATEWAY_ID ] );
			$client->get_access_token();
			wp_send_json_success( array( 'message' => __( 'Daraja OAuth succeeded.', 'plug-one' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function ajax_simulate() {
		self::guard();
		if ( ! self::can_simulate() ) {
			wp_send_json_error( array( 'message' => __( 'Simulate payment is only available in sandbox / WP_DEBUG.', 'plug-one' ) ) );
		}
		$order = self::order_from_request();
		try {
			$receipt = Plug_One_Order_Service::simulate_success( $order );
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %s receipt */
						__( 'Simulated payment. Receipt: %s', 'plug-one' ),
						$receipt
					),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Local / sandbox only — Safaricom never hits localhost.
	 *
	 * @return bool
	 */
	protected static function can_simulate() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}
		$settings = get_option( 'woocommerce_' . PLUG_ONE_GATEWAY_ID . '_settings', array() );
		return isset( $settings['environment'] ) && 'sandbox' === $settings['environment'];
	}

	protected static function guard() {
		check_ajax_referer( 'plug_one_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'plug-one' ) ), 403 );
		}
	}

	/**
	 * @return WC_Order
	 */
	protected static function order_from_request() {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$order    = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'plug-one' ) ) );
		}
		return $order;
	}
}
