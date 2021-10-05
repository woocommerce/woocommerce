<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Cart.
 */

/**
 * Class WC_Cart_Test
 */
class WC_Cart_Test extends \WC_Unit_Test_Case {

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

		// Check for cart contents.
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

	/**
	 * @testdox should throw a notice to the cart if using variation_id
	 * that doesn't belong to specified variable product.
	 */
	public function test_add_variation_to_the_cart_invalid_variation_id() {
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$variable_product = WC_Helper_Product::create_variation_product();
		$single_product   = WC_Helper_Product::create_simple_product();

		// Add variation using parent id.
		WC()->cart->add_to_cart(
			$variable_product->get_id(),
			1,
			$single_product->get_id()
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check for cart contents.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Check that the notices contain an error message about invalid colour and number.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$expected = sprintf( sprintf( 'The selected product isn\'t a variation of %2$s, please choose product options by visiting <a href="%1$s" title="%2$s">%2$s</a>.', esc_url( $variable_product->get_permalink() ), esc_html( $variable_product->get_name() ) ) );
		$this->assertEquals( $expected, $notices['error'][0]['notice'] );

		// Reset cart.
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		$variable_product->delete( true );
	}

	/**
	 * Test show shipping.
	 */
	public function test_show_shipping() {
		// Test with an empty cart.
		$this->assertFalse( WC()->cart->show_shipping() );

		// Add a product to the cart.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test with "woocommerce_ship_to_countries" disabled.
		$default_ship_to_countries = get_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_ship_to_countries', 'disabled' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Test with default "woocommerce_ship_to_countries" and "woocommerce_shipping_cost_requires_address".
		update_option( 'woocommerce_ship_to_countries', $default_ship_to_countries );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Test with "woocommerce_shipping_cost_requires_address" enabled.
		$default_shipping_cost_requires_address = get_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Set address for shipping calculation required for "woocommerce_shipping_cost_requires_address".
		WC()->cart->get_customer()->set_shipping_country( 'US' );
		WC()->cart->get_customer()->set_shipping_state( 'NY' );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Reset.
		update_option( 'woocommerce_shipping_cost_requires_address', $default_shipping_cost_requires_address );
		$product->delete( true );
		WC()->cart->get_customer()->set_shipping_country( 'GB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
	}

	/**
	 * Test show_shipping for countries with various state/postcode requirement.
	 */
	public function test_show_shipping_for_countries_different_shipping_requirements() {
		$default_shipping_cost_requires_address = get_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		WC()->cart->empty_cart();
		$this->assertFalse( WC()->cart->show_shipping() );

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Country that does not require state.
		WC()->cart->get_customer()->set_shipping_country( 'LB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Country that does not require postcode.
		WC()->cart->get_customer()->set_shipping_country( 'NG' );
		WC()->cart->get_customer()->set_shipping_state( 'AB' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Reset.
		update_option( 'woocommerce_shipping_cost_requires_address', $default_shipping_cost_requires_address );
		$product->delete( true );
		WC()->cart->get_customer()->set_shipping_country( 'GB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
	}

	/**
	 * Test adding a variable product without selecting variations.
	 *
	 * @see WC_Form_Handler::add_to_cart_action()
	 */
	public function test_form_handler_add_to_cart_action_with_parent_variable_product() {
		$this->tearDown();

		$product                 = WC_Helper_Product::create_variation_product();
		$product_id              = $product->get_id();
		$url                     = get_permalink( $product_id );
		$_REQUEST['add-to-cart'] = $product_id;

		WC_Form_Handler::add_to_cart_action();

		$notices = WC()->session->get( 'wc_notices', array() );

		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$this->assertRegExp( '/Please choose product options by visiting/', $notices['error'][0]['notice'] );
	}
}
