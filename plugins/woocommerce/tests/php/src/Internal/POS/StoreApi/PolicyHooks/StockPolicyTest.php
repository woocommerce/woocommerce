<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\StockPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Stock policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\StockPolicy
 */
class StockPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StockPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new StockPolicy();
	}

	/**
	 * Tear down — clear POS context override and any filters this test may have installed.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_product_is_in_stock', '__return_true' );
		remove_filter( 'woocommerce_variation_is_in_stock', '__return_true' );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches stock filters inside POS context.
	 */
	public function test_register_attaches_filters_in_pos_context(): void {
		Context::set_test_override( true );

		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_product_is_in_stock', '__return_true' ) );
		$this->assertNotFalse( has_filter( 'woocommerce_variation_is_in_stock', '__return_true' ) );
	}

	/**
	 * @testdox register() does not attach stock filters outside POS context.
	 */
	public function test_register_skips_filters_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->sut->register();

		$this->assertFalse( has_filter( 'woocommerce_product_is_in_stock', '__return_true' ) );
		$this->assertFalse( has_filter( 'woocommerce_variation_is_in_stock', '__return_true' ) );
	}
}
