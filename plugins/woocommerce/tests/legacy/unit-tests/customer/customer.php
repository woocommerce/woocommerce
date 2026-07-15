<?php

/**
 * Class Customer.
 * @package WooCommerce\Tests\Customer
 */
class WC_Tests_Customer extends WC_Unit_Test_Case {

	/**
	 * Test the get_taxable_address method.
	 */
	public function test_get_taxable_address() {

		$customer           = WC_Helper_Customer::create_mock_customer();
		$base_store_address = WC_Helper_Customer::get_expected_store_location();
		$customer_address   = $customer->get_taxable_address(); // Default is geolocation!

		// Get the original settings for the session and the WooCommerce options
		$original_chosen_shipping_methods = WC_Helper_Customer::get_chosen_shipping_methods();
		$original_tax_based_on            = WC_Helper_Customer::get_tax_based_on();
		$original_customer_details        = WC_Helper_Customer::get_customer_details();

		// Create dummy product, and add the product to the cart.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

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

		// Now reset the settings back to the way they were before this test
		WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen_shipping_methods );
		WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
		WC_Helper_Customer::set_customer_details( $original_customer_details );

		// Clean up the cart
		WC()->cart->empty_cart();
	}

	/**
	 * @testdox get_taxable_address falls back to the billing address for a virtual cart when tax is based on the shipping address.
	 */
	public function test_get_taxable_address_falls_back_to_billing_for_virtual_cart() {
		$original_chosen_shipping_methods = WC_Helper_Customer::get_chosen_shipping_methods();
		$original_tax_based_on            = WC_Helper_Customer::get_tax_based_on();
		$original_customer_details        = WC_Helper_Customer::get_customer_details();

		$customer = WC_Helper_Customer::create_mock_customer();

		// The customer provided a billing address (GB), but the shipping address still holds the
		// store-base default (US), as happens on a virtual-only cart where no shipping address is
		// collected from the customer.
		$customer->set_billing_country( 'GB' );
		$customer->set_billing_state( '' );
		$customer->set_billing_postcode( 'AB1 1AB' );
		$customer->set_billing_city( 'London' );

		WC_Helper_Customer::set_chosen_shipping_methods( array() );
		WC_Helper_Customer::set_tax_based_on( 'shipping' );

		$virtual_product = WC_Helper_Product::create_simple_product();
		$virtual_product->set_virtual( true );
		$virtual_product->save();
		WC()->cart->add_to_cart( $virtual_product->get_id(), 1 );

		$this->assertEquals(
			array( 'GB', '', 'AB1 1AB', 'London' ),
			$customer->get_taxable_address(),
			'A virtual cart should be taxed using the billing address when tax is based on the shipping address.'
		);

		// Adding a product that needs shipping must restore shipping-based taxation for the mixed cart.
		$physical_product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $physical_product->get_id(), 1 );

		$this->assertEquals(
			array( 'US', 'CA', '94110', 'San Francisco' ),
			$customer->get_taxable_address(),
			'A cart containing an item that needs shipping should keep using the shipping address.'
		);

		WC()->cart->empty_cart();
		WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen_shipping_methods );
		WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
		WC_Helper_Customer::set_customer_details( $original_customer_details );
	}

	/**
	 * Test the is_customer_outside_base method.
	 */
	public function test_is_customer_outside_base() {

		// Get the original settings for the session and the WooCommerce options
		$original_chosen_shipping_methods = WC_Helper_Customer::get_chosen_shipping_methods();
		$original_tax_based_on            = WC_Helper_Customer::get_tax_based_on();
		$original_customer_details        = WC_Helper_Customer::get_customer_details();

		$customer = WC_Helper_Customer::create_mock_customer();

		// Create dummy product, and add the product to the cart.
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->add_to_cart( $product->get_id(), 1 );

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
		$this->assertEquals( $customer->is_customer_outside_base(), false );

		// Customer is going with the Free Shipping option, and the store calculates tax based on the store base location.
		WC_Helper_Customer::set_chosen_shipping_methods( array( 'free_shipping' ) );
		WC_Helper_Customer::set_tax_based_on( 'base' );
		$this->assertEquals( $customer->is_customer_outside_base(), false );

		// Now reset the settings back to the way they were before this test
		WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen_shipping_methods );
		WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
		WC_Helper_Customer::set_customer_details( $original_customer_details );

		// Clean up the cart
		WC()->cart->empty_cart();
	}
}
