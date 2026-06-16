<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDepositsCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyDepositsCompatibilityProjectionService class.
 */
class MultiCurrencyDepositsCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project Deposits compatibility hook manifest.
	 */
	public function test_projects_deposits_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyDepositsCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array(
				'woocommerce_get_cart_contents',
				'woocommerce_product_get__wc_deposit_amount',
				'wcpay_multi_currency_should_convert_product_price',
			),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame(
			array( 'woocommerce_deposits_create_order' ),
			array_column( $manifest['actions'], 'hook' )
		);
		$this->assertSame( 'modify_order_currency', $manifest['actions'][0]['callback'] );
		$this->assertSame( 'modify_cart_item_deposit_amounts', $manifest['filters'][0]['callback'] );
		$this->assertSame( 'modify_cart_item_deposit_amount_meta', $manifest['filters'][1]['callback'] );
		$this->assertSame( 'maybe_convert_product_prices_for_deposits', $manifest['filters'][2]['callback'] );
		$this->assertSame( 10, $manifest['filters'][0]['priority'] );
		$this->assertSame( 1, $manifest['filters'][0]['accepted_args'] );
		$this->assertSame( 2, $manifest['filters'][1]['accepted_args'] );
	}

	/**
	 * @testdox Should register only for Deposits versions without native multi-currency support.
	 */
	public function test_registers_only_for_deposits_versions_without_native_multi_currency_support(): void {
		$this->assertTrue( MultiCurrencyDepositsCompatibilityProjectionService::should_register( true, '2.0.0' ) );
		$this->assertTrue( MultiCurrencyDepositsCompatibilityProjectionService::should_register( true, null ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_register( true, '2.0.1' ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_register( true, '2.1.0' ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_register( false, '1.9.9' ) );
	}

	/**
	 * @testdox Should convert only deposit cart items with deposit amounts.
	 */
	public function test_converts_only_deposit_cart_items_with_deposit_amounts(): void {
		$this->assertTrue(
			MultiCurrencyDepositsCompatibilityProjectionService::should_convert_cart_item_deposit_amount(
				array(
					'is_deposit'     => true,
					'deposit_amount' => '10.00',
				)
			)
		);
		$this->assertFalse(
			MultiCurrencyDepositsCompatibilityProjectionService::should_convert_cart_item_deposit_amount(
				array(
					'is_deposit'     => false,
					'deposit_amount' => '10.00',
				)
			)
		);
		$this->assertFalse(
			MultiCurrencyDepositsCompatibilityProjectionService::should_convert_cart_item_deposit_amount(
				array( 'is_deposit' => true )
			)
		);
	}

	/**
	 * @testdox Should project deposit amount meta conversion decisions.
	 */
	public function test_projects_deposit_amount_meta_conversion_decisions(): void {
		$this->assertTrue( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_deposit_amount_meta( 'percent', true ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_deposit_amount_meta( 'percent', false ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_deposit_amount_meta( 'plan', true ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_deposit_amount_meta( false, true ) );
	}

	/**
	 * @testdox Should project Deposits product conversion decisions.
	 */
	public function test_projects_deposits_product_conversion_decisions(): void {
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_product_price( true, 'plan', true ) );
		$this->assertTrue( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_product_price( true, 'plan', false ) );
		$this->assertTrue( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_product_price( true, 'percent', true ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_convert_product_price( false, 'plan', true ) );
	}

	/**
	 * @testdox Should project remaining order currency alignment decisions.
	 */
	public function test_projects_remaining_order_currency_alignment_decisions(): void {
		$this->assertTrue( MultiCurrencyDepositsCompatibilityProjectionService::should_align_order_currency( 'USD', 'GBP' ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_align_order_currency( 'GBP', 'GBP' ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_align_order_currency( '', 'GBP' ) );
		$this->assertFalse( MultiCurrencyDepositsCompatibilityProjectionService::should_align_order_currency( 'USD', '' ) );
	}
}
