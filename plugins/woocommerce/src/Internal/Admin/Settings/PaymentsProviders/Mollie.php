<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Mollie payment gateway provider class.
 *
 * This class handles all the custom logic for the Mollie payment gateway provider.
 */
class Mollie extends PaymentGateway {

	/**
	 * Get the settings URL for a payment gateway.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The settings URL for the payment gateway.
	 */
	public function get_settings_url( WC_Payment_Gateway $payment_gateway ): string {
		// Don't target any section because there are none to target when Mollie is not connected.
		if ( 'mollie_stand_in' === $payment_gateway->id ) {
			return $this->get_custom_settings_url();
		}

		// Target the payment methods section when the gateway is connected.
		return $this->get_custom_settings_url( 'mollie_payment_methods' );
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
			$sandbox_mode = $this->is_mollie_in_sandbox_mode( $payment_gateway );
			// Let null results bubble up to the parent class.
			if ( true === $sandbox_mode ) {
				// If Mollie is in sandbox mode, we consider the account connected if the test API key is set.
				return ! empty( get_option( 'mollie-payments-for-woocommerce_test_api_key', '' ) );
			} elseif ( false === $sandbox_mode ) {
				// In production mode, we check the live API key.
				return ! empty( get_option( 'mollie-payments-for-woocommerce_live_api_key', '' ) );
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
	 * Determine if the payment gateway is in test mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mollie_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
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
		return $this->is_mollie_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Determine if at least a Mollie gateway is registered.
	 *
	 * @param array $payment_gateways The payment gateways objects.
	 *
	 * @return bool True if at least a Mollie gateway is registered, false otherwise.
	 */
	public function is_gateway_registered( array $payment_gateways ): bool {
		$mollie_gateways = array_filter(
			$payment_gateways,
			function ( $gateway ) {
				return str_starts_with( $gateway->id, 'mollie_wc_gateway_' );
			}
		);

		return ! empty( $mollie_gateways );
	}

	/**
	 * Get the pseudo Mollie gateway object.
	 *
	 * @param array $suggestion The suggestion data.
	 *
	 * @return PseudoWCPaymentGateway The pseudo gateway object.
	 */
	public function get_pseudo_gateway( array $suggestion ): PseudoWCPaymentGateway {
		// We will generate a generic gateway to represent Mollie in the settings page.
		// The generic gateway's state will be not enabled, not connected, and not onboarded.
		// The presentational details will be minimal, letting the suggestion provide most of the information.
		return new PseudoWCPaymentGateway(
			'mollie_stand_in',
			array(
				'method_title'         => $suggestion['title'],
				'method_description'   => $suggestion['description'],
				'enabled'              => false,
				'needs_setup'          => true,
				'test_mode'            => false,
				'dev_mode'             => false,
				'account_connected'    => false,
				'onboarding_started'   => false,
				'onboarding_completed' => false,
				'settings_url'         => $this->get_custom_settings_url(),
				'plugin_slug'          => $suggestion['plugin']['slug'],
				'plugin_file'          => $suggestion['plugin']['file'],
			),
		);
	}

	/**
	 * Get the URL to the custom settings page for Mollie.
	 *
	 * @param string $section Optional. The section to navigate to.
	 *
	 * @return string The URL to the custom settings page for Mollie.
	 */
	private function get_custom_settings_url( string $section = '' ): string {
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=mollie_settings' );

		if ( ! empty( $section ) ) {
			$settings_url = add_query_arg( 'section', $section, $settings_url );
		}

		return $settings_url;
	}

	/**
	 * Check if the Mollie payment gateway is in sandbox mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_mollie_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			// Unfortunately, Mollie does not provide a standard way to determine if the gateway is in sandbox mode.
			return filter_var( get_option( 'mollie-payments-for-woocommerce_test_mode_enabled', 'yes' ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
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
