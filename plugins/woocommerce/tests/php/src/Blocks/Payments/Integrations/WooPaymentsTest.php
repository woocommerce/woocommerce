<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Payments\Integrations\WooPayments;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use WP_UnitTestCase;

/**
 * Tests for the Blocks WooPayments integration.
 */
class WooPaymentsTest extends WP_UnitTestCase {

	/**
	 * @testdox Should require checkout bridge readiness before the Blocks payment method activates.
	 */
	public function test_is_active_requires_checkout_bridge_readiness(): void {
		$asset_api = $this->getMockBuilder( AssetApi::class )
			->disableOriginalConstructor()
			->getMock();
		$bridge    = $this->getMockBuilder( WooPaymentsCheckoutBridge::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_expose_checkout_surface' ) )
			->getMock();
		$bridge->method( 'should_expose_checkout_surface' )->willReturn( false );
		$provider = $this->getMockBuilder( WooPaymentsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();
		$provider->method( 'can_process_payments' )->willReturn( true );

		$integration = new WooPayments( $asset_api, $bridge, $provider );

		$this->assertFalse( $integration->is_active() );
	}

	/**
	 * @testdox Should require provider readiness before the Blocks payment method activates.
	 */
	public function test_is_active_requires_provider_readiness(): void {
		$asset_api = $this->getMockBuilder( AssetApi::class )
			->disableOriginalConstructor()
			->getMock();
		$bridge    = $this->getMockBuilder( WooPaymentsCheckoutBridge::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_expose_checkout_surface' ) )
			->getMock();
		$bridge->method( 'should_expose_checkout_surface' )->willReturn( true );
		$provider = $this->getMockBuilder( WooPaymentsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();
		$provider->method( 'can_process_payments' )->willReturn( false );

		$integration = new WooPayments( $asset_api, $bridge, $provider );

		$this->assertFalse( $integration->is_active() );
	}

	/**
	 * @testdox Should register a core-owned Blocks asset handle for WooPayments.
	 */
	public function test_get_payment_method_script_handles_registers_core_owned_woopayments_blocks_script(): void {
		wp_deregister_script( 'stripe' );

		$asset_api = $this->getMockBuilder( AssetApi::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'register_script' ) )
			->getMock();
		$asset_api
			->expects( $this->once() )
			->method( 'register_script' )
			->with(
				'wc-payment-method-woopayments',
				'assets/client/blocks/wc-payment-method-woopayments.js',
				array( 'stripe' )
			);

		$bridge = $this->getMockBuilder( WooPaymentsCheckoutBridge::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_expose_checkout_surface' ) )
			->getMock();
		$bridge->method( 'should_expose_checkout_surface' )->willReturn( true );
		$provider = $this->getMockBuilder( WooPaymentsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();
		$provider->method( 'can_process_payments' )->willReturn( true );

		$integration = new WooPayments( $asset_api, $bridge, $provider );

		$this->assertSame( array( 'wc-payment-method-woopayments' ), $integration->get_payment_method_script_handles() );
		$this->assertTrue( wp_script_is( 'stripe', 'registered' ) );
		$this->assertSame( 'https://js.stripe.com/v3/', wp_scripts()->registered['stripe']->src );
	}

	/**
	 * @testdox Should source Blocks payment method data from the checkout bridge.
	 */
	public function test_get_payment_method_data_uses_checkout_bridge_config(): void {
		$asset_api = $this->getMockBuilder( AssetApi::class )
			->disableOriginalConstructor()
			->getMock();
		$bridge    = $this->getMockBuilder( WooPaymentsCheckoutBridge::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_blocks_payment_method_data', 'should_expose_checkout_surface' ) )
			->getMock();
		$bridge->method( 'should_expose_checkout_surface' )->willReturn( true );
		$bridge
			->expects( $this->once() )
			->method( 'get_blocks_payment_method_data' )
			->willReturn(
				array(
					'title' => 'WooPayments',
				)
			);
		$provider = $this->getMockBuilder( WooPaymentsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();
		$provider->method( 'can_process_payments' )->willReturn( true );

		$integration = new WooPayments( $asset_api, $bridge, $provider );

		$this->assertSame( array( 'title' => 'WooPayments' ), $integration->get_payment_method_data() );
	}
}
