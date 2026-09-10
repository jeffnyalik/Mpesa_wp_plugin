<?php
/**
 * WooCommerce → Plug One Help (docs + support).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

class Plug_One_Admin_Support {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUG_ONE_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Plug One Help', 'plug-one-lipa-na-m-pesa' ),
			__( 'Plug One Help', 'plug-one-lipa-na-m-pesa' ),
			'manage_woocommerce',
			'plug-one-support',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * @param array $links Plugin row links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$extra = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . PLUG_ONE_GATEWAY_ID ) ) . '">' . esc_html__( 'Settings', 'plug-one-lipa-na-m-pesa' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=plug-one-support' ) ) . '">' . esc_html__( 'Docs & support', 'plug-one-lipa-na-m-pesa' ) . '</a>',
		);
		return array_merge( $extra, $links );
	}

	public static function render() {
		$email   = Plug_One_Licensing::config( 'support_email', 'jeffnyak@gmail.com' );
		$phone   = Plug_One_Licensing::config( 'support_phone', '0716431039' );
		$docs    = Plug_One_Licensing::config( 'docs_url', '' );
		$pricing = Plug_One_Licensing::pricing_url();
		$pro     = Plug_One_Licensing::can_use_pro();
		$fs_on   = Plug_One_Licensing::is_freemius_configured();

		$local_docs = PLUG_ONE_URL . 'docs/';

		echo '<div class="wrap plug-one-support">';
		echo '<h1>' . esc_html__( 'Plug One — Docs & support', 'plug-one-lipa-na-m-pesa' ) . '</h1>';

		echo '<div class="plug-one-admin-panel" style="max-width:720px;margin-top:1rem;">';
		echo '<h2>' . esc_html__( 'License', 'plug-one-lipa-na-m-pesa' ) . '</h2>';
		if ( $pro ) {
			echo '<p><span class="plug-one-ok">' . esc_html__( 'Pro features unlocked (STK Push).', 'plug-one-lipa-na-m-pesa' ) . '</span></p>';
		} else {
			echo '<p><span class="plug-one-err">' . esc_html__( 'Free mode: Manual Paybill/Till only. Activate Pro for STK Push.', 'plug-one-lipa-na-m-pesa' ) . '</span></p>';
		}
		if ( $fs_on && $pricing ) {
			echo '<p><a class="button button-primary" href="' . esc_url( $pricing ) . '">' . esc_html__( 'Upgrade / activate license', 'plug-one-lipa-na-m-pesa' ) . '</a></p>';
		} elseif ( ! $fs_on ) {
			echo '<p class="description">' . esc_html__( 'Freemius SDK not loaded. Run composer install in the plugin folder, then add the secret key to wp-config.php (see Docs → Licensing). Pro stays locked until the SDK loads.', 'plug-one-lipa-na-m-pesa' ) . '</p>';
		}
		echo '</div>';

		echo '<div class="plug-one-admin-panel" style="max-width:720px;margin-top:1rem;">';
		echo '<h2>' . esc_html__( 'Documentation', 'plug-one-lipa-na-m-pesa' ) . '</h2>';
		if ( ! $docs ) {
			echo '<p class="description">' . esc_html__( 'Using bundled docs in the plugin (no public website required).', 'plug-one-lipa-na-m-pesa' ) . '</p>';
		}
		echo '<ul style="list-style:disc;margin-left:1.25rem;">';
		self::doc_link( __( 'Getting started', 'plug-one-lipa-na-m-pesa' ), $docs ? trailingslashit( $docs ) . 'getting-started/' : $local_docs . 'getting-started.md' );
		self::doc_link( __( 'Paybill vs Till', 'plug-one-lipa-na-m-pesa' ), $docs ? trailingslashit( $docs ) . 'paybill-vs-till/' : $local_docs . 'paybill-vs-till.md' );
		self::doc_link( __( 'Callbacks & ngrok', 'plug-one-lipa-na-m-pesa' ), $docs ? trailingslashit( $docs ) . 'callbacks/' : $local_docs . 'callbacks.md' );
		self::doc_link( __( 'Production Till checklist', 'plug-one-lipa-na-m-pesa' ), $docs ? trailingslashit( $docs ) . 'production-till/' : $local_docs . 'production-till.md' );
		self::doc_link( __( 'Licensing & updates', 'plug-one-lipa-na-m-pesa' ), $docs ? trailingslashit( $docs ) . 'licensing/' : $local_docs . 'licensing.md' );
		self::doc_link( __( 'Troubleshooting', 'plug-one-lipa-na-m-pesa' ), $docs ? trailingslashit( $docs ) . 'troubleshooting/' : $local_docs . 'troubleshooting.md' );
		echo '</ul>';
		echo '</div>';

		echo '<div class="plug-one-admin-panel" style="max-width:720px;margin-top:1rem;">';
		echo '<h2>' . esc_html__( 'Support', 'plug-one-lipa-na-m-pesa' ) . '</h2>';
		echo '<p>' . esc_html__( 'Contact us with your site URL, WooCommerce version, and a copy of the plug-one-mpesa log.', 'plug-one-lipa-na-m-pesa' ) . '</p>';
		echo '<p><a class="button" href="mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( 'Plug One M-Pesa support' ) . '">' . esc_html( $email ) . '</a></p>';
		if ( $phone ) {
			$tel = preg_replace( '/\D+/', '', $phone );
			if ( 0 === strpos( $tel, '0' ) && 10 === strlen( $tel ) ) {
				$tel = '254' . substr( $tel, 1 );
			}
			echo '<p><a class="button" href="tel:+' . esc_attr( $tel ) . '">' . esc_html( $phone ) . '</a></p>';
			echo '<p class="description">' . esc_html__( 'WhatsApp / call (Kenya). Prefer email for logs and screenshots.', 'plug-one-lipa-na-m-pesa' ) . '</p>';
		}
		echo '<p class="description">' . esc_html__( 'Typical response within 1–2 business days for Pro licenses.', 'plug-one-lipa-na-m-pesa' ) . '</p>';
		echo '</div>';

		echo '</div>';
	}

	/**
	 * @param string $label Link label.
	 * @param string $url   URL.
	 */
	protected static function doc_link( $label, $url ) {
		echo '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a></li>';
	}
}
