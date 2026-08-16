<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * HelioPay payment gateway provider class.
 *
 * This class handles all the custom logic for the HelioPay payment gateway provider.
 */
class HelioPay extends PaymentGateway {

	/**
	 * Try to determine if the payment gateway is in test mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			if ( defined( 'HELIO_DEVNET_ENABLED' ) ) {
				return wc_string_to_bool( $payment_gateway->get_option( \HELIO_DEVNET_ENABLED ) );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in test mode: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return parent::is_in_test_mode( $payment_gateway );
	}

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
			if ( $this->is_in_test_mode( $payment_gateway ) ) {
				if ( defined( 'HELIO_API_KEY_DEVNET' ) &&
					defined( 'HELIO_API_SECRET_DEVNET' ) ) {

					return ! empty( $payment_gateway->get_option( \HELIO_API_KEY_DEVNET ) ) && ! empty( $payment_gateway->get_option( \HELIO_API_SECRET_DEVNET ) );
				}
			} elseif ( defined( 'HELIO_API_KEY_MAINNET' ) &&
					defined( 'HELIO_API_SECRET_MAINNET' ) ) {

					return ! empty( $payment_gateway->get_option( \HELIO_API_KEY_MAINNET ) ) && ! empty( $payment_gateway->get_option( \HELIO_API_SECRET_MAINNET ) );
			}
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
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox or test-drive).
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		// Test mode is actually sandbox mode for HelioPay, affecting the API credentials used.
		return $this->is_in_test_mode( $payment_gateway );
	}
}
