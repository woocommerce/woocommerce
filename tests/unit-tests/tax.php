<?php

class WC_Tests_Tax extends WC_Unit_Test_Case {

	/**
	 * Get rates
	 */
	public function test_get_rates() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::get_rates();

		$this->assertEquals( $tax_rates, array( $tax_rate_id => array( 'rate' => '20.0000', 'label' => 'VAT', 'shipping' => 'yes', 'compound' => 'no' ) ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Get rates
	 */
	public function test_get_shipping_tax_rates() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::get_shipping_tax_rates();

		$this->assertEquals( $tax_rates, array( $tax_rate_id => array( 'rate' => '20.0000', 'label' => 'VAT', 'shipping' => 'yes', 'compound' => 'no' ) ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Get rates
	 */
	public function test_get_base_tax_rates() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::get_base_tax_rates();

		$this->assertEquals( $tax_rates, array( $tax_rate_id => array( 'rate' => '20.0000', 'label' => 'VAT', 'shipping' => 'yes', 'compound' => 'no' ) ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Find tax rates
	 */
	public function test_find_rates() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::find_rates( array(
			'country'   => 'GB',
			'state'     => 'Cambs',
			'postcode'  => 'PE14 1XX',
			'city'      => 'Somewhere',
			'tax_class' => ''
		) );

		$this->assertEquals( $tax_rates, array( $tax_rate_id => array( 'rate' => '20.0000', 'label' => 'VAT', 'shipping' => 'yes', 'compound' => 'no' ) ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Find tax rates
	 */
	public function test_find_shipping_rates() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::find_shipping_rates( array(
			'country'   => 'GB',
			'state'     => 'Cambs',
			'postcode'  => 'PE14 1XX',
			'city'      => 'Somewhere',
			'tax_class' => ''
		) );

		$this->assertEquals( $tax_rates, array( $tax_rate_id => array( 'rate' => '20.0000', 'label' => 'VAT', 'shipping' => 'yes', 'compound' => 'no' ) ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test tax amounts
	 */
	public function test_calc_tax() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::find_rates( array(
			'country'   => 'GB',
			'state'     => 'Cambs',
			'postcode'  => 'PE14 1XX',
			'city'      => 'Somewhere',
			'tax_class' => ''
		) );

		$calced_tax = WC_Tax::calc_tax( '9.99', $tax_rates, true, false );

		$this->assertEquals( $calced_tax, array( $tax_rate_id => '1.665' ) );

		$calced_tax = WC_Tax::calc_tax( '9.99', $tax_rates, false, false );

		$this->assertEquals( $calced_tax, array( $tax_rate_id => '1.998' ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Shipping tax amounts
	 */
	public function test_calc_shipping_tax() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::find_rates( array(
			'country'   => 'GB',
			'state'     => 'Cambs',
			'postcode'  => 'PE14 1XX',
			'city'      => 'Somewhere',
			'tax_class' => ''
		) );

		$calced_tax = WC_Tax::calc_shipping_tax( '10', $tax_rates );

		$this->assertEquals( $calced_tax, array( $tax_rate_id => '2' ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Customer Location
	 */
	public function test_get_customer_location() {
		$this->assertEquals( WC_Tax::get_customer_location(), array( 'GB', '', '', '' ) );
	}

	/**
	 * Test rate labels
	 */
	public function test_get_rate_label() {
		global $wpdb;

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertEquals( WC_Tax::get_rate_label( $tax_rate_id ), 'VAT' );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test rate percent
	 */
	public function test_get_rate_percent() {
		global $wpdb;

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertEquals( WC_Tax::get_rate_percent( $tax_rate_id ), '20%' );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test rate code
	 */
	public function test_get_rate_code() {
		global $wpdb;

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertEquals( WC_Tax::get_rate_code( $tax_rate_id ), 'GB-VAT-1' );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test is compound
	 */
	public function test_is_compound() {
		global $wpdb;

		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertTrue( WC_Tax::is_compound( $tax_rate_id ) );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test the rounding method
	 */
	public function test_round() {
		$this->assertEquals( WC_Tax::round( '2.1234567' ), '2.1235' );
	}

	/**
	 * Get tax totals
	 */
	public function test_get_tax_total() {
		$to_total = array(
			'1' => '1.665',
			'2' => '2',
		);

		$this->assertEquals( WC_Tax::get_tax_total( $to_total ), '3.665' );
	}

	/**
	 * Test getting the tax classes
	 */
	public function test_get_tax_classes() {
		$tax_classes = WC_Tax::get_tax_classes();

		$this->assertEquals( $tax_classes, array( 'Reduced Rate', 'Zero Rate' ) );
	}

	/**
	 * Test Inserting a tax rate
	 */
	public function test__insert_tax_rate() {
		global $wpdb;

		// Define a rate
		$tax_rate = array(
			'tax_rate_country'  => 'gb',
			'tax_rate_state'    => '',
			'tax_rate'          => '20',
			'tax_rate_name'     => '',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		// Run function
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertGreaterThan( 0, $tax_rate_id );

		$new_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d", $tax_rate_id ) );

		$this->assertEquals( $new_row->tax_rate_country, 'GB' );
		$this->assertEquals( $new_row->tax_rate_state, '' );
		$this->assertEquals( $new_row->tax_rate, '20.0000' );
		$this->assertEquals( $new_row->tax_rate_name, 'Tax' );
		$this->assertEquals( $new_row->tax_rate_priority, '1' );
		$this->assertEquals( $new_row->tax_rate_compound, '0' );
		$this->assertEquals( $new_row->tax_rate_shipping, '1' );
		$this->assertEquals( $new_row->tax_rate_order, '1' );
		$this->assertEquals( $new_row->tax_rate_class, '' );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test updating a tax rate
	 */
	public function test__update_tax_rate() {
		global $wpdb;

		// Define a rate
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		// Run function
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// Update a rate
		$tax_rate = array(
			'tax_rate_country'  => 'US'
		);

		// Run function
		WC_Tax::_update_tax_rate( $tax_rate_id, $tax_rate );

		$this->assertNotFalse( $wpdb->last_result );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test deleting a tax rate
	 */
	public function test__delete_tax_rate() {
		global $wpdb;

		// Define a rate
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		// Run function
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// Run function
		WC_Tax::_delete_tax_rate( $tax_rate_id );

		$this->assertNotFalse( $wpdb->last_result );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Postcode saving
	 */
	public function test__update_tax_rate_postcodes() {
		global $wpdb;

		$to_save = '12345;90210-90215';
		$tax_rate          = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		// Run function
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $to_save );

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d ORDER BY location_code ASC", $tax_rate_id ) );

		$this->assertEquals( array( '12345', '90210', '90211', '90212', '90213', '90214', '90215' ), $results );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * City saving
	 */
	public function test__update_tax_rate_cities() {
		global $wpdb;

		$to_save  = 'SOMEWHERE;SOMEWHERE_ELSE';
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);

		// Run function
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		WC_Tax::_update_tax_rate_cities( $tax_rate_id, $to_save );

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d ORDER BY location_code ASC", $tax_rate_id ) );

		$this->assertEquals( array( 'SOMEWHERE', 'SOMEWHERE_ELSE' ), $results );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}
}
