<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Helcim payment gateway provider class.
 *
 * This class handles all the custom logic for the Helcim payment gateway provider.
 *
 * @internal
 */
class Helcim extends PaymentGateway {

	/**
	 * Check if the payment gateway has a payments processor account connected.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway account is connected, false otherwise.
	 *              If the payment gateway does not provide the information, it will return true.
	 */
	public function is_account_connected( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			return ! wc_string_to_bool( $payment_gateway->needs_setup() );
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway has an account connected: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return parent::is_account_connected( $payment_gateway );
	}

	/**
	 * Determine if the payment gateway is in test mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_helcim_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox).
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_helcim_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Check if the Helcim payment gateway is in sandbox mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_helcim_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			// Helcim stores the active environment ('sandbox'|'live') as a gateway option,
			// read through the gateway's own get_option() so we follow whatever option key
			// and precedence the extension uses internally.
			$environment = $payment_gateway->get_option( 'environment' );
			if ( ! is_string( $environment ) ) {
				return null;
			}

			$environment = strtolower( trim( $environment ) );
			if ( 'sandbox' === $environment ) {
				return true;
			}
			if ( 'live' === $environment ) {
				return false;
			}

			return null;
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in sandbox mode: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// Let the caller know that we couldn't determine the environment.
		return null;
	}
}
