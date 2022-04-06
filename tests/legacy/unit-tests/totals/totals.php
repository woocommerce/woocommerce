<?php
/**
 * Tests for the totals class.
 *
 * @package WooCommerce\Tests\Discounts
 */

/**
 * WC_Tests_Totals
 */
class WC_Tests_Totals extends WC_Unit_Test_Case {

	/**
	 * Totals class for getter tests.
	 *
	 * @var object
	 */
	protected $totals;

	/**
	 * ID tracking for cleanup.
	 *
	 * @var array
	 */
	protected $ids = array();

	/**
	 * Setup the cart for totals calculation.
	 */
	public function setUp() {
		parent::setUp();

		// Set a valid address for the customer so shipping rates will calculate.
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'NY' );
		WC()->customer->set_shipping_postcode( '12345' );

		WC()->cart->empty_cart();

		$this->ids = array();

		$tax_rate    = array(
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
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'base' );

		$product  = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();
		// Variations with parent tax class.
		$product3   = WC_Helper_Product::create_variation_product();
		$variations = $product3->get_available_variations();

		// Update product3 so that each variation has the parent tax class.
		foreach ( $variations as $variation ) {
			$child = wc_get_product( $variation['variation_id'] );
			$child->set_tax_class( 'parent' );
			$child->save();
		}

		WC_Helper_Shipping::create_simple_flat_rate();
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-coupon-10' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$this->ids['tax_rate_ids'][] = $tax_rate_id;
		$this->ids['products'][]     = $product;
		$this->ids['products'][]     = $product2;
		$this->ids['products'][]     = $product3;
		$this->ids['coupons'][]      = $coupon;

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_to_cart( $product2->get_id(), 2 );
		$variations = $product3->get_available_variations();
		$variation  = array_shift( $variations );
		WC()->cart->add_to_cart(
			$product3->get_id(),
			1,
			$variation['variation_id'],
			array(
				'attribute_pa_colour' => 'red', // Set a value since this is an 'any' attribute.
				'attribute_pa_number' => '2', // Set a value since this is an 'any' attribute.
			)
		);

		WC()->cart->add_discount( $coupon->get_code() );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees_callback' ) );
	}

	/**
	 * Add fees when the fees API is called.
	 */
	public function add_cart_fees_callback() {
		WC()->cart->add_fee( 'test fee', 10, true );
		WC()->cart->add_fee( 'test fee 2', 20, true );
		WC()->cart->add_fee( 'test fee non-taxable', 10, false );
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		WC()->cart->empty_cart();
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Shipping::delete_simple_flat_rate();
		remove_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees_callback' ) );

		WC()->customer->set_is_vat_exempt( false );

		parent::tearDown();
	}

	/**
	 * Test get and set items.
	 */
	public function test_get_totals() {
		WC()->customer->set_is_vat_exempt( false );
		$this->totals = new WC_Cart_Totals( WC()->cart );

		$this->assertEquals(
			array(
				'fees_total'          => 40.00,
				'fees_total_tax'      => 6.00,
				'items_subtotal'      => 40.00,
				'items_subtotal_tax'  => 8.00,
				'items_total'         => 36.00,
				'items_total_tax'     => 7.20,
				'total'               => 101.20,
				'shipping_total'      => 10,
				'shipping_tax_total'  => 2,
				'discounts_total'     => 4.00,
				'discounts_tax_total' => 0.80,
			),
			$this->totals->get_totals()
		);
	}

	/**
	 * Test get totals for tax exempt customer.
	 */
	public function test_get_totals_tax_exempt_customer() {
		WC()->customer->set_is_vat_exempt( true );
		$this->totals = new WC_Cart_Totals( WC()->cart );

		$this->assertEquals(
			array(
				'fees_total'          => 40.00,
				'fees_total_tax'      => 0.00,
				'items_subtotal'      => 40.00,
				'items_subtotal_tax'  => 0.00,
				'items_total'         => 36.00,
				'items_total_tax'     => 0.00,
				'total'               => 86.00,
				'shipping_total'      => 10,
				'shipping_tax_total'  => 0.00,
				'discounts_total'     => 4.00,
				'discounts_tax_total' => 0.00,
			),
			$this->totals->get_totals()
		);
	}

	/**
	 * Test that cart totals get updated.
	 */
	public function test_cart_totals() {
		WC()->customer->set_is_vat_exempt( false );
		$this->totals = new WC_Cart_Totals( WC()->cart );
		$cart         = WC()->cart;

		$this->assertEquals( 40.00, $cart->get_fee_total() );
		$this->assertEquals( 36.00, $cart->get_cart_contents_total() );
		$this->assertEquals( 101.20, $cart->get_total( 'edit' ) );
		$this->assertEquals( 40.00, $cart->get_subtotal() );
		$this->assertEquals( 8.00, $cart->get_subtotal_tax() );
		$this->assertEquals( 7.20, $cart->get_cart_contents_tax() );
		$this->assertEquals( 7.20, array_sum( $cart->get_cart_contents_taxes() ) );
		$this->assertEquals( 6.00, $cart->get_fee_tax() );
		$this->assertEquals( 6.00, array_sum( $cart->get_fee_taxes() ) );
		$this->assertEquals( 4, $cart->get_discount_total() );
		$this->assertEquals( 0.80, $cart->get_discount_tax() );
		$this->assertEquals( 10, $cart->get_shipping_total() );
		$this->assertEquals( 2, $cart->get_shipping_tax() );

		$this->assertEquals( 36.00, $cart->cart_contents_total );
		$this->assertEquals( 101.20, $cart->total );
		$this->assertEquals( 48.00, $cart->subtotal );
		$this->assertEquals( 40.00, $cart->subtotal_ex_tax );
		$this->assertEquals( 13.20, $cart->tax_total );
		$this->assertEquals( 4, $cart->discount_cart );
		$this->assertEquals( 0.80, $cart->discount_cart_tax );
		$this->assertEquals( 40.00, $cart->fee_total );
		$this->assertEquals( 10, $cart->shipping_total );
		$this->assertEquals( 2, $cart->shipping_tax_total );
	}

	/**
	 * Test that cart totals get updated for tax exempt customer.
	 */
	public function test_cart_totals_tax_exempt_customer() {
		WC()->customer->set_is_vat_exempt( true );
		$this->totals = new WC_Cart_Totals( WC()->cart );
		$cart         = WC()->cart;

		$this->assertEquals( 40.00, $cart->get_fee_total() );
		$this->assertEquals( 36.00, $cart->get_cart_contents_total() );
		$this->assertEquals( 86.00, $cart->get_total( 'edit' ) );
		$this->assertEquals( 40.00, $cart->get_subtotal() );
		$this->assertEquals( 0.00, $cart->get_subtotal_tax() );
		$this->assertEquals( 0.00, $cart->get_cart_contents_tax() );
		$this->assertEquals( 0.00, array_sum( $cart->get_cart_contents_taxes() ) );
		$this->assertEquals( 0.00, $cart->get_fee_tax() );
		$this->assertEquals( 0.00, array_sum( $cart->get_fee_taxes() ) );
		$this->assertEquals( 4, $cart->get_discount_total() );
		$this->assertEquals( 0.00, $cart->get_discount_tax() );
		$this->assertEquals( 10, $cart->get_shipping_total() );
		$this->assertEquals( 0.00, $cart->get_shipping_tax() );

		$this->assertEquals( 36.00, $cart->cart_contents_total );
		$this->assertEquals( 86.00, $cart->total );
		$this->assertEquals( 40.00, $cart->subtotal );
		$this->assertEquals( 40.00, $cart->subtotal_ex_tax );
		$this->assertEquals( 0.00, $cart->tax_total );
		$this->assertEquals( 4, $cart->discount_cart );
		$this->assertEquals( 0.00, $cart->discount_cart_tax );
		$this->assertEquals( 40.00, $cart->fee_total );
		$this->assertEquals( 10, $cart->shipping_total );
		$this->assertEquals( 0.00, $cart->shipping_tax_total );
	}
}
