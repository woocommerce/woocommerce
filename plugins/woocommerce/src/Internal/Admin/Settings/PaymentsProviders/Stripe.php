<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Stripe payment gateway provider class.
 *
 * This class handles all the custom logic for the Stripe payment gateway provider.
 */
class Stripe extends PaymentGateway {

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
			if ( class_exists( '\WC_Stripe_Mode' ) &&
				is_callable( '\WC_Stripe_Mode::is_test' ) ) {

				return wc_string_to_bool( \WC_Stripe_Mode::is_test() );
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
		return false;
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
			if ( class_exists( '\WC_Stripe' ) && is_callable( '\WC_Stripe::get_instance' ) ) {
				$stripe = \WC_Stripe::get_instance();
				if ( is_object( $stripe ) && isset( $stripe->account ) &&
					class_exists( '\WC_Stripe_Account' ) &&
					defined( '\WC_Stripe_Account::STATUS_NO_ACCOUNT' ) &&
					$stripe->account instanceof \WC_Stripe_Account &&
					is_callable( array( $stripe->account, 'get_account_status' ) ) ) {

					return \WC_Stripe_Account::STATUS_NO_ACCOUNT !== $stripe->account->get_account_status();
				}
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway has account connected: ' . $e->getMessage(),
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
	 * Check if the payment gateway has started the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway has started the onboarding process, false otherwise.
	 *              If the payment gateway does not provide the information,
	 *              it will infer it from having a connected account.
	 */
	public function is_onboarding_started( WC_Payment_Gateway $payment_gateway ): bool {
		// Fall back to inferring this from having a connected account.
		return $this->is_account_connected( $payment_gateway );
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
		// Sanity check: If the onboarding has not started, it cannot be completed.
		if ( ! $this->is_onboarding_started( $payment_gateway ) ) {
			return false;
		}

		// Fall back to inferring this from having a connected account.
		return $this->is_account_connected( $payment_gateway );
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
		try {
			if ( class_exists( '\WC_Stripe' ) && is_callable( '\WC_Stripe::get_instance' ) ) {
				$stripe = \WC_Stripe::get_instance();
				if ( is_object( $stripe ) && isset( $stripe->connect ) &&
					class_exists( '\WC_Stripe_Connect' ) &&
					$stripe->connect instanceof \WC_Stripe_Connect &&
					is_callable( array( $stripe->connect, 'is_connected' ) ) ) {

					return $stripe->connect->is_connected( 'test' )
						&& ! $stripe->connect->is_connected( 'live' );
				}
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in test mode onboarding: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Get the settings URL for a payment gateway.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The settings URL for the payment gateway.
	 */
	public function get_settings_url( WC_Payment_Gateway $payment_gateway ): string {
		return Utils::wc_payments_settings_url(
			null,
			array(
				'section' => strtolower( $payment_gateway->id ),
				'from'    => Payments::FROM_PAYMENTS_SETTINGS,
			)
		);
	}

	/**
	 * Get the onboarding URL for the payment gateway.
	 *
	 * This URL should start or continue the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $return_url      Optional. The URL to return to after onboarding.
	 *                                            This will likely get attached to the onboarding URL.
	 *
	 * @return string The onboarding URL for the payment gateway.
	 */
	public function get_onboarding_url( WC_Payment_Gateway $payment_gateway, string $return_url = '' ): string {
		// Fall back to pointing users to the payment gateway settings page to handle onboarding.
		return $this->get_settings_url( $payment_gateway );
	}

	/**
	 * Try and determine a list of recommended payment methods for a payment gateway.
	 *
	 * This data is not always available, and it is up to the payment gateway to provide it.
	 * This is not a definitive list of payment methods that the gateway supports.
	 * The data is aimed at helping the user understand what payment methods are recommended for the gateway
	 * and potentially help them make a decision on which payment methods to enable.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $country_code    Optional. The country code for which to get recommended payment methods.
	 *                                            This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The recommended payment methods list for the payment gateway.
	 *               Empty array if there are none.
	 */
	public function get_recommended_payment_methods( WC_Payment_Gateway $payment_gateway, string $country_code = '' ): array {
		return array();
	}
}
