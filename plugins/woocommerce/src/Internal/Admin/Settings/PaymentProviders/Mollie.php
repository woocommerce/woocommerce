<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;

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
	 * Determine if the payment gateway is in test mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		$test_mode_enabled = get_option( 'mollie-payments-for-woocommerce_test_mode_enabled', true );
		$test_key_entered  = get_option( 'mollie-payments-for-woocommerce_test_api_key', true );

		return filter_var( $test_mode_enabled, FILTER_VALIDATE_BOOLEAN ) && ! empty( $test_key_entered );
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
}
