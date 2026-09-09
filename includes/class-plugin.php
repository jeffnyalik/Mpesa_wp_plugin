<?php
/**
 * Plugin bootstrap.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

final class Plug_One_Plugin {

	/**
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * @var bool
	 */
	protected static $booted = false;

	/**
	 * @var bool
	 */
	protected static $blocks_registered = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate() {
		require_once PLUG_ONE_PATH . 'includes/class-transactions.php';
		Plug_One_Transactions::install();
		update_option( 'plug_one_db_version', PLUG_ONE_VERSION );
		flush_rewrite_rules();
	}

	public static function boot() {
		if ( self::$booted ) {
			return;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_woocommerce' ) );
			return;
		}

		self::$booted = true;
		self::load_files();
		self::instance()->init();
	}

	public static function missing_woocommerce() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Plug One M-Pesa requires WooCommerce to be installed and active.', 'plug-one' );
		echo '</p></div>';
	}

	protected static function load_files() {
		require_once PLUG_ONE_PATH . 'includes/class-phone.php';
		require_once PLUG_ONE_PATH . 'includes/class-logger.php';
		require_once PLUG_ONE_PATH . 'includes/class-transactions.php';
		require_once PLUG_ONE_PATH . 'includes/class-daraja-client.php';
		require_once PLUG_ONE_PATH . 'includes/class-order-service.php';
		require_once PLUG_ONE_PATH . 'includes/class-callback.php';
		require_once PLUG_ONE_PATH . 'includes/class-gateway.php';
		require_once PLUG_ONE_PATH . 'includes/class-rest.php';
		require_once PLUG_ONE_PATH . 'includes/class-admin-order.php';
	}

	public function init() {
		if ( get_option( 'plug_one_db_version' ) !== PLUG_ONE_VERSION ) {
			Plug_One_Transactions::install();
			update_option( 'plug_one_db_version', PLUG_ONE_VERSION );
		}

		load_plugin_textdomain( 'plug-one', false, dirname( plugin_basename( PLUG_ONE_FILE ) ) . '/languages' );

		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );

		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->register_blocks();
		} else {
			add_action( 'woocommerce_blocks_loaded', array( $this, 'register_blocks' ), 5 );
		}
		add_action( 'plug_one_query_stk', array( 'Plug_One_Order_Service', 'query_and_update' ) );

		Plug_One_Callback::init();
		Plug_One_REST::init();
		Plug_One_Admin_Order::init();
	}

	public function register_gateway( $gateways ) {
		$gateways[] = 'Plug_One_Gateway';
		return $gateways;
	}

	public function register_blocks() {
		if ( self::$blocks_registered ) {
			return;
		}

		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			return;
		}

		require_once PLUG_ONE_PATH . 'includes/class-blocks.php';

		$register = static function ( $registry ) {
			$registry->register( new Plug_One_Blocks() );
		};

		self::$blocks_registered = true;

		if ( did_action( 'woocommerce_blocks_payment_method_type_registration' )
			&& class_exists( '\Automattic\WooCommerce\Blocks\Package' ) ) {
			try {
				$registry = \Automattic\WooCommerce\Blocks\Package::container()->get(
					\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry::class
				);
				$registry->register( new Plug_One_Blocks() );
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
			return;
		}

		add_action( 'woocommerce_blocks_payment_method_type_registration', $register );
	}

	public function frontend_assets() {
		$on_checkout = function_exists( 'is_checkout' ) && is_checkout();
		$on_received = function_exists( 'is_order_received_page' ) && is_order_received_page();
		if ( $on_checkout || $on_received ) {
			wp_enqueue_style(
				'plug-one-frontend',
				PLUG_ONE_URL . 'assets/css/frontend.css',
				array(),
				PLUG_ONE_VERSION
			);
		}

		if ( ! $on_received ) {
			return;
		}

		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || PLUG_ONE_GATEWAY_ID !== $order->get_payment_method() ) {
			return;
		}

		if ( $order->is_paid() || ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'plug-one-thankyou',
			PLUG_ONE_URL . 'assets/js/thankyou.js',
			array(),
			PLUG_ONE_VERSION,
			true
		);

		wp_localize_script(
			'plug-one-thankyou',
			'plugOneThankyou',
			array(
				'restUrl'     => rest_url( 'plug-one/v1/orders/' . $order->get_id() . '/status' ),
				'orderKey'    => $order->get_order_key(),
				'pollMs'      => 3000,
				'maxAttempts' => 24,
			)
		);
	}
}
