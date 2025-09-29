<?php
/**
 * PayPal Notices Class
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wc-gateway-paypal-helper.php';

/**
 * Class WC_Gateway_Paypal_Notices.
 */
class WC_Gateway_Paypal_Notices {
	/**
	 * The PayPal gateway instance.
	 *
	 * @var WC_Gateway_Paypal
	 */
	private $gateway;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->gateway = WC_Gateway_Paypal::get_instance();
		if ( ! $this->gateway ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'add_paypal_migration_notice' ) );

		// Use admin_head to inject notice on payments settings page.
		// This bypasses the suppress_admin_notices() function which removes all admin_notices hooks on the payments page.
		// This is a workaround to avoid the notice being suppressed by the suppress_admin_notices() function.
		add_action( 'admin_head', array( $this, 'add_paypal_migration_notice_on_payments_settings_page' ) );
	}

	/**
	 * Add notice warning about the migration to PayPal Payments.
	 *
	 * @return void
	 */
	public function add_paypal_migration_notice() {
		// Show only to users who can manage the site.
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip if the gateway is not available or the merchant has not been onboarded.
		if ( ! WC_Gateway_Paypal_Helper::is_paypal_gateway_available() || ! $this->gateway->should_use_orders_v2() ) {
			return;
		}

		// Skip if the notice has been dismissed.
		if ( $this->paypal_migration_notice_dismissed() ) {
			return;
		}

		$doc_url     = 'https://woocommerce.com/document/woocommerce-paypal-payments/paypal-payments-upgrade-guide/';
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wc-hide-notice', 'paypal_migration_completed' ),
			'woocommerce_hide_notices_nonce',
			'_wc_notice_nonce'
		);
		$message     = sprintf(
			/* translators: 1: opening <a> tag, 2: closing </a> tag */
			esc_html__( 'WooCommerce has upgraded your PayPal integration from PayPal Standard to PayPal Payments (PPCP), for a more reliable and modern checkout experience. If you do not prefer the upgraded integration in WooCommerce, we recommend switching to %1$sPayPal Payments%2$s extension.', 'woocommerce' ),
			'<a href="' . esc_url( $doc_url ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>',
		);

		$notice_html = '<div class="notice notice-warning is-dismissible">'
			. '<a class="woocommerce-message-close notice-dismiss" style="text-decoration: none;" href="' . esc_url( $dismiss_url ) . '"></a>'
			. '<p>' . $message . '</p>'
			. '</div>';

		echo wp_kses_post( $notice_html );
	}

	/**
	 * Add notice warning about the migration to PayPal Payments on the Payments settings page.
	 *
	 * @return void
	 */
	public function add_paypal_migration_notice_on_payments_settings_page() {
		global $current_tab, $current_section;
		$is_payments_settings_page = 'woocommerce_page_wc-settings' === get_current_screen()->id && 'checkout' === $current_tab && empty( $current_section );

		// Only add the notice from this callback on the payments settings page.
		if ( ! $is_payments_settings_page ) {
			return;
		}
		$this->add_paypal_migration_notice();
	}

	/**
	 * Check if the installation notice has been dismissed.
	 *
	 * @return bool
	 */
	protected static function paypal_migration_notice_dismissed() {
		return get_user_meta( get_current_user_id(), 'dismissed_paypal_migration_completed_notice', true );
	}
}
