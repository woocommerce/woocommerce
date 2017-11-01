<?php
/**
 * Tests for the WC_Product_Variation class.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Class Product_Variation.
 *
 * @package WooCommerce\Tests\Product
 * @since 3.0
 */
class WC_Tests_Product_Variation extends WC_Unit_Test_Case {

	/**
	 * Test is_sold_individually().
	 *
	 * @since 2.3
	 */
	public function test_is_sold_individually() {
		// Create a variable product with sold individually.
		$product = new WC_Product_Variable;
		$product->set_sold_individually( true );
		$product->save();

		$variation = new WC_Product_Variation;
		$variation->set_parent_id( $product->get_id() );
		$variation->save();

		$variation = wc_get_product( $variation->get_id() );
		$this->assertTrue( $variation->is_sold_individually() );
	}

	/**
	 * Check get_tax_class against different parent child scenarios
	 */
	public function test_get_tax_class() {
		$product = new WC_Product_Variable;
		$product->set_tax_class( 'standard' );
		$product->save();

		$variation = new WC_Product_Variation;
		$variation->set_parent_id( $product->get_id() );
		$variation->set_tax_class( 'parent' );
		$variation->save();

		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( '', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( 'parent', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( '', $variation->get_tax_class( 'view' ) );

		$variation->set_tax_class( 'standard' );
		$variation->save();
		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( '', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( '', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( '', $variation->get_tax_class( 'view' ) );

		$variation->set_tax_class( 'reduced-rate' );
		$variation->save();
		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( 'reduced-rate', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( 'reduced-rate', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( 'reduced-rate', $variation->get_tax_class( 'view' ) );

		$product->set_tax_class( 'zero-rate' );
		$product->save();

		$variation->set_tax_class( 'parent' );
		$variation->save();
		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( 'zero-rate', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( 'parent', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( 'zero-rate', $variation->get_tax_class( 'view' ) );
	}
}
