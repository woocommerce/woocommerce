<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\StockPolicy;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the POS stock policy.
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
		$this->sut->register();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_product_is_in_stock', array( $this->sut, 'maybe_force_in_stock' ) );
		remove_filter( 'woocommerce_variation_is_in_stock', array( $this->sut, 'maybe_force_in_stock' ) );
		remove_filter( 'woocommerce_product_backorders_allowed', array( $this->sut, 'maybe_force_in_stock' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox An out-of-stock product is sellable in POS context and stays unsellable outside it.
	 */
	public function test_out_of_stock_product_sellable_only_in_pos_context(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		Context::set_test_override( false );
		$this->assertFalse( $product->is_in_stock() );

		Context::set_test_override( true );
		$this->assertTrue( $product->is_in_stock() );
	}

	/**
	 * Inventory drift is normal at the register: the goods in the customer's
	 * hand may exceed the recorded count, and the sale must not be blocked.
	 *
	 * @testdox Quantities above the recorded stock level are accepted in POS context.
	 */
	public function test_quantity_above_recorded_stock_sellable_in_pos_context(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 2 );
		$product->save();

		Context::set_test_override( false );
		$this->assertFalse( $product->has_enough_stock( 5 ) );

		Context::set_test_override( true );
		$this->assertTrue( $product->has_enough_stock( 5 ) );
	}
}
