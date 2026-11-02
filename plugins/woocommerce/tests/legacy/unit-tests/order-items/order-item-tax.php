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

	/**
	 * Test that set_label preserves percent signs in tax names.
	 *
	 * In some locales (e.g. Turkish, Basque), the percent sign is placed
	 * before the number (e.g. "%15 KDV"). Previously, wc_clean() would
	 * URL-decode "%15" and strip the resulting control character.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/45103
	 */
	public function test_set_label_preserves_percent_sign() {
		$item = new WC_Order_Item_Tax();

		$item->set_label( 'Test %15 Tax' );
		$this->assertEquals( 'Test %15 Tax', $item->get_label() );

		$item->set_label( '%25 KDV' );
		$this->assertEquals( '%25 KDV', $item->get_label() );

		$item->set_label( 'VAT 15%' );
		$this->assertEquals( 'VAT 15%', $item->get_label() );

		$item->set_label( '<b>%15 KDV</b>' );
		$this->assertEquals( '%15 KDV', $item->get_label() );
	}
}
