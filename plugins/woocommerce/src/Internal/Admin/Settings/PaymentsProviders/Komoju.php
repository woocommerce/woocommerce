<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * KOMOJU payment gateway provider class.
 *
 * This class handles all the custom logic for the KOMOJU payment gateway provider.
 */
class Komoju extends PaymentGateway {

	/**
	 * Get the settings URL for a payment gateway.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The settings URL for the payment gateway.
	 */
	public function get_settings_url( WC_Payment_Gateway $payment_gateway ): string {
		// KOMOJU's account connection and payment method selection happen on its own
		// dedicated settings tab, not on the legacy combined gateway's settings section.
		return admin_url( 'admin.php?page=wc-settings&tab=komoju_settings' );
	}
}
