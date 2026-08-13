<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Klarna payment gateway provider class.
 *
 * This class handles all the custom logic for the Klarna payment gateway provider.
 */
class Klarna extends PaymentGateway {

	/**
	 * Check if the payment gateway needs setup.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway needs setup, false otherwise.
	 */
	public function needs_setup( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			if ( class_exists( '\KP_Settings_Page' ) &&
				is_callable( '\KP_Settings_Page::get_setting_status' ) ) {

				return ! wc_string_to_bool( \KP_Settings_Page::get_setting_status( 'credentials' ) );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway needs setup: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return parent::needs_setup( $payment_gateway );
	}
}
