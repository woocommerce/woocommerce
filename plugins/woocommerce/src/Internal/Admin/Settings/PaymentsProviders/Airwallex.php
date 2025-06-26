<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Airwallex payment gateway provider class.
 *
 * This class handles all the custom logic for the Airwallex payment gateway provider.
 */
class Airwallex extends PaymentGateway {

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
		return $this->is_airwallex_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
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
			if ( class_exists( '\Airwallex\Services\Util' ) &&
				is_callable( '\Airwallex\Services\Util::getClientId' ) &&
				is_callable( '\Airwallex\Services\Util::getApiKey' ) &&
				is_callable( '\Airwallex\Services\Util::getWebhookSecret' ) ) {

				return ! empty( \Airwallex\Services\Util::getClientId() ) &&
					! empty( \Airwallex\Services\Util::getApiKey() ) &&
					! empty( \Airwallex\Services\Util::getWebhookSecret() );
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
		return $this->is_airwallex_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Check if the Airwallex payment gateway is in sandbox mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_airwallex_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			if ( class_exists( '\Airwallex\Services\Util' ) &&
				is_callable( '\Airwallex\Services\Util::getEnvironment' ) ) {

				return 'demo' === \Airwallex\Services\Util::getEnvironment();
			}
		} catch ( \Throwable $e ) {
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
