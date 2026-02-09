<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * AmazonPay payment gateway provider class.
 *
 * This class handles all the custom logic for the AmazonPay payment gateway provider.
 */
class AmazonPay extends PaymentGateway {

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
		return $this->is_amazon_pay_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in dev mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in dev mode, false otherwise.
	 */
	public function is_in_dev_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_amazon_pay_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_dev_mode( $payment_gateway );
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
		return $this->is_amazon_pay_onboarded( $payment_gateway ) ?? parent::is_account_connected( $payment_gateway );
	}

	/**
	 * Check if the payment gateway has completed the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway has completed the onboarding process, false otherwise.
	 *              If the payment gateway does not provide the information,
	 *              it will infer it from having a connected account.
	 */
	public function is_onboarding_completed( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_amazon_pay_onboarded( $payment_gateway ) ?? parent::is_onboarding_completed( $payment_gateway );
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
		return $this->is_amazon_pay_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Check if the AmazonPay payment gateway is in sandbox mode.
	 *
	 * For AmazonPay, there are two different environments: sandbox and production.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_amazon_pay_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			if ( class_exists( '\WC_Amazon_Payments_Advanced_API' ) &&
				is_callable( '\WC_Amazon_Payments_Advanced_API::get_settings' ) ) {

				$settings = \WC_Amazon_Payments_Advanced_API::get_settings();
				if ( isset( $settings['sandbox'] ) ) {
					return wc_string_to_bool( $settings['sandbox'] );
				}
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

	/**
	 * Check if the AmazonPay payment gateway is onboarded.
	 *
	 * For AmazonPay, there are two different environments: sandbox and production.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is onboarded, false otherwise.
	 *               Null if we failed to determine the onboarding status.
	 */
	private function is_amazon_pay_onboarded( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			if ( class_exists( '\WC_Amazon_Payments_Advanced_API' ) &&
				is_callable( '\WC_Amazon_Payments_Advanced_API::validate_api_settings' ) ) {

				return true === \WC_Amazon_Payments_Advanced_API::validate_api_settings();
			}
		} catch ( \Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is onboarded: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// Let the caller know that we couldn't determine the onboarding status.
		return null;
	}
}
