<?php
/**
 * WooCommerce Checkout Blocks payment method.
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Plug_One_Blocks extends AbstractPaymentMethodType {

	protected $name = PLUG_ONE_GATEWAY_ID;

	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . PLUG_ONE_GATEWAY_ID . '_settings', array() );
	}

	public function is_active() {
		$gateways = WC()->payment_gateways()->payment_gateways();
		return isset( $gateways[ $this->name ] ) && $gateways[ $this->name ]->is_available();
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'plug-one-blocks',
			PLUG_ONE_URL . 'assets/js/blocks.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			PLUG_ONE_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'plug-one-blocks', 'plug-one-lipa-na-m-pesa' );
		}

		return array( 'plug-one-blocks' );
	}

	public function get_payment_method_data() {
		$phone = '';
		if ( WC()->customer ) {
			$phone = WC()->customer->get_billing_phone();
		}

		return array(
			'title'        => isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'M-Pesa', 'plug-one-lipa-na-m-pesa' ),
			'description'  => isset( $this->settings['description'] ) ? $this->settings['description'] : '',
			'supports'     => array( 'products' ),
			'billingPhone' => $phone,
		);
	}
}
