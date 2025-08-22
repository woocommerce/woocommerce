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
	}

	/**
	 * Add notice warning about the migration to PayPal Payments.
	 *
	 * @return void
	 */
	public function add_paypal_migration_notice() {
		// Show only to users who can manage the site.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip if the merchant is not eligible for migration.
		if ( ! WC_Gateway_Paypal_Helper::is_orders_v2_migration_eligible() ) {
			return;
		}

		// Skip if the notice has been dismissed.
		if ( get_option( 'show_woocommerce_paypal_migration_notice' ) === 'no' ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p>WooCommerce will automatically upgrade your PayPal integration from PayPal Standard to PayPal Payments (PPCP) in version 10.3.0, for a more reliable and modern checkout experience. If you prefer not to migrate, we recommed upgrading to <a href="https://woocommerce.com/document/woocommerce-paypal-payments/paypal-payments-upgrade-guide/" target="_blank">PayPal Payments</a> extension.</p>';
		echo '</div>';
	}
}
