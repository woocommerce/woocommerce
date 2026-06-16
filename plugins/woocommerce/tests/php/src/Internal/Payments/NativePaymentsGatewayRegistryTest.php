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
	 * @testdox Should not add the native gateway when the provider cannot process payments.
	 */
	public function test_does_not_add_gateway_when_provider_cannot_process_payments(): void {
		$sut = new NativePaymentsGatewayRegistry();
		$sut->init( new StaticNativeRuntimeArbiter( true ), new StaticWooPaymentsProvider( false ) );

		$sut->register();

		$this->assertSame( 10, has_filter( 'woocommerce_payment_gateways', array( $sut, 'register_gateway' ) ) );
		$this->assertSame( array(), $this->apply_payment_gateways_filter() );
	}

	/**
	 * @testdox Should defer provider readiness until the payment gateways filter runs.
	 */
	public function test_defers_provider_readiness_until_payment_gateways_filter_runs(): void {
		$provider = new class() extends StaticWooPaymentsProvider {
			/**
			 * Number of provider readiness checks.
			 *
			 * @var int
			 */
			public int $can_process_payments_calls = 0;

			/**
			 * Current provider readiness.
			 *
			 * @var bool
			 */
			private bool $can_process_payments = false;

			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Set current provider readiness.
			 *
			 * @param bool $can_process_payments Whether the provider can process payments.
			 */
			public function set_can_process_payments( bool $can_process_payments ): void {
				$this->can_process_payments = $can_process_payments;
			}

			/**
			 * Tell whether WooPayments can currently process native money operations.
			 *
			 * @return bool
			 */
			public function can_process_payments(): bool {
				++$this->can_process_payments_calls;

				return $this->can_process_payments;
			}
		};
		$sut      = new NativePaymentsGatewayRegistry();
		$sut->init( new StaticNativeRuntimeArbiter( true ), $provider );

		$sut->register();

		$this->assertSame( 0, $provider->can_process_payments_calls, 'Registry setup must not consult provider readiness before WooCommerce finishes bootstrapping transport dependencies.' );

		$provider->set_can_process_payments( true );

		$this->assertContains( NativeWooPaymentsGateway::class, $this->apply_payment_gateways_filter() );
		$this->assertSame( 1, $provider->can_process_payments_calls );
	}

	/**
	 * @testdox Should register the native gateway when native runtime owns the site.
	 */
	public function test_registers_when_native_runtime_owns_site(): void {
		$sut = new NativePaymentsGatewayRegistry();
		$sut->init( new StaticNativeRuntimeArbiter( true ), new StaticWooPaymentsProvider( true ) );

		$sut->register();

		$this->assertSame( 10, has_filter( 'woocommerce_payment_gateways', array( $sut, 'register_gateway' ) ) );

		$this->assertContains( NativeWooPaymentsGateway::class, $this->apply_payment_gateways_filter() );
	}

	/**
	 * Apply the payment gateways filter.
	 *
	 * @param array<int,mixed> $gateways Registered payment gateways.
	 * @return array<int,mixed>
	 */
	private function apply_payment_gateways_filter( array $gateways = array() ): array {
		/**
		 * Filters payment gateways registered with WooCommerce.
		 *
		 * @since 11.0.0
		 *
		 * @param array<int,mixed> $gateways Registered payment gateways.
		 */
		return apply_filters( 'woocommerce_payment_gateways', $gateways );
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
