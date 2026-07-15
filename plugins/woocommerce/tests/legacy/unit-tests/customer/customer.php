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
	 * Create a virtual product and add it to the global cart.
	 *
	 * @return WC_Product_Simple
	 */
	private function add_virtual_product_to_cart() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( true );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		return $product;
	}

	/**
	 * Give a customer a billing address (GB) that differs from its shipping address (US). The shipping
	 * address represents the store-base default that a virtual cart never overwrites.
	 *
	 * @param WC_Customer $customer Customer to configure.
	 */
	private function set_distinct_billing_and_shipping( $customer ) {
		$customer->set_props(
			array(
				'billing_country'   => 'GB',
				'billing_state'     => '',
				'billing_postcode'  => 'AB1 1AB',
				'billing_city'      => 'London',
				'shipping_country'  => 'US',
				'shipping_state'    => 'CA',
				'shipping_postcode' => '94110',
				'shipping_city'     => 'San Francisco',
			)
		);
	}

	/**
	 * @testdox get_taxable_address falls back to billing for the active shopper's virtual cart when tax is based on shipping.
	 */
	public function test_get_taxable_address_falls_back_to_billing_for_active_virtual_cart() {
		$original_customer         = WC()->customer;
		$original_tax_based_on     = WC_Helper_Customer::get_tax_based_on();
		$original_chosen           = WC_Helper_Customer::get_chosen_shipping_methods();
		$original_customer_details = WC_Helper_Customer::get_customer_details();

		try {
			$customer = WC_Helper_Customer::create_mock_customer();
			$this->set_distinct_billing_and_shipping( $customer );
			WC()->customer = $customer;

			WC_Helper_Customer::set_chosen_shipping_methods( array() );
			WC_Helper_Customer::set_tax_based_on( 'shipping' );

			$this->add_virtual_product_to_cart();

			$this->assertEquals(
				array( 'GB', '', 'AB1 1AB', 'London' ),
				$customer->get_taxable_address(),
				'A virtual cart should be taxed using the billing address when tax is based on the shipping address.'
			);

			// Adding a product that needs shipping restores shipping-based taxation for the mixed cart.
			WC()->cart->add_to_cart( WC_Helper_Product::create_simple_product()->get_id(), 1 );

			$this->assertEquals(
				array( 'US', 'CA', '94110', 'San Francisco' ),
				$customer->get_taxable_address(),
				'A cart containing an item that needs shipping should keep using the shipping address.'
			);
		} finally {
			WC()->cart->empty_cart();
			WC()->customer = $original_customer;
			WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen );
			WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
			WC_Helper_Customer::set_customer_details( $original_customer_details );
		}
	}

	/**
	 * @testdox get_taxable_address keeps using the shipping address when a virtual cart is forced to require shipping.
	 */
	public function test_get_taxable_address_uses_shipping_when_virtual_cart_requires_shipping() {
		$original_customer         = WC()->customer;
		$original_tax_based_on     = WC_Helper_Customer::get_tax_based_on();
		$original_chosen           = WC_Helper_Customer::get_chosen_shipping_methods();
		$original_customer_details = WC_Helper_Customer::get_customer_details();

		// Simulate an extension that forces a virtual cart to collect a shipping address.
		add_filter( 'woocommerce_cart_needs_shipping', '__return_true' );

		try {
			$customer = WC_Helper_Customer::create_mock_customer();
			$this->set_distinct_billing_and_shipping( $customer );
			WC()->customer = $customer;

			WC_Helper_Customer::set_chosen_shipping_methods( array() );
			WC_Helper_Customer::set_tax_based_on( 'shipping' );

			$this->add_virtual_product_to_cart();

			$this->assertEquals(
				array( 'US', 'CA', '94110', 'San Francisco' ),
				$customer->get_taxable_address(),
				'When shipping is forced to be required, the entered shipping address should still drive tax.'
			);
		} finally {
			remove_filter( 'woocommerce_cart_needs_shipping', '__return_true' );
			WC()->cart->empty_cart();
			WC()->customer = $original_customer;
			WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen );
			WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
			WC_Helper_Customer::set_customer_details( $original_customer_details );
		}
	}

	/**
	 * @testdox get_taxable_address for an explicit customer is not affected by the active shopper's virtual cart.
	 */
	public function test_get_taxable_address_ignores_active_cart_for_unrelated_customer() {
		$original_tax_based_on = WC_Helper_Customer::get_tax_based_on();
		$original_chosen       = WC_Helper_Customer::get_chosen_shipping_methods();

		try {
			WC_Helper_Customer::set_chosen_shipping_methods( array() );
			WC_Helper_Customer::set_tax_based_on( 'shipping' );

			// The active shopper's cart is virtual...
			$this->add_virtual_product_to_cart();

			// ...but a customer constructed for an unrelated context keeps its own shipping address for
			// tax and must not be swayed by the global cart.
			$other_customer = new WC_Customer();
			$this->set_distinct_billing_and_shipping( $other_customer );

			$this->assertEquals(
				array( 'US', 'CA', '94110', 'San Francisco' ),
				$other_customer->get_taxable_address(),
				'An explicit customer unrelated to the active cart should not be affected by the global cart contents.'
			);
		} finally {
			WC()->cart->empty_cart();
			WC_Helper_Customer::set_chosen_shipping_methods( $original_chosen );
			WC_Helper_Customer::set_tax_based_on( $original_tax_based_on );
		}
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
