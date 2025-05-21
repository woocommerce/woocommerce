<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;

use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * PayPal payment gateway provider class.
 *
 * This class handles all the custom logic for the PayPal payment gateway provider.
 */
class PayPal extends PaymentGateway {

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
		$is_in_sandbox_mode = $this->is_paypal_in_sandbox_mode();
		if ( ! is_null( $is_in_sandbox_mode ) ) {
			return $is_in_sandbox_mode;
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
		$is_in_sandbox_mode = $this->is_paypal_in_sandbox_mode();
		if ( ! is_null( $is_in_sandbox_mode ) ) {
			return $is_in_sandbox_mode;
		}

		return parent::is_in_dev_mode( $payment_gateway );
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
		$is_onboarded = $this->is_paypal_onboarded();
		if ( ! is_null( $is_onboarded ) ) {
			return $is_onboarded;
		}

		return parent::is_account_connected( $payment_gateway );
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
		$is_onboarded = $this->is_paypal_onboarded();
		if ( ! is_null( $is_onboarded ) ) {
			return $is_onboarded;
		}

		return parent::is_onboarding_completed( $payment_gateway );
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
		$is_in_sandbox_mode = $this->is_paypal_in_sandbox_mode();
		if ( ! is_null( $is_in_sandbox_mode ) ) {
			return $is_in_sandbox_mode;
		}

		return parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Check if the PayPal payment gateway is in sandbox mode.
	 *
	 * For PayPal, there are two different environments: sandbox and production.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_paypal_in_sandbox_mode(): ?bool {
		if ( class_exists( '\WooCommerce\PayPalCommerce\PPCP' ) &&
			is_callable( '\WooCommerce\PayPalCommerce\PPCP::container' ) ) {
			try {
				$container = \WooCommerce\PayPalCommerce\PPCP::container();

				if ( $container->has( 'settings.connection-state' ) ) {
					$state = $container->get( 'settings.connection-state' );

					return $state->is_sandbox();
				}

				// Backwards compatibility with pre 3.0.0 (deprecated).
				if ( $container->has( 'onboarding.environment' ) &&
					defined( '\WooCommerce\PayPalCommerce\Onboarding\Environment::SANDBOX' ) ) {
					$environment         = $container->get( 'onboarding.environment' );
					$current_environment = $environment->current_environment();

					return \WooCommerce\PayPalCommerce\Onboarding\Environment::SANDBOX === $current_environment;
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Ignore any exceptions.
			}
		}

		// Let the caller know that we couldn't determine the environment.
		return null;
	}

	/**
	 * Check if the PayPal payment gateway is onboarded.
	 *
	 * @return ?bool True if the payment gateway is onboarded, false otherwise.
	 *               Null if we failed to determine the onboarding status.
	 */
	private function is_paypal_onboarded(): ?bool {
		if ( class_exists( '\WooCommerce\PayPalCommerce\PPCP' ) &&
			is_callable( '\WooCommerce\PayPalCommerce\PPCP::container' ) ) {
			try {
				$container = \WooCommerce\PayPalCommerce\PPCP::container();

				if ( $container->has( 'settings.connection-state' ) ) {
					$state = $container->get( 'settings.connection-state' );

					return $state->is_connected();
				}

				// Backwards compatibility with pre 3.0.0 (deprecated).
				if ( $container->has( 'onboarding.state' ) &&
					defined( '\WooCommerce\PayPalCommerce\Onboarding\State::STATE_ONBOARDED' ) ) {
					$state = $container->get( 'onboarding.state' );

					return $state->current_state() >= \WooCommerce\PayPalCommerce\Onboarding\State::STATE_ONBOARDED;
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Ignore any exceptions.
			}
		}

		// Let the caller know that we couldn't determine the onboarding status.
		return null;
	}
}
