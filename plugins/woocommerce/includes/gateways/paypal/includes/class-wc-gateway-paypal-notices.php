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
	 * Constructor.
	 */
	public function __construct() {
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

		// Skip if the gateway is not available or the merchant is not eligible for migration.
		if ( ! WC_Gateway_Paypal_Helper::is_paypal_gateway_available() || ! WC_Gateway_Paypal_Helper::is_orders_v2_migration_eligible() ) {
			return;
		}

		// Skip if the notice has been dismissed.
		if ( $this->paypal_migration_notice_dismissed() ) {
			return;
		}

		$doc_url     = 'https://woocommerce.com/document/woocommerce-paypal-payments/paypal-payments-upgrade-guide/';
		$release_url = 'https://developer.woocommerce.com/release-calendar/';
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wc-hide-notice', 'paypal_migration' ),
			'woocommerce_hide_notices_nonce',
			'_wc_notice_nonce'
		);
		$message     = sprintf(
			/* translators: 1: opening <a> tag, 2: closing </a> tag, 3: opening <a> tag, 4: closing </a> tag */
			esc_html__( 'WooCommerce will automatically upgrade your PayPal integration from PayPal Standard to PayPal Payments (PPCP) in version %1$s10.3.0%2$s, for a more reliable and modern checkout experience. If you prefer not to migrate, we recommend switching to %3$sPayPal Payments%4$s extension.', 'woocommerce' ),
			'<a href="' . esc_url( $release_url ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>',
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
		return get_user_meta( get_current_user_id(), 'dismissed_paypal_migration_notice', true );
	}
}
