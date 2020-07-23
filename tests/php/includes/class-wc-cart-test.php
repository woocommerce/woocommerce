<?php
/**
 * Unit tests for the WC_Form_Cart_Test class.
 *
 * @package WooCommerce\Tests\Cart.
 */

/**
 * Class WC_Form_Cart_Test
 */
class WC_Form_Cart_Test extends \WC_Unit_Test_Case {

	/**
	 * tearDown.
	 */
	public function tearDown() {
		parent::tearDown();

		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * @testdox should throw a notice to the cart if an "any" attribute is empty.
	 */
	public function test_add_variation_to_the_cart_with_empty_attributes() {
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();

		// Get a variation with small pa_size and any pa_colour and pa_number.
		$variation = $variations[0];

		// Add variation using parent id.
		WC()->cart->add_to_cart(
			$variation['variation_id'],
			1,
			0,
			array(
				'attribute_pa_colour' => '',
				'attribute_pa_number' => '',
			)
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check that the second add to cart call increases the quantity of the existing cart-item.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Check that the notices contain an error message about invalid colour and number.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$this->assertEquals( 'colour and number are required fields', $notices['error'][0]['notice'] );

		// Reset cart.
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		$product->delete( true );
	}
}
