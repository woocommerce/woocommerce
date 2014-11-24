<?php

class WC_Tests_Tax extends WC_Unit_Test_Case {

	/** @todo
	 * calc_tax
	 * calc_shipping_tax
	 * round
	 * find_rates
	 * find_shipping_rates
	 * get_customer_location
	 * get_rates
	 * get_base_tax_rates
	 * get_shipping_tax_rates
	 * is_compound
	 * get_rate_label
	 * get_rate_percent
	 * get_rate_code
	 * get_tax_total
	 * get_tax_classes
	 * _delete_tax_rate
	 * _update_tax_rate_postcodes
	 * _update_tax_rate_cities
	 * _update_tax_rate_locations
	 */

	/**
	 * Test Inserting a tax rate
	 */
	public function test__insert_tax_rate() {
		global $wpdb;

		// Define a rate
		$tax_rate = array(
			'tax_rate_country'  => "gb",
			'tax_rate_state'    => "",
			'tax_rate'          => "20",
			'tax_rate_name'     => "",
			'tax_rate_priority' => "1",
			'tax_rate_compound' => "0",
			'tax_rate_shipping' => "1",
			'tax_rate_order'    => "1",
			'tax_rate_class'    => ""
		);

		// Run function
		$result = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertGreaterThan( 0, $wpdb->insert_id );

		$new_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d", $wpdb->insert_id ) );

		$this->assertEquals( $new_row->tax_rate_country, "GB" );
		$this->assertEquals( $new_row->tax_rate_state, "" );
		$this->assertEquals( $new_row->tax_rate, "20.0000" );
		$this->assertEquals( $new_row->tax_rate_name, "Tax" );
		$this->assertEquals( $new_row->tax_rate_priority, "1" );
		$this->assertEquals( $new_row->tax_rate_compound, "0" );
		$this->assertEquals( $new_row->tax_rate_shipping, "1" );
		$this->assertEquals( $new_row->tax_rate_order, "1" );
		$this->assertEquals( $new_row->tax_rate_class, "" );
	}

	/**
	 * Test updating a tax rate
	 */
	public function test__update_tax_rate() {
		global $wpdb;

		// Define a rate
		$tax_rate = array(
			'tax_rate_country'  => "GB",
			'tax_rate_state'    => "",
			'tax_rate'          => "20.0000",
			'tax_rate_name'     => "VAT",
			'tax_rate_priority' => "1",
			'tax_rate_compound' => "0",
			'tax_rate_shipping' => "1",
			'tax_rate_order'    => "1",
			'tax_rate_class'    => ""
		);

		// Run function
		$result = WC_Tax::_insert_tax_rate( $tax_rate );
		$tax_rate_id = $wpdb->insert_id;

		// Update a rate
		$tax_rate = array(
			'tax_rate_country'  => "US"
		);

		// Run function
		WC_Tax::_update_tax_rate( $tax_rate_id, $tax_rate );

		$this->assertNotFalse( $wpdb->last_result );
	}

	/**
	 * Test deleting a tax rate
	 */
	public function test__delete_tax_rate() {
		global $wpdb;

		// Define a rate
		$tax_rate = array(
			'tax_rate_country'  => "GB",
			'tax_rate_state'    => "",
			'tax_rate'          => "20.0000",
			'tax_rate_name'     => "VAT",
			'tax_rate_priority' => "1",
			'tax_rate_compound' => "0",
			'tax_rate_shipping' => "1",
			'tax_rate_order'    => "1",
			'tax_rate_class'    => ""
		);

		// Run function
		$result = WC_Tax::_insert_tax_rate( $tax_rate );
		$tax_rate_id = $wpdb->insert_id;

		// Run function
		WC_Tax::_delete_tax_rate( $tax_rate_id );

		$this->assertNotFalse( $wpdb->last_result );
	}


}
