<?php
/**
 * NativePaymentsGatewayRegistry class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native payments gateways when the native runtime owns the site.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class NativePaymentsGatewayRegistry implements RegisterHooksInterface {

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * WooPayments provider.
	 *
	 * @var Providers\WooPayments\WooPaymentsProvider
	 */
	private Providers\WooPayments\WooPaymentsProvider $provider;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter              $arbiter  Runtime owner arbiter.
	 * @param Providers\WooPayments\WooPaymentsProvider $provider WooPayments provider.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, Providers\WooPayments\WooPaymentsProvider $provider ): void {
		$this->arbiter  = $arbiter;
		$this->provider = $provider;
	}

	/**
	 * Register gateway hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) ) ) {
			add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		}
	}

	/**
	 * Add the native WooPayments gateway class.
	 *
	 * @param array<int|string,mixed> $gateways Registered gateway classes or instances.
	 * @return array<int|string,mixed>
	 */
	public function register_gateway( array $gateways ): array {
		if ( ! $this->arbiter->should_native_register() || ! $this->provider->can_process_payments() ) {
			return $gateways;
		}

		if ( in_array( NativeWooPaymentsGateway::class, $gateways, true ) ) {
			return $gateways;
		}

		$gateways[] = NativeWooPaymentsGateway::class;

		return $gateways;
	}
}
