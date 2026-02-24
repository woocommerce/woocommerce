<?php

/**
 * Class WC_Product_Variable_Data_Store_CPT_Test
 */
class WC_Product_Variable_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * Helper filter to force prices inclusive of tax.
	 */
	public function __return_incl() {
		return 'incl';
	}

	/**
	 * @testdox Validation of prices data correctly identifies valid and invalid structures
	 */
	public function test_validate_prices_data() {
		$data_store      = new WC_Product_Variable_Data_Store_CPT();
		$current_version = '1234';
		$price_hash1     = 'f9e544f77b7eac7add281ef28ca5559f';
		$price_hash2     = 'a7c539f88b7eac7add281ef28ca5559f';

		// Test valid prices data with single hash structure.
		$valid_prices = array(
			$price_hash1 => array(
				'price'         => array(
					123 => '10.00',
					456 => '20.00',
				),
				'regular_price' => array(
					123 => '15.00',
					456 => '25.00',
				),
				'sale_price'    => array(
					123 => '10.00',
					456 => '20.00',
				),
			),
		);

		$this->assertTrue(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $valid_prices, $current_version ) ),
			'Valid prices data with single hash should pass validation'
		);

		// Test valid prices data with multiple hash structure.
		$valid_prices_multiple = array(
			$price_hash1 => array(
				'price'         => array(
					123 => '10.00',
					456 => '20.00',
				),
				'regular_price' => array(
					123 => '15.00',
					456 => '25.00',
				),
				'sale_price'    => array(
					123 => '10.00',
					456 => '20.00',
				),
			),
			$price_hash2 => array(
				'price'         => array(
					789 => '30.00',
					101 => '40.00',
				),
				'regular_price' => array(
					789 => '35.00',
					101 => '45.00',
				),
				'sale_price'    => array(
					789 => '30.00',
					101 => '40.00',
				),
			),
		);

		$this->assertTrue(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $valid_prices_multiple, $current_version ) ),
			'Valid prices data with multiple hashes should pass validation'
		);

		// Test invalid data type.
		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( 'not an array', $current_version ) ),
			'Non-array data should fail validation'
		);

		// Test valid prices data with empty sale prices.
		$valid_prices_empty_sale = array(
			$price_hash1 => array(
				'price'         => array(
					123 => '15.00',
					456 => '25.00',
				),
				'regular_price' => array(
					123 => '15.00',
					456 => '25.00',
				),
				'sale_price'    => array(
					123 => '',
					456 => '',
				),
			),
		);

		$this->assertTrue(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $valid_prices_empty_sale, $current_version ) ),
			'Valid prices data with empty sale prices should pass validation'
		);

		// Test valid prices data with mixed empty and set prices.
		$valid_prices_mixed = array(
			$price_hash1 => array(
				'price'         => array(
					123 => '10.00',
					456 => '25.00',
				),
				'regular_price' => array(
					123 => '15.00',
					456 => '25.00',
				),
				'sale_price'    => array(
					123 => '10.00',
					456 => '',  // No sale price for this variation.
				),
			),
		);

		$this->assertTrue(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $valid_prices_mixed, $current_version ) ),
			'Valid prices data with mixed empty and set sale prices should pass validation'
		);

		// Test invalid hash value type.
		$invalid_hash_value = array(
			'version'    => $current_version,
			$price_hash1 => 'not an array',
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $invalid_hash_value, $current_version ) ),
			'Non-array hash value should fail validation'
		);

		// Test missing required price types.
		$missing_price_types = array(
			$price_hash1 => array(
				'price' => array( 123 => '10.00' ),
				// missing regular_price and sale_price.
			),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $missing_price_types, $current_version ) ),
			'Data missing required price types should fail validation'
		);

		// Test invalid variation ID type.
		$invalid_variation_id = array(
			$price_hash1 => array(
				'price'         => array( 'not_numeric' => '10.00' ),
				'regular_price' => array( 'not_numeric' => '15.00' ),
				'sale_price'    => array( 'not_numeric' => '10.00' ),
			),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $invalid_variation_id, $current_version ) ),
			'Non-numeric variation IDs should fail validation'
		);

		// Test invalid price value type.
		$invalid_price_value = array(
			$price_hash1 => array(
				'price'         => array( 123 => 'not_numeric' ),
				'regular_price' => array( 123 => 'not_numeric' ),
				'sale_price'    => array( 123 => 'not_numeric' ),
			),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $invalid_price_value, $current_version ) ),
			'Non-numeric price values should fail validation'
		);

		// Test one valid hash and one invalid hash.
		$mixed_valid_invalid = array(
			$price_hash1 => array(
				'price'         => array( 123 => '10.00' ),
				'regular_price' => array( 123 => '15.00' ),
				'sale_price'    => array( 123 => '10.00' ),
			),
			$price_hash2 => array(
				'price'         => array( 'invalid' => 'not_numeric' ),
				'regular_price' => array( 'invalid' => 'not_numeric' ),
				'sale_price'    => array( 'invalid' => 'not_numeric' ),
			),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $mixed_valid_invalid, $current_version ) ),
			'Data with mix of valid and invalid hashes should fail validation'
		);

		// Test empty prices data with version (likely corrupt).
		$empty_prices_with_version = array(
			$price_hash1 => array(
				'price'         => array(),
				'regular_price' => array(),
				'sale_price'    => array(),
			),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $empty_prices_with_version, $current_version ) ),
			'Empty prices data with version should fail validation as likely corrupt'
		);

		// Test uninitialized prices data (new product, should pass).
		$uninitialized_prices = array();

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_prices_data', array( $uninitialized_prices, $current_version ) ),
			'Uninitialized prices data should pass validation as could be new product'
		);
	}

	/**
	 * @testdox Validation of children data correctly identifies valid and invalid structures
	 */
	public function test_validate_children_data() {
		$data_store      = new WC_Product_Variable_Data_Store_CPT();
		$current_version = '1234';

		// Test valid children data.
		$valid_children = array(
			'all'     => array( 123, 456, 789 ),
			'visible' => array( 123, 456 ),
		);

		$this->assertTrue(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $valid_children, $current_version ) ),
			'Valid children data should pass validation'
		);

		// Test invalid data type.
		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( 'not an array', $current_version ) ),
			'Non-array data should fail validation'
		);

		// Test missing required keys.
		$missing_keys = array(
			'all' => array( 123, 456 ),
			// missing 'visible' key.
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $missing_keys, $current_version ) ),
			'Data missing required keys should fail validation'
		);

		// Test invalid child ID type.
		$invalid_child_id = array(
			'all'     => array( 'not_numeric', 456 ),
			'visible' => array( 'not_numeric' ),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $invalid_child_id, $current_version ) ),
			'Non-numeric child IDs should fail validation'
		);

		// Test invalid arrays for all/visible.
		$invalid_arrays = array(
			'all'     => 'not an array',
			'visible' => 'not an array',
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $invalid_arrays, $current_version ) ),
			'Non-array values for all/visible should fail validation'
		);

		// Test empty children data with version (likely corrupt).
		$empty_children_with_version = array(
			'all'     => array(),
			'visible' => array(),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $empty_children_with_version, $current_version ) ),
			'Empty children data with version should fail validation as likely corrupt'
		);

		// Test empty children data without version (new product, should pass).
		$empty_children_no_version = array(
			'all'     => array(),
			'visible' => array(),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $empty_children_no_version, $current_version ) ),
			'Empty children data without version should fail validation as likely corrupt'
		);

		// Test uninitialized children data.
		$uninitialized_children = array();

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $uninitialized_children, $current_version ) ),
			'Uninitialized children data should fail validation to trigger rebuild'
		);
	}

	/**
	 * Helper method to call protected/private methods.
	 *
	 * @param object $obj         Object instance.
	 * @param string $method_name Method name to call.
	 * @param array  $parameters  Array of parameters to pass to method.
	 *
	 * @return mixed Method return value.
	 */
	protected function invokeMethod( $obj, $method_name, $parameters = array() ) {
		$reflection = new \ReflectionClass( get_class( $obj ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $obj, $parameters );
	}

	/**
	 * @testdox Variation price cache accounts for Customer VAT exemption.
	 */
	public function test_variation_price_cache_vat_exempt() {
		// Set store to include tax in price display.
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_prices_include_tax', '__return_true' );
		add_filter( 'pre_option_woocommerce_tax_display_shop', array( $this, '__return_incl' ) );
		add_filter( 'pre_option_woocommerce_tax_display_cart', array( $this, '__return_incl' ) );

		// Create tax rate.
		$tax_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create our variable product.
		$product = WC_Helper_Product::create_variation_product();

		// Verify that a VAT exempt customer gets prices with tax removed.
		WC()->customer->set_is_vat_exempt( true );

		$prices_no_tax    = array( '9.09', '13.64', '14.55', '15.45', '16.36', '17.27' );
		$variation_prices = $product->get_variation_prices( true );

		$this->assertEquals( $prices_no_tax, array_values( $variation_prices['price'] ) );

		// Verify that a normal customer gets prices with tax included.
		// This indirectly proves that the customer's VAT exemption influences the cache key.
		WC()->customer->set_is_vat_exempt( false );

		$prices_with_tax  = array( '10.00', '15.00', '16.00', '17.00', '18.00', '19.00' );
		$variation_prices = $product->get_variation_prices( true );

		$this->assertEquals( $prices_with_tax, array_values( $variation_prices['price'] ) );

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_id );

		remove_filter( 'wc_tax_enabled', '__return_true' );
		remove_filter( 'woocommerce_prices_include_tax', '__return_true' );
		remove_filter( 'pre_option_woocommerce_tax_display_shop', array( $this, '__return_incl' ) );
		remove_filter( 'pre_option_woocommerce_tax_display_cart', array( $this, '__return_incl' ) );
	}

	/**
	 * @testdox Test read_children method handles various scenarios correctly including invalid transient data
	 */
	public function test_read_children() {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();

		// Set invalid transient data.
		$invalid_data = 'not an array';
		set_transient( 'wc_product_children_' . $product->get_id(), $invalid_data );

		// Test read still works with invalid transient.
		$children = $data_store->read_children( $product, false );
		$this->assertIsArray( $children );
		$this->assertArrayHasKey( 'all', $children );
		$this->assertArrayHasKey( 'visible', $children );
		$this->assertNotEmpty( $children['all'] );

		// Set corrupt transient data.
		$corrupt_data = array(
			'version' => 'wrong_version',
			'all'     => 'not an array',
			'visible' => array(),
		);
		set_transient( 'wc_product_children_' . $product->get_id(), wp_json_encode( $corrupt_data ) );

		// Test read still works with corrupt transient.
		$children_after_corrupt = $data_store->read_children( $product, false );
		$this->assertEquals( $children, $children_after_corrupt, 'Should return correct data even with corrupt transient' );
	}

	/**
	 * @testdox Test read_price_data method handles various pricing scenarios including invalid transient data
	 */
	public function test_read_price_data() {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();

		// Get initial valid price data.
		$initial_prices = $data_store->read_price_data( $product, false );

		// Set invalid transient data.
		$transient_name = 'wc_var_prices_' . $product->get_id();
		set_transient( $transient_name, 'invalid data' );

		// Test read still works with invalid transient.
		$prices_after_invalid = $data_store->read_price_data( $product, false );
		$this->assertEquals(
			$initial_prices,
			$prices_after_invalid,
			'Should return correct prices even with invalid transient'
		);

		// Set corrupt transient data.
		$corrupt_data = array(
			'version'    => 'wrong_version',
			'price_hash' => array(
				'price'         => 'not an array',
				'regular_price' => array(),
				'sale_price'    => array(),
			),
		);
		set_transient( $transient_name, wp_json_encode( $corrupt_data ) );

		// Test read still works with corrupt transient.
		$prices_after_corrupt = $data_store->read_price_data( $product, false );
		$this->assertArrayHasKey( 'price', $prices_after_corrupt );
		$this->assertArrayHasKey( 'regular_price', $prices_after_corrupt );
		$this->assertArrayHasKey( 'sale_price', $prices_after_corrupt );
		$this->assertEquals(
			$initial_prices,
			$prices_after_corrupt,
			'Should return correct prices even with corrupt transient'
		);
	}

	/**
	 * @testdox read_prices caches both prices for display and not for display when prices are the same in both cases.
	 *
	 * @testWith [false, true, true, false]
	 *           [false, false, true, false]
	 *           [true, true, false, false]
	 *           [true, true, true, true]
	 *
	 * @param bool $tax_enabled Taxes enabled shop-wide or not.
	 * @param bool $taxable_product Product is taxable or not.
	 * @param bool $tax_has_rates Product tax has defined rates or not.
	 * @param bool $user_vat_exempt User is VAT exempt or not.
	 */
	public function test_read_prices_cache_when_taxes_dont_influence_price( bool $tax_enabled, bool $taxable_product, bool $tax_has_rates, bool $user_vat_exempt ) {
		add_filter( 'wc_tax_enabled', $tax_enabled ? '__return_true' : '__return_false' );
		add_filter( 'woocommerce_product_is_taxable', $taxable_product ? '__return_true' : '__return_false' );
		add_filter( 'woocommerce_matched_rates', $tax_has_rates ? array( $this, '__return_rates' ) : '__return_empty_array' );
		WC()->customer->set_is_vat_exempt( $user_vat_exempt );

		$data_store     = new WC_Product_Variable_Data_Store_CPT();
		$product        = WC_Helper_Product::create_variation_product();
		$transient_name = 'wc_var_prices_' . $product->get_id();
		delete_transient( $transient_name );

		$extended_data_store = $this->get_data_store_with_public_get_price_hash();

		$expected_hashes = array_unique( array( $extended_data_store->get_price_hash( $product, true ), $extended_data_store->get_price_hash( $product, false ) ) );
		sort( $expected_hashes );

		delete_transient( $transient_name );
		$data_store->read_price_data( $product, false );
		$actual_hashes = array_unique( $this->get_keys_for_json_encoded_transient( $transient_name ) );
		sort( $actual_hashes );
		$this->assertEquals( $expected_hashes, $actual_hashes );

		$data_store = new WC_Product_Variable_Data_Store_CPT();
		delete_transient( $transient_name );
		$data_store->read_price_data( $product, false );
		$actual_hashes = array_unique( $this->get_keys_for_json_encoded_transient( $transient_name ) );
		sort( $actual_hashes );
		$this->assertEquals( $expected_hashes, $actual_hashes );

		remove_filter( 'wc_tax_enabled', $tax_enabled ? '__return_true' : '__return_false' );
		remove_filter( 'woocommerce_product_is_taxable', $taxable_product ? '__return_true' : '__return_false' );
		remove_filter( 'woocommerce_matched_rates', $tax_has_rates ? array( $this, '__return_rates' ) : '__return_empty_array' );
	}

	/**
	 * @testdox read_prices does separate caching for prices for display and not for display when they are different.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $for_display Test getting prices for display or not for display.
	 */
	public function test_read_prices_cache_when_taxes_influence_price( bool $for_display ) {
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_product_is_taxable', '__return_true' );
		add_filter( 'woocommerce_matched_rates', array( $this, '__return_rates' ) );
		WC()->customer->set_is_vat_exempt( false );

		$data_store     = new WC_Product_Variable_Data_Store_CPT();
		$product        = WC_Helper_Product::create_variation_product();
		$transient_name = 'wc_var_prices_' . $product->get_id();
		delete_transient( $transient_name );

		$extended_data_store = $this->get_data_store_with_public_get_price_hash();

		delete_transient( $transient_name );
		$data_store->read_price_data( $product, $for_display );
		$expected_hashes = array( $extended_data_store->get_price_hash( $product, $for_display ) );
		$actual_hashes   = array_unique( $this->get_keys_for_json_encoded_transient( $transient_name ) );
		$this->assertEquals( $expected_hashes, $actual_hashes );

		remove_filter( 'wc_tax_enabled', '__return_true' );
		remove_filter( 'woocommerce_product_is_taxable', '__return_true' );
		remove_filter( 'woocommerce_matched_rates', array( $this, '__return_rates' ) );
	}

	/**
	 * @testdox read_prices skips unified caching if code hooked to woocommerce_variation_prices_array modifies the prices array.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hook_modifies_prices The hooked code modifies the prices array or not.
	 * @return void
	 */
	public function test_read_prices_cache_when_taxes_dont_influence_price_plus_hook( bool $hook_modifies_prices ) {
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_product_is_taxable', '__return_false' );
		add_filter( 'woocommerce_matched_rates', array( $this, '__return_rates' ) );
		WC()->customer->set_is_vat_exempt( false );

		add_filter(
			'woocommerce_variation_prices_array',
			function ( $prices_array, $variation, $for_display ) use ( $hook_modifies_prices ) {
				if ( $hook_modifies_prices ) {
					$prices_array['foobar'] = $for_display;
				}
				return $prices_array;
			},
			10,
			3
		);

		$data_store     = new WC_Product_Variable_Data_Store_CPT();
		$product        = WC_Helper_Product::create_variation_product();
		$transient_name = 'wc_var_prices_' . $product->get_id();
		delete_transient( $transient_name );

		$extended_data_store = $this->get_data_store_with_public_get_price_hash();

		$data_store->read_price_data( $product, true );
		$actual_hashes   = array_unique( $this->get_keys_for_json_encoded_transient( $transient_name ) );
		$expected_hashes =
			$hook_modifies_prices ?
			array( $extended_data_store->get_price_hash( $product, true ) ) :
			array( $extended_data_store->get_price_hash( $product, true ), $extended_data_store->get_price_hash( $product, false ) );
		$this->assertEquals( $expected_hashes, $actual_hashes );

		remove_all_filters( 'woocommerce_variation_prices_array' );
		remove_filter( 'wc_tax_enabled', '__return_true' );
		remove_filter( 'woocommerce_product_is_taxable', '__return_false' );
		remove_filter( 'woocommerce_matched_rates', array( $this, '__return_rates' ) );
	}

	/**
	 * Get an instance of WC_Product_Variable_Data_Store_CPT whose get_price_hash method is public.
	 *
	 * @return WC_Product_Variable_Data_Store_CPT
	 */
	private function get_data_store_with_public_get_price_hash(): object {
		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		return new class() extends WC_Product_Variable_Data_Store_CPT {
			public function get_price_hash( &$product, $for_display = false ) {
				return parent::get_price_hash( $product, $for_display );
			}
		};
		// phpcs:enable Generic.CodeAnalysis, Squiz.Commenting
	}

	/**
	 * Parse a variable product prices transient and return the hashes only.
	 *
	 * @param string $transient_name Name of the transient to parse.
	 * @return array
	 */
	private function get_keys_for_json_encoded_transient( string $transient_name ): array {
		return array_keys( array_filter( (array) json_decode( strval( get_transient( $transient_name ) ), true ) ) );
	}

	/**
	 * Return dummy tax rates.
	 *
	 * @return array
	 */
	public function __return_rates() {
		return array(
			'rate'     => 10,
			'label'    => 'rate',
			'shipping' => 'no',
			'compund'  => 'no',
		);
	}

	/**
	 * @testdox Test read_price_data method works even when price validation fails
	 */
	public function test_read_price_data_with_validation_failure() {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();

		// Get initial valid price data.
		$initial_prices = $data_store->read_price_data( $product, false );

		// Create a mock that will force validation to fail.
		$mock_data_store = $this->getMockBuilder( WC_Product_Variable_Data_Store_CPT::class )
			->setMethods( array( 'validate_prices_data' ) )
			->getMock();

		$mock_data_store->method( 'validate_prices_data' )
			->willReturn( false );

		// Clear any existing transient.
		delete_transient( 'wc_var_prices_' . $product->get_id() );

		// Read prices with the mock that will fail validation.
		$prices_with_failed_validation = $mock_data_store->read_price_data( $product, false );

		// Verify the data is still correct despite validation failing.
		$this->assertArrayHasKey( 'price', $prices_with_failed_validation );
		$this->assertArrayHasKey( 'regular_price', $prices_with_failed_validation );
		$this->assertArrayHasKey( 'sale_price', $prices_with_failed_validation );
		$this->assertEquals(
			$initial_prices,
			$prices_with_failed_validation,
			'Should return correct prices even when validation fails'
		);

		// Verify the transient was not set.
		$this->assertFalse(
			get_transient( 'wc_var_prices_' . $product->get_id() ),
			'Transient should not be set when validation fails'
		);
	}

	/**
	 * @testdox Test read_children method works even when validation fails
	 */
	public function test_read_children_with_validation_failure() {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();

		// Get initial valid children data.
		$initial_children = $data_store->read_children( $product, false );

		// Create a mock that will force validation to fail.
		$mock_data_store = $this->getMockBuilder( WC_Product_Variable_Data_Store_CPT::class )
			->setMethods( array( 'validate_children_data' ) )
			->getMock();

		$mock_data_store->method( 'validate_children_data' )
			->willReturn( false );

		// Clear any existing transient.
		delete_transient( 'wc_product_children_' . $product->get_id() );

		// Read children with the mock that will fail validation.
		$children_with_failed_validation = $mock_data_store->read_children( $product, false );

		// Verify the data is still correct despite validation failing.
		$this->assertEquals(
			$initial_children,
			$children_with_failed_validation,
			'Should return correct children even when validation fails'
		);

		// Verify the transient was not set.
		$this->assertFalse(
			get_transient( 'wc_product_children_' . $product->get_id() ),
			'Transient should not be set when validation fails'
		);
	}

	/**
	 * Tests `read_attributes` for handling metas migration due to sanitize_title BC breaks.
	 */
	public function test_read_attributes_addresses_bc_break_in_sanitize(): void {
		$product    = WC_Helper_Product::create_variation_product();
		$product_id = $product->get_id();
		$child_ids  = array_values( $product->get_children() );

		// Patch up the metas to match pre-BC state.
		$attributes                      = get_post_meta( $product_id, '_product_attributes', true );
		$attributes['Size/Size']         = $attributes['pa_size'];
		$attributes['Size/Size']['name'] = 'Size/Size';
		unset( $attributes['pa_size'] );
		update_post_meta( $product_id, '_product_attributes', $attributes );
		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, 'attribute_Size/Size', get_post_meta( $child_id, 'attribute_pa_size', true ) );
			delete_post_meta( $child_id, 'attribute_pa_size' );
		}

		// Reload the product object, so the migration is executed.
		$product = wc_get_product( $product_id );

		// Verify the migrated entries and cleanup.
		$sizes = array( 'small', 'large', 'huge', 'huge', 'huge', 'huge' );
		foreach ( $child_ids as $index => $child_id ) {
			$this->assertSame( $sizes[ $index ], get_post_meta( $child_id, 'attribute_size-size', true ) );
			$this->assertSame( $sizes[ $index ], get_post_meta( $child_id, 'attribute_Size/Size', true ) );
		}
		$product->delete();
	}
}
