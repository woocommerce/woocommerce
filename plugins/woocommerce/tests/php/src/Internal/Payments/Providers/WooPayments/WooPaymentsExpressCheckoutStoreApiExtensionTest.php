<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressCheckoutService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressCheckoutStoreApiExtension;
use Automattic\WooCommerce\Tests\Internal\Payments\StaticNativeRuntimeArbiter;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsExpressCheckoutStoreApiExtension class.
 */
class WooPaymentsExpressCheckoutStoreApiExtensionTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var WooPaymentsExpressCheckoutStoreApiExtension|null
	 */
	private ?WooPaymentsExpressCheckoutStoreApiExtension $sut = null;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->sut instanceof WooPaymentsExpressCheckoutStoreApiExtension ) {
			remove_action( 'woocommerce_blocks_loaded', array( $this->sut, 'register_store_api_extension' ) );
		}

		delete_option( 'woocommerce_currency' );
		parent::tearDown();
	}

	/**
	 * @testdox Should register Store API extension hook only when native WooPayments owns the runtime.
	 */
	public function test_registers_blocks_loaded_hook_only_when_native_owns_runtime(): void {
		$this->sut = $this->create_extension( false, array( 'payment_request' ) );
		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_blocks_loaded', array( $this->sut, 'register_store_api_extension' ) ) );

		$this->sut = $this->create_extension( true, array( 'payment_request' ) );
		$this->sut->register();

		$this->assertSame( 10, has_action( 'woocommerce_blocks_loaded', array( $this->sut, 'register_store_api_extension' ) ) );
	}

	/**
	 * @testdox Should expose cart-aware express checkout methods through the Store API extension data.
	 */
	public function test_get_cart_extension_data_returns_cart_methods_for_current_currency(): void {
		update_option( 'woocommerce_currency', 'EUR' );

		$this->sut = $this->create_extension( true, array( 'payment_request', 'amazon_pay' ), 'EUR' );

		$this->assertSame(
			array(
				'express_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
			),
			$this->sut->get_cart_extension_data()
		);
	}

	/**
	 * @testdox Should expose currency-fresh methods without limiting them to the cart location.
	 */
	public function test_get_cart_extension_data_is_not_limited_to_cart_location_settings(): void {
		update_option( 'woocommerce_currency', 'EUR' );

		$this->sut = $this->create_extension(
			true,
			array(),
			'EUR',
			static function ( string $context ): array {
				return 'checkout' === $context ? array( 'payment_request' ) : array();
			}
		);

		$this->assertSame(
			array(
				'express_checkout_methods' => array( 'payment_request' ),
			),
			$this->sut->get_cart_extension_data()
		);
	}

	/**
	 * @testdox Should expose the reference-compatible Store API extension schema.
	 */
	public function test_get_cart_extension_schema_returns_express_checkout_methods_schema(): void {
		$this->sut = $this->create_extension( true, array( 'payment_request' ) );

		$this->assertSame(
			array(
				'express_checkout_methods' => array(
					'description' => __( 'Express Checkout methods available for the cart\'s current currency.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
					),
				),
			),
			$this->sut->get_cart_extension_schema()
		);
	}

	/**
	 * Create the System Under Test.
	 *
	 * @param bool              $native_register Whether native should register.
	 * @param array<int,string> $methods         Express checkout methods to return.
	 * @param string            $currency        Expected cart currency.
	 * @param callable|null     $method_resolver Optional context-aware method resolver.
	 * @return WooPaymentsExpressCheckoutStoreApiExtension
	 */
	private function create_extension( bool $native_register, array $methods, string $currency = 'USD', ?callable $method_resolver = null ): WooPaymentsExpressCheckoutStoreApiExtension {
		$express_checkout_service = $this->getMockBuilder( WooPaymentsExpressCheckoutService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_enabled_methods_for_context' ) )
			->getMock();
		$express_checkout_service
			->expects( $this->any() )
			->method( 'get_enabled_methods_for_context' )
			->willReturnCallback(
				function ( string $context, string $requested_currency ) use ( $methods, $currency, $method_resolver ): array {
					$this->assertSame( $currency, $requested_currency );

					if ( null !== $method_resolver ) {
						return $method_resolver( $context );
					}

					return $methods;
				}
			);

		$extension = new WooPaymentsExpressCheckoutStoreApiExtension();
		$extension->init( new StaticNativeRuntimeArbiter( $native_register ), $express_checkout_service );

		return $extension;
	}
}
