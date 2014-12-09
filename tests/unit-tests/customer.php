<?php
class WC_Tests_Customer extends WC_Unit_Test_Case {

	/**
	 * Test the get_taxable_address method
	 */
	public function test_get_taxable_address() {

		$customer_data = array(
			'country' 				=> 'US',
			'state' 				=> 'PA',
			'postcode' 				=> '19123',
			'city'					=> 'Philadelphia',
			'address' 				=> '123 South Street',
			'address_2' 			=> 'Apt 1',
			'shipping_country' 		=> 'US',
			'shipping_state' 		=> 'PA',
			'shipping_postcode' 	=> '19123',
			'shipping_city'			=> 'Philadelphia',
			'shipping_address'		=> '123 South Street',
			'shipping_address_2'	=> 'Apt 1',
			'is_vat_exempt' 		=> false,
			'calculated_shipping'	=> false
		);

		//Set up the address assertions for the two taxable addresses

		$base_store_address = array( "GB", "", "", "" );
		$customer_address = array( "US", "PA", "19123", "Philadelphia" );

		//Initialize the session variables

		WC()->session->set( 'customer', $customer_data );

		// Create dummy product, and add it to the cart
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->id, 1 );

		// Customer is going with the Local Pickup option, and the store calculates tax based on the store base location.
		$customer = $this->helper_setup_customer( 'local_pickup', 'billing' );
		$this->assertEquals( $customer->get_taxable_address() , $base_store_address);
		
		// Customer is going with the Local Pickup option, and the store calculates tax based on the customer's billing address.
		$customer = $this->helper_setup_customer( 'local_pickup', 'billing' );
		$this->assertEquals( $customer->get_taxable_address() , $base_store_address);
		
		// Customer is going with the Free Shipping option, and the store calculates tax based on the customer's billing address.
		$customer = $this->helper_setup_customer( 'free_shipping', 'billing' );
		$this->assertEquals( $customer->get_taxable_address() , $customer_address);

		// Customer is going with the Free Shipping option, and the store calculates tax based on the store base location.
		$customer = $this->helper_setup_customer( 'free_shipping', 'base' );
		$this->assertEquals( $customer->get_taxable_address() , $base_store_address);
	}
	/**
	 * Test the is_customer_outside_base method
	 */
	public function test_is_customer_outside_base() {

		$customer_data = array(
			'country' 				=> 'US',
			'state' 				=> 'PA',
			'postcode' 				=> '19123',
			'city'					=> 'Philadelphia',
			'address' 				=> '123 South Street',
			'address_2' 			=> 'Apt 1',
			'shipping_country' 		=> 'US',
			'shipping_state' 		=> 'PA',
			'shipping_postcode' 	=> '19123',
			'shipping_city'			=> 'Philadelphia',
			'shipping_address'		=> '123 South Street',
			'shipping_address_2'	=> 'Apt 1',
			'is_vat_exempt' 		=> false,
			'calculated_shipping'	=> false
		);

		//Initialize the session variables for the dummy customer.
		
		WC()->session->set( 'customer', $customer_data );

		// Create dummy product, and add the product to the cart.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->id, 1 );

		// Customer is going with the Local Pickup option, and the store calculates tax based on the store base location.
		$customer = $this->helper_setup_customer( 'local_pickup', 'billing' );
		$this->assertFalse( $customer->is_customer_outside_base() );
		
		// Customer is going with the Local Pickup option, and the store calculates tax based on the customer's billing address.
		$customer = $this->helper_setup_customer( 'local_pickup', 'billing' );
		$this->assertFalse( $customer->is_customer_outside_base() );
		
		// Customer is going with the Free Shipping option, and the store calculates tax based on the customer's billing address.
		$customer = $this->helper_setup_customer( 'free_shipping', 'billing' );
		$this->assertTrue( $customer->is_customer_outside_base() );
		
		// Customer is going with the Free Shipping option, and the store calculates tax based on the store base location.
		$customer = $this->helper_setup_customer( 'free_shipping', 'base' );
		$this->assertFalse( $customer->is_customer_outside_base() );
	}
	/**
	 * Helper function for creating the customer and setting up the tax enviroment based on desired params.
	 */
	private function helper_setup_customer($shipping_method, $tax_based_on) {		
		//Set up the environment for our combination

		//Shipping Methods
		update_option( 'woocommerce_default_shipping_method', $shipping_method );
		WC()->session->set( 'chosen_shipping_methods', array( $shipping_method ) );

		//Tax "Based-on" Settings
		update_option( 'woocommerce_tax_based_on', $tax_based_on );

		$customer = new WC_Customer();

		return $customer;
	}
}