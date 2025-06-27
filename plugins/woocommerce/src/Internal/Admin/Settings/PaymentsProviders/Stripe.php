<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

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
}
