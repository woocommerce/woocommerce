<?php

/**
 * Tests for the totals class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Totals extends WC_Unit_Test_Case {

	// Totals class for getter tests.
	protected $totals;

	// ID tracking for cleanup.
	protected $products = array();
	protected $coupons = array();
	protected $tax_rate_ids = array();

	/**
	 * Setup the cart for totals calculation.
	 */
	public function setUp() {
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$product  = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();

		$coupon = new WC_Coupon;
		$coupon->set_code( 'test-coupon-10' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$this->tax_rate_ids[] = $tax_rate_id;
		$this->products[]     = $product;
		$this->products[]     = $product2;
		$this->coupons[]      = $coupon;

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_to_cart( $product2->get_id(), 2 );
		WC()->cart->add_fee( "test fee", 10, true );
		WC()->cart->add_fee( "test fee 2", 20, true );
		WC()->cart->add_fee( "test fee non-taxable", 10, false );
		WC()->cart->add_discount( 'test-coupon-10' );

		// @todo manual discounts
		$this->totals = new WC_Totals( WC()->cart );
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		WC()->cart->empty_cart();
		update_option( 'woocommerce_calc_taxes', 'no' );

		foreach ( $this->products as $product ) {
			$product->delete( true );
		}

		foreach ( $this->coupons as $coupon ) {
			$coupon->delete( true );
		}

		foreach ( $this->tax_rate_ids as $tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
		}
	}

	/**
	 * Test get and set items.
	 */
	public function test_get_totals() {
		$this->assertEquals( array(), $this->totals->get_totals() );
	}

	/**
	 * Test get and set items.
	 */
	public function test_get_taxes() {

	}

	/**
	 * Test get and set items.
	 */
	public function test_get_tax_total() {

	}

	//... @todo test all public methods
}
