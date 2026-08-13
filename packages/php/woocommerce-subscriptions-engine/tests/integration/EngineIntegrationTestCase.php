<?php
/**
 * Base test case for engine integration tests.
 *
 * Schema is installed once in the bootstrap; WP_UnitTestCase wraps each test in
 * a transaction and rolls it back, so test rows do not leak between tests.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;

/**
 * Engine integration test case.
 */
abstract class EngineIntegrationTestCase extends WP_UnitTestCase {

	/**
	 * Gateway ids wired with an approving scheduled-payment handler, to unhook on teardown.
	 *
	 * @var array<int, string>
	 */
	private $approved_gateways = array();

	public function tear_down(): void {
		foreach ( $this->approved_gateways as $gateway ) {
			remove_all_actions( 'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway );
		}
		$this->approved_gateways = array();

		parent::tear_down();
	}

	/**
	 * Declare `recurring` for `$gateway` and wire an inline approving handler: it marks the
	 * renewal order paid synchronously (the dummy-gateway shape), so the money-path reads a
	 * paid order immediately after the charge is attempted. Unhooked automatically on teardown.
	 *
	 * @param string $gateway Gateway id to approve charges for.
	 */
	protected function approve_charges_for( string $gateway ): void {
		GatewayCapabilities::declare( $gateway, array( GatewayCapabilities::RECURRING ) );

		add_action(
			'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway,
			static function ( $amount, $renewal_order ): void {
				unset( $amount );
				if ( $renewal_order instanceof WC_Order && $renewal_order->needs_payment() ) {
					$renewal_order->payment_complete();
				}
			},
			10,
			2
		);

		$this->approved_gateways[] = $gateway;
	}

	/**
	 * Declare `recurring` for `$gateway` and wire an inline declining handler: it moves the
	 * renewal order to `failed` (a hard decline the gateway reports synchronously), so the
	 * money-path settles the cycle `failed` - as opposed to a gateway that leaves the order
	 * un-paid-and-not-failed, which is treated as an async charge still awaiting confirmation.
	 * Unhooked automatically on teardown.
	 *
	 * @param string $gateway Gateway id to fail charges for.
	 */
	protected function fail_charges_for( string $gateway ): void {
		GatewayCapabilities::declare( $gateway, array( GatewayCapabilities::RECURRING ) );

		add_action(
			'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway,
			static function ( $amount, $renewal_order ): void {
				unset( $amount );
				if ( $renewal_order instanceof WC_Order ) {
					$renewal_order->update_status( 'failed', 'Gateway declined the recurring charge.' );
				}
			},
			10,
			2
		);

		$this->approved_gateways[] = $gateway;
	}
}
