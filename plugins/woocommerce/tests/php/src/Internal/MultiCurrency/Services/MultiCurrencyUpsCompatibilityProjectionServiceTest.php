<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyUpsCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyUpsCompatibilityProjectionService class.
 */
class MultiCurrencyUpsCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project UPS compatibility hook manifest.
	 */
	public function test_projects_ups_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyUpsCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array( 'wcpay_multi_currency_should_return_store_currency' ),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame( array(), $manifest['actions'] );
		$this->assertSame( 'should_return_store_currency', $manifest['filters'][0]['callback'] );
		$this->assertSame( 10, $manifest['filters'][0]['priority'] );
		$this->assertSame( 1, $manifest['filters'][0]['accepted_args'] );
	}

	/**
	 * @testdox Should require UPS runtime before registering hooks.
	 */
	public function test_requires_ups_runtime_before_registering_hooks(): void {
		$this->assertTrue( MultiCurrencyUpsCompatibilityProjectionService::should_register( true ) );
		$this->assertFalse( MultiCurrencyUpsCompatibilityProjectionService::should_register( false ) );
	}

	/**
	 * @testdox Should project store currency decision for UPS context.
	 */
	public function test_projects_store_currency_decision_for_ups_context(): void {
		$this->assertTrue(
			MultiCurrencyUpsCompatibilityProjectionService::should_return_store_currency( true, false )
		);
		$this->assertTrue(
			MultiCurrencyUpsCompatibilityProjectionService::should_return_store_currency( false, true )
		);
		$this->assertFalse(
			MultiCurrencyUpsCompatibilityProjectionService::should_return_store_currency( false, false )
		);
	}
}
