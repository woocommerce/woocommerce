<?php

/**
 * Order Item Tax Tests.
 * @package WooCommerce\Tests\Order_Items
 * @since 3.0.0
 */
class WC_Tests_Order_Item_Tax extends WC_Unit_Test_Case {

	/**
	 * Test set_tax_total/get_tax_total.
	 *
	 * @since 3.0.0
	 */
	function test_set_get_tax_totals() {

		$item = new WC_Order_Item_Tax();
		$this->assertEquals( 0, $item->get_tax_total() );

		$item->set_tax_total( '1.50' );
		$this->assertEquals( '1.50', $item->get_tax_total() );

		$item->set_tax_total( '' );
		$this->assertEquals( 0, $item->get_tax_total() );

		$item->set_tax_total( 10.99 );
		$this->assertEquals( '10.99', $item->get_tax_total() );
	}

	/**
	 * Test set_tax_total/get_tax_total.
	 *
	 * @since 3.0.0
	 */
	function test_set_get_shipping_tax_totals() {

		$item = new WC_Order_Item_Tax();
		$this->assertEquals( 0, $item->get_shipping_tax_total() );

		$item->set_shipping_tax_total( '1.50' );
		$this->assertEquals( '1.50', $item->get_shipping_tax_total() );

		$item->set_shipping_tax_total( '' );
		$this->assertEquals( 0, $item->get_shipping_tax_total() );

		$item->set_shipping_tax_total( 10.99 );
		$this->assertEquals( '10.99', $item->get_shipping_tax_total() );
	}
}
