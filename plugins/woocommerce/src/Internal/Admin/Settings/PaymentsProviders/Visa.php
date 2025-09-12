<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Visa payment gateway provider class.
 *
 * This class handles all the custom logic for the Visa payment gateway provider.
 */
class Visa extends PaymentGateway {

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
			if ( is_callable( array( $payment_gateway, 'get_config_settings' ) ) &&
				defined( 'VISA_ACCEPTANCE_ENVIRONMENT_TEST' ) &&
				defined( 'VISA_ACCEPTANCE_ENVIRONMENT_PRODUCTION' ) ) {
				$settings = $payment_gateway->get_config_settings();

				return is_array( $settings ) && isset( $settings['environment'] ) &&
						( ( \VISA_ACCEPTANCE_ENVIRONMENT_TEST === $settings['environment'] &&
						! empty( $settings['test_merchant_id'] ) &&
						! empty( $settings['test_api_key'] ) &&
						! empty( $settings['test_api_shared_secret'] ) ) ||
						( \VISA_ACCEPTANCE_ENVIRONMENT_PRODUCTION === $settings['environment'] &&
						! empty( $settings['merchant_id'] ) &&
						! empty( $settings['api_key'] ) &&
						! empty( $settings['api_shared_secret'] ) ) );
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
	 * Try to determine if the payment gateway is in test mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_visa_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in dev mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in dev mode, false otherwise.
	 */
	public function is_in_dev_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_visa_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_dev_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox or test-drive).
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_visa_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Check if the Visa payment gateway is in test/sandbox mode.
	 *
	 * There are two different environments: test/sandbox and production.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_visa_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			if ( is_callable( array( $payment_gateway, 'get_config_settings' ) ) &&
				defined( 'VISA_ACCEPTANCE_ENVIRONMENT_TEST' ) &&
				defined( 'VISA_ACCEPTANCE_ENVIRONMENT_PRODUCTION' ) ) {
				$settings = $payment_gateway->get_config_settings();

				if ( is_array( $settings ) && isset( $settings['environment'] ) ) {
					if ( \VISA_ACCEPTANCE_ENVIRONMENT_TEST === $settings['environment'] ) {
						return true;
					}
					if ( \VISA_ACCEPTANCE_ENVIRONMENT_PRODUCTION === $settings['environment'] ) {
						return false;
					}
				}
			}
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
