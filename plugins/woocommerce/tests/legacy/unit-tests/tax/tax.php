<?php
/**
 * Test tax functions.
 *
 * @package WooCommerce\Tests\Tax
 * @since   3.4.0
 */

use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * Class Tax.
 * @package WooCommerce\Tests\Tax
 */
class WC_Tests_Tax extends WC_Unit_Test_Case {

	/**
	 * Get rates.
	 */
	public function test_get_rates() {
		$customer_location = WC_Tax::get_tax_location();
		$tax_rate          = array(
			'tax_rate_country'  => $customer_location[0],
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

		$tax_rates = WC_Tax::get_rates();

		$this->assertSame(
			$tax_rates,
			array(
				$tax_rate_id => array(
					'rate'     => 20.0,
					'label'    => 'VAT',
					'shipping' => 'yes',
					'compound' => 'no',
				),
			)
		);

		WC_Tax::_delete_tax_rate( $tax_rate_id );

		$tax_rate_catch_all = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '0.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_catch_all_id = WC_Tax::_insert_tax_rate( $tax_rate_catch_all );

		$tax_rates = WC_Tax::get_rates();
		$this->assertSame(
			$tax_rates,
			array(
				$tax_rate_catch_all_id => array(
					'rate'     => 0.0,
					'label'    => 'VAT',
					'shipping' => 'yes',
					'compound' => 'no',
				),
			)
		);
	}

	/**
	 * Get rates.
	 */
	public function test_get_shipping_tax_rates() {
		$customer_location = WC_Tax::get_tax_location();
		$tax_rate          = array(
			'tax_rate_country'  => $customer_location[0],
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

		$tax_rates = WC_Tax::get_shipping_tax_rates();

		$this->assertEquals(
			$tax_rates,
			array(
				$tax_rate_id => array(
					'rate'     => '20.0000',
					'label'    => 'VAT',
					'shipping' => 'yes',
					'compound' => 'no',
				),
			),
			print_r( $tax_rates, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		);
	}

	/**
	 * Get rates.
	 */
	public function test_get_base_tax_rates() {
		$tax_rate = array(
			'tax_rate_country'  => 'US',
			'tax_rate_state'    => 'CA',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rates = WC_Tax::get_base_tax_rates();

		$this->assertEquals(
			$tax_rates,
			array(
				$tax_rate_id => array(
					'rate'     => '20.0000',
					'label'    => 'VAT',
					'shipping' => 'yes',
					'compound' => 'no',
				),
			)
		);
	}

	/**
	 * Find tax rates.
	 */
	public function test_find_rates() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
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

		$tax_rates = WC_Tax::find_rates(
			array(
				'country'   => 'GB',
				'state'     => 'Cambs',
				'postcode'  => 'PE14 1XX',
				'city'      => 'Somewhere',
				'tax_class' => '',
			)
		);

		$this->assertEquals(
			$tax_rates,
			array(
				$tax_rate_id => array(
					'rate'     => '20.0000',
					'label'    => 'VAT',
					'shipping' => 'yes',
					'compound' => 'no',
				),
			)
		);
	}

	/**
	 * Find tax rates.
	 */
	public function test_find_shipping_rates() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
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

		$tax_rates = WC_Tax::find_shipping_rates(
			array(
				'country'   => 'GB',
				'state'     => 'Cambs',
				'postcode'  => 'PE14 1XX',
				'city'      => 'Somewhere',
				'tax_class' => '',
			)
		);

		$this->assertEquals(
			$tax_rates,
			array(
				$tax_rate_id => array(
					'rate'     => '20.0000',
					'label'    => 'VAT',
					'shipping' => 'yes',
					'compound' => 'no',
				),
			)
		);
	}

	/**
	 * Test tax amounts.
	 */
	public function test_calc_tax() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
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

		$tax_rates = WC_Tax::find_rates(
			array(
				'country'   => 'GB',
				'state'     => 'Cambs',
				'postcode'  => 'PE14 1XX',
				'city'      => 'Somewhere',
				'tax_class' => '',
			)
		);

		$calced_tax = WC_Tax::calc_tax( '9.99', $tax_rates, true, false );

		$this->assertEquals( $calced_tax, array( $tax_rate_id => '1.665' ) );

		$calced_tax = WC_Tax::calc_tax( '9.99', $tax_rates, false, false );

		$this->assertEquals( $calced_tax, array( $tax_rate_id => '1.998' ) );
	}

	/**
	 * Test compound tax amounts
	 */
	public function test_calc_compound_tax() {
		update_option( 'woocommerce_default_country', 'CA' );
		update_option( 'woocommerce_default_state', 'QC' );

		$tax_rate_1 = array(
			'tax_rate_country'  => 'CA',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'GST',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_2 = array(
			'tax_rate_country'  => 'CA',
			'tax_rate_state'    => 'QC',
			'tax_rate'          => '8.5000',
			'tax_rate_name'     => 'PST',
			'tax_rate_priority' => '2',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '2',
			'tax_rate_class'    => '',
		);

		$tax_rate_1_id = WC_Tax::_insert_tax_rate( $tax_rate_1 );
		$tax_rate_2_id = WC_Tax::_insert_tax_rate( $tax_rate_2 );

		$tax_rates = WC_Tax::find_rates(
			array(
				'country'   => 'CA',
				'state'     => 'QC',
				'postcode'  => '12345',
				'city'      => '',
				'tax_class' => '',
			)
		);

		// prices exclusive of tax.
		$calced_tax = WC_Tax::calc_tax( '100', $tax_rates, false, false );
		$this->assertEquals(
			$calced_tax,
			array(
				$tax_rate_1_id => '5.0000',
				$tax_rate_2_id => '8.925',
			)
		);

		// prices inclusive of tax.
		$calced_tax = WC_Tax::calc_tax( '100', $tax_rates, true, false );
		/**
		 * 100 is inclusive of all taxes.
		 *
		 * Compound would be 100 - ( 100 / 1.085 ) = 7.8341.
		 * Next tax would be calced on 100 - 7.8341 = 92.1659.
		 * 92.1659 - ( 92.1659 / 1.05 ) = 4.38885.
		 */
		$this->assertEquals( NumberUtil::round( $calced_tax[ $tax_rate_1_id ], 4 ), 4.3889 );
		$this->assertEquals( NumberUtil::round( $calced_tax[ $tax_rate_2_id ], 4 ), 7.8341 );
	}

	/**
	 * Shipping tax amounts.
	 */
	public function test_calc_shipping_tax() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
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

		$tax_rates = WC_Tax::find_rates(
			array(
				'country'   => 'GB',
				'state'     => 'Cambs',
				'postcode'  => 'PE14 1XX',
				'city'      => 'Somewhere',
				'tax_class' => '',
			)
		);

		$calced_tax = WC_Tax::calc_shipping_tax( '10', $tax_rates );

		$this->assertEquals( $calced_tax, array( $tax_rate_id => '2' ) );
	}

	/**
	 * Test rate labels.
	 */
	public function test_get_rate_label() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertEquals( WC_Tax::get_rate_label( $tax_rate_id ), 'VAT' );
	}

	/**
	 * Test rate percent.
	 */
	public function test_get_rate_percent() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertEquals( WC_Tax::get_rate_percent( $tax_rate_id ), '20%' );
	}

	/**
	 * Test rate code.
	 */
	public function test_get_rate_code() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertEquals( WC_Tax::get_rate_code( $tax_rate_id ), 'GB-VAT-1' );
	}

	/**
	 * Test is compound.
	 */
	public function test_is_compound() {
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertTrue( WC_Tax::is_compound( $tax_rate_id ) );
	}

	/**
	 * Test the rounding method.
	 */
	public function test_round() {
		$this->assertEquals( WC_Tax::round( '2.123456789' ), '2.123457' );
	}

	/**
	 * Get tax totals.
	 */
	public function test_get_tax_total() {
		$to_total = array(
			'1' => '1.665',
			'2' => '2',
		);

		$this->assertEquals( WC_Tax::get_tax_total( $to_total ), '3.665' );
	}

	/**
	 * Test getting the tax classes.
	 */
	public function test_get_tax_classes() {
		$tax_classes = WC_Tax::get_tax_classes();
		$this->assertEquals( $tax_classes, array( 'Reduced rate', 'Zero rate' ) );

		$tax_class_slugs = WC_Tax::get_tax_class_slugs();
		$this->assertEquals( $tax_class_slugs, array( 'reduced-rate', 'zero-rate' ) );

		$tax_rate_classes = WC_Tax::get_tax_rate_classes();
		$this->assertEquals(
			$tax_rate_classes,
			array(
				(object) array(
					'tax_rate_class_id' => '1',
					'name'              => 'Reduced rate',
					'slug'              => 'reduced-rate',
				),
				(object) array(
					'tax_rate_class_id' => '2',
					'name'              => 'Zero rate',
					'slug'              => 'zero-rate',
				),
			)
		);
	}

	/**
	 * Test Inserting a tax rate.
	 */
	public function test__insert_tax_rate() {
		global $wpdb;

		// Define a rate.
		$tax_rate = array(
			'tax_rate_country'  => 'gb',
			'tax_rate_state'    => '',
			'tax_rate'          => '20',
			'tax_rate_name'     => '',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		// Run function.
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->assertGreaterThan( 0, $tax_rate_id );

		$new_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d",
				$tax_rate_id
			)
		);

		$this->assertEquals( $new_row->tax_rate_country, 'GB' );
		$this->assertEquals( $new_row->tax_rate_state, '' );
		$this->assertEquals( $new_row->tax_rate, '20.0000' );
		$this->assertEquals( $new_row->tax_rate_name, 'Tax' );
		$this->assertEquals( $new_row->tax_rate_priority, '1' );
		$this->assertEquals( $new_row->tax_rate_compound, '0' );
		$this->assertEquals( $new_row->tax_rate_shipping, '1' );
		$this->assertEquals( $new_row->tax_rate_order, '1' );
		$this->assertEquals( $new_row->tax_rate_class, '' );
	}

	/**
	 * Test updating a tax rate.
	 */
	public function test__update_tax_rate() {
		global $wpdb;

		// Define a rate.
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		// Run function.
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// Update a rate.
		$tax_rate = array(
			'tax_rate_country' => 'US',
		);

		// Run function.
		WC_Tax::_update_tax_rate( $tax_rate_id, $tax_rate );

		$this->assertNotFalse( $wpdb->last_result );
	}

	/**
	 * Test deleting a tax rate.
	 */
	public function test__delete_tax_rate() {
		global $wpdb;

		// Define a rate.
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		// Run function.
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// Run function.
		WC_Tax::_delete_tax_rate( $tax_rate_id );

		$this->assertNotFalse( $wpdb->last_result );
	}

	/**
	 * Postcode saving.
	 */
	public function test__update_tax_rate_postcodes() {
		global $wpdb;

		$to_save  = '12345;90210...90215';
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		// Run function.
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $to_save );

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d ORDER BY location_code ASC",
				$tax_rate_id
			)
		);

		$this->assertEquals( array( '12345', '90210...90215' ), $results );
	}

	/**
	 * City saving.
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
			'tax_rate_class'    => '',
		);

		// Run function.
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		WC_Tax::_update_tax_rate_cities( $tax_rate_id, $to_save );

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d ORDER BY location_code ASC",
				$tax_rate_id
			)
		);

		$this->assertEquals( array( 'SOMEWHERE', 'SOMEWHERE_ELSE' ), $results );
	}

}
