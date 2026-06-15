<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsGatewayRegistry;
use Automattic\WooCommerce\Internal\Payments\NativeWooPaymentsGateway;
use WC_Unit_Test_Case;

/**
 * Tests for the NativePaymentsGatewayRegistry class.
 */
class NativePaymentsGatewayRegistryTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_payment_gateways' );
		parent::tearDown();
	}

	/**
	 * @testdox Should not register the native gateway when native runtime does not own the site.
	 */
	public function test_does_not_register_when_native_runtime_does_not_own_site(): void {
		$sut = new NativePaymentsGatewayRegistry();
		$sut->init( new StaticNativeRuntimeArbiter( false ), new StaticWooPaymentsProvider( true ) );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_payment_gateways', array( $sut, 'register_gateway' ) ) );
	}

	/**
	 * @testdox Should not register the native gateway when the provider cannot process payments.
	 */
	public function test_does_not_register_when_provider_cannot_process_payments(): void {
		$sut = new NativePaymentsGatewayRegistry();
		$sut->init( new StaticNativeRuntimeArbiter( true ), new StaticWooPaymentsProvider( false ) );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_payment_gateways', array( $sut, 'register_gateway' ) ) );
	}

	/**
	 * @testdox Should register the native gateway when native runtime owns the site.
	 */
	public function test_registers_when_native_runtime_owns_site(): void {
		$sut = new NativePaymentsGatewayRegistry();
		$sut->init( new StaticNativeRuntimeArbiter( true ), new StaticWooPaymentsProvider( true ) );

		$sut->register();

		$this->assertSame( 10, has_filter( 'woocommerce_payment_gateways', array( $sut, 'register_gateway' ) ) );
			/**
			 * Filters payment gateways registered with WooCommerce.
			 *
			 * @since 11.0.0
			 *
			 * @param array<int,mixed> $gateways Registered payment gateways.
			 */
			$this->assertContains( NativeWooPaymentsGateway::class, apply_filters( 'woocommerce_payment_gateways', array() ) );
	}

	/**
	 * @testdox Should not duplicate the native gateway class.
	 */
	public function test_register_gateway_does_not_duplicate_gateway_class(): void {
		$sut = new NativePaymentsGatewayRegistry();
		$sut->init( new StaticNativeRuntimeArbiter( true ), new StaticWooPaymentsProvider( true ) );

		$gateways = $sut->register_gateway( array( NativeWooPaymentsGateway::class ) );

		$this->assertSame( array( NativeWooPaymentsGateway::class ), $gateways );
	}
}
