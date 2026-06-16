<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPreOrdersCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyPreOrdersCompatibilityProjectionService class.
 */
class MultiCurrencyPreOrdersCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project Pre-Orders compatibility hook manifest.
	 */
	public function test_projects_pre_orders_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyPreOrdersCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array( 'wc_pre_orders_fee' ),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame( array(), $manifest['actions'] );
		$this->assertSame( 'convert_pre_orders_fee', $manifest['filters'][0]['callback'] );
		$this->assertSame( 10, $manifest['filters'][0]['priority'] );
		$this->assertSame( 1, $manifest['filters'][0]['accepted_args'] );
	}

	/**
	 * @testdox Should require Pre-Orders runtime before registering hooks.
	 */
	public function test_requires_pre_orders_runtime_before_registering_hooks(): void {
		$this->assertTrue( MultiCurrencyPreOrdersCompatibilityProjectionService::should_register( true ) );
		$this->assertFalse( MultiCurrencyPreOrdersCompatibilityProjectionService::should_register( false ) );
	}

	/**
	 * @testdox Should only convert fee args with an amount.
	 */
	public function test_only_converts_fee_args_with_amount(): void {
		$this->assertTrue(
			MultiCurrencyPreOrdersCompatibilityProjectionService::should_convert_fee_amount(
				array( 'amount' => '12.00' )
			)
		);
		$this->assertFalse(
			MultiCurrencyPreOrdersCompatibilityProjectionService::should_convert_fee_amount(
				array( 'name' => 'Pre-order fee' )
			)
		);
	}
}
