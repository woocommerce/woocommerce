<?php
/**
 * CorePayPalGatewayTrait file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\RestApi\UnitTests;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

/**
 * Trait CorePayPalGatewayTrait.
 *
 * Controls the WC core PayPal gateway for tests that need a real, non-suggested
 * payment gateway present — for example to satisfy the enabled-ecommerce-gateway
 * rule in the payment provider and suggestion services.
 *
 * The using class must expose the payment providers service it asserts against,
 * because that service caches gateway details and would otherwise keep serving
 * the state from before the gateway changed.
 */
trait CorePayPalGatewayTrait {

	/**
	 * The payment providers service whose cache these helpers must invalidate.
	 *
	 * @return PaymentsProviders
	 */
	abstract protected function get_payments_providers_service(): PaymentsProviders;

	/**
	 * Load the WC core PayPal gateway without enabling it.
	 */
	protected function load_core_paypal_pg(): void {
		$this->set_core_paypal_pg_enabled( false );
	}

	/**
	 * Load and enable the WC core PayPal gateway.
	 */
	protected function enable_core_paypal_pg(): void {
		$this->set_core_paypal_pg_enabled( true );
	}

	/**
	 * Clean up the WC core PayPal gateway.
	 */
	protected function unload_core_paypal_pg(): void {
		delete_option( 'woocommerce_paypal_settings' );
		delete_option( 'woocommerce_currency' );

		$this->get_payments_providers_service()->clear_cache();
	}

	/**
	 * Write the core PayPal gateway settings and reload the gateway list.
	 *
	 * @param bool $enabled Whether the gateway should be enabled.
	 */
	private function set_core_paypal_pg_enabled( bool $enabled ): void {
		update_option(
			'woocommerce_paypal_settings',
			array(
				'_should_load' => 'yes',
				'enabled'      => $enabled ? 'yes' : 'no',
			)
		);
		// Make sure the store currency is supported by the gateway.
		update_option( 'woocommerce_currency', 'USD' );

		self::reload_payment_gateways();

		// Clear cached provider data to pick up the new gateway details.
		$this->get_payments_providers_service()->clear_cache();
	}

	/**
	 * Rebuild the loaded payment gateways from the current options.
	 *
	 * WC_Payment_Gateways::init() adds to the loaded list rather than replacing
	 * it, so the list has to be emptied first for a gateway that is no longer
	 * loadable to actually disappear.
	 */
	protected static function reload_payment_gateways(): void {
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();
	}
}
