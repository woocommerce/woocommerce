<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFedExCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyFedExCompatibilityProjectionService class.
 */
class MultiCurrencyFedExCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project FedEx compatibility hook manifest.
	 */
	public function test_projects_fedex_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyFedExCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array(
				'wcpay_multi_currency_should_convert_product_price',
				'wcpay_multi_currency_should_return_store_currency',
			),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame( array(), $manifest['actions'] );
		$this->assertSame( 'should_convert_product_price', $manifest['filters'][0]['callback'] );
		$this->assertSame( 'should_return_store_currency', $manifest['filters'][1]['callback'] );
		$this->assertSame( 10, $manifest['filters'][0]['priority'] );
		$this->assertSame( 1, $manifest['filters'][0]['accepted_args'] );
	}

	/**
	 * @testdox Should require FedEx runtime before registering hooks.
	 */
	public function test_requires_fedex_runtime_before_registering_hooks(): void {
		$this->assertTrue( MultiCurrencyFedExCompatibilityProjectionService::should_register( true ) );
		$this->assertFalse( MultiCurrencyFedExCompatibilityProjectionService::should_register( false ) );
	}

	/**
	 * @testdox Should project FedEx product conversion decisions.
	 */
	public function test_projects_fedex_product_conversion_decisions(): void {
		$this->assertTrue(
			MultiCurrencyFedExCompatibilityProjectionService::should_convert_product_price( true, false )
		);
		$this->assertFalse(
			MultiCurrencyFedExCompatibilityProjectionService::should_convert_product_price( true, true )
		);
		$this->assertFalse(
			MultiCurrencyFedExCompatibilityProjectionService::should_convert_product_price( false, true )
		);
	}

	/**
	 * @testdox Should project FedEx store currency decisions.
	 */
	public function test_projects_fedex_store_currency_decisions(): void {
		$this->assertTrue(
			MultiCurrencyFedExCompatibilityProjectionService::should_return_store_currency( true, false )
		);
		$this->assertTrue(
			MultiCurrencyFedExCompatibilityProjectionService::should_return_store_currency( false, true )
		);
		$this->assertFalse(
			MultiCurrencyFedExCompatibilityProjectionService::should_return_store_currency( false, false )
		);
	}
}
