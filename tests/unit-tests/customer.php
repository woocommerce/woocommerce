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

		$base_store_address = array( "GB", "", "", "" );
		$customer_address = array( "US", "PA", "19123", "Philadelphia" );

		//Initialize the session variables for the dummy customer.
		
		// Get the original settings for the session and the options
		$original_default_shipping_method = WC_Helper_Customer::get_default_shipping_method();
		$original_chosen_shipping_methods = WC_Helper_Customer::get_chosen_shipping_methods();
		$original_tax_based_on = WC_Helper_Customer::get_tax_based_on();
		$original_customer_details = WC_Helper_Customer::get_customer_details();

		WC_Helper_Customer::set_customer_details( $customer_data );

		//Create a dummy customer to use for testing!
		$customer = new WC_Customer();

		// Create dummy product, and add the product to the cart.
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->add_to_cart( $product->id, 1 );

		//Run through these tests twice - with two different selections for the store's default shipping options

		foreach( array('local_pickup') as $default_shipping_option ) {

			WC_Helper_Customer::get_default_shipping_method( $default_shipping_option );

			// Customer is going with the Local Pickup option, and the store calculates tax based on the store location.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'local_pickup' ) );
			WC_Helper_Customer::set_tax_based_on( 'base' );

			$this->assertEquals( $customer->get_taxable_address(), $base_store_address );
			
			// Customer is going with the Local Pickup option, and the store calculates tax based on the customer's billing address.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'local_pickup' ) );
			WC_Helper_Customer::set_tax_based_on( 'billing' );

			$this->assertEquals( $customer->get_taxable_address(), $base_store_address );
			
			// Customer is going with the Free Shipping option, and the store calculates tax based on the customer's billing address.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'free_shipping' ) );
			WC_Helper_Customer::set_tax_based_on( 'billing' );

			$this->assertEquals( $customer->get_taxable_address(), $customer_address );
			
			// Customer is going with the Free Shipping option, and the store calculates tax based on the store base location.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'free_shipping' ) );
			WC_Helper_Customer::set_tax_based_on( 'base' );

			$this->assertEquals( $customer->get_taxable_address(), $base_store_address );

		}

		//Reset the session and the options.

		WC_Helper_Customer::set_default_shipping_method( $original_default_shipping_method );
		WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen_shipping_methods );
		WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
		WC_Helper_Customer::set_customer_details( $original_customer_details );
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
		
		// Get the original settings for the session and the options
		$original_default_shipping_method = WC_Helper_Customer::get_default_shipping_method();
		$original_chosen_shipping_methods = WC_Helper_Customer::get_chosen_shipping_methods();
		$original_tax_based_on = WC_Helper_Customer::get_tax_based_on();
		$original_customer_details = WC_Helper_Customer::get_customer_details();

		WC_Helper_Customer::set_customer_details( $customer_data );

		//Create a dummy customer to use for testing!
		$customer = new WC_Customer();

		// Create dummy product, and add the product to the cart.
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->add_to_cart( $product->id, 1 );

		//Run through these tests twice - with two different selections for the store's default shipping options

		foreach( array('local_pickup', 'free_shipping') as $default_shipping_option ) {

			WC_Helper_Customer::get_default_shipping_method( $default_shipping_option );

			// Customer is going with the Local Pickup option, and the store calculates tax based on the store location.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'local_pickup' ) );
			WC_Helper_Customer::set_tax_based_on( 'base' );

			$this->assertEquals( $customer->is_customer_outside_base(), false );
			
			// Customer is going with the Local Pickup option, and the store calculates tax based on the customer's billing address.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'local_pickup' ) );
			WC_Helper_Customer::set_tax_based_on( 'billing' );

			$this->assertEquals( $customer->is_customer_outside_base(), false );
			
			// Customer is going with the Free Shipping option, and the store calculates tax based on the customer's billing address.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'free_shipping' ) );
			WC_Helper_Customer::set_tax_based_on( 'billing' );

			$this->assertEquals( $customer->is_customer_outside_base(), true );
			
			// Customer is going with the Free Shipping option, and the store calculates tax based on the store base location.

			WC_Helper_Customer::set_chosen_shipping_methods( array( 'free_shipping' ) );
			WC_Helper_Customer::set_tax_based_on( 'base' );

			$this->assertEquals( $customer->is_customer_outside_base(), false );

		}

		//Reset the session and the options.

		WC_Helper_Customer::set_default_shipping_method( $original_default_shipping_method );
		WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen_shipping_methods );
		WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
		WC_Helper_Customer::set_customer_details( $original_customer_details );
	}
}