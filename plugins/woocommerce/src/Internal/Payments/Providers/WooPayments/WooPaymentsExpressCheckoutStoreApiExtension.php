<?php
/**
 * WooPaymentsExpressCheckoutStoreApiExtension class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * Native WooPayments express checkout Store API cart extension.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsExpressCheckoutStoreApiExtension implements RegisterHooksInterface {

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Express checkout service.
	 *
	 * @var WooPaymentsExpressCheckoutService
	 */
	private WooPaymentsExpressCheckoutService $express_checkout_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter      $arbiter                  Runtime owner arbiter.
	 * @param WooPaymentsExpressCheckoutService $express_checkout_service Express checkout service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsExpressCheckoutService $express_checkout_service ): void {
		$this->arbiter                  = $arbiter;
		$this->express_checkout_service = $express_checkout_service;
	}

	/**
	 * Register Store API extension hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_action( 'woocommerce_blocks_loaded', array( $this, 'register_store_api_extension' ) ) ) {
			add_action( 'woocommerce_blocks_loaded', array( $this, 'register_store_api_extension' ) );
		}
	}

	/**
	 * Register WooPayments cart extension data with the Store API.
	 */
	public function register_store_api_extension(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) || ! class_exists( CartSchema::class ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'wcpay',
				'data_callback'   => array( $this, 'get_cart_extension_data' ),
				'schema_callback' => array( $this, 'get_cart_extension_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Get WooPayments cart extension data.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function get_cart_extension_data(): array {
		return array(
			'express_checkout_methods' => $this->get_location_blind_methods_for_current_currency(),
		);
	}

	/**
	 * Get methods available for the current cart currency without applying cart-location gating.
	 *
	 * The Store API cart response can be consumed by both Cart and Checkout Blocks. The frontend intersects
	 * this currency-fresh list with the localized current-location methods before mounting wallets.
	 *
	 * @return array<int,string>
	 */
	private function get_location_blind_methods_for_current_currency(): array {
		$methods  = array();
		$currency = get_woocommerce_currency();

		foreach ( array( 'product', 'cart', 'checkout' ) as $context ) {
			$methods = array_merge(
				$methods,
				$this->express_checkout_service->get_enabled_methods_for_context( $context, $currency )
			);
		}

		return array_values( array_unique( $methods ) );
	}

	/**
	 * Get WooPayments cart extension schema.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_cart_extension_schema(): array {
		return array(
			'express_checkout_methods' => array(
				'description' => __( 'Express Checkout methods available for the cart\'s current currency.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type' => 'string',
				),
			),
		);
	}
}
