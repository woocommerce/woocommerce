<?php

use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Class WC_Product_Variable_Data_Store_CPT_Test
 */
class WC_Product_Variable_Data_Store_CPT_Test extends WC_Unit_Test_Case {
	/**
	 * Variable product shared by the class.
	 *
	 * @var int
	 */
	private static $product_id;

	/**
	 * Attribute taxonomy IDs owned by the class fixture.
	 *
	 * @var int[]
	 */
	private static $attribute_ids = array();

	/**
	 * Whether an attribute deletion rewrite flush was already scheduled.
	 *
	 * @var bool
	 */
	private static $had_scheduled_rewrite_flush;

	/**
	 * Create the variable product fixture shared by all test methods.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::enable_direct_product_attribute_lookup_updates();

		try {
			$existing_attribute_ids            = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_id' );
			$product                           = WC_Helper_Product::create_variation_product();
			self::$product_id                  = $product->get_id();
			self::$attribute_ids               = array_values( array_diff( wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_id' ), $existing_attribute_ids ) );
			self::$had_scheduled_rewrite_flush = false !== wp_next_scheduled( 'woocommerce_flush_rewrite_rules' );
		} finally {
			self::disable_direct_product_attribute_lookup_updates();
		}
	}

	/**
	 * Delete the class-owned variable product through its data store.
	 */
	public static function wpTearDownAfterClass(): void {
		global $wc_product_attributes;

		self::enable_direct_product_attribute_lookup_updates();

		try {
			$product = wc_get_product( self::$product_id );
			if ( $product ) {
				$product->delete( true );
			}

			foreach ( self::$attribute_ids as $attribute_id ) {
				$attribute = wc_get_attribute( $attribute_id );
				$taxonomy  = $attribute ? $attribute->slug : '';

				wc_delete_attribute( $attribute_id );

				if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
					unregister_taxonomy( $taxonomy );
				}
				unset( $wc_product_attributes[ $taxonomy ] );
			}

			if ( ! self::$had_scheduled_rewrite_flush ) {
				wp_clear_scheduled_hook( 'woocommerce_flush_rewrite_rules' );
			}
		} finally {
			self::disable_direct_product_attribute_lookup_updates();
		}
	}

	/**
	 * Reload the class-owned variable product for the current transaction.
	 *
	 * @return WC_Product_Variable
	 */
	private function get_variation_product_fixture(): WC_Product_Variable {
		$product = wc_get_product( self::$product_id );
		if ( ! $product instanceof WC_Product_Variable ) {
			throw new RuntimeException( 'Unable to load the variable product fixture.' );
		}

		return $product;
	}

	/**
	 * Cleans up global state that individual tests may leave behind.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_product_lookup_table_is_generating' );
		parent::tearDown();
	}

	/**
	 * Provides two cases: one where the product lookup table is available, and one where it is
	 * still being generated and the code must fall back to postmeta queries.
	 *
	 * @return array[]
	 */
	public function provider_lookup_table_generating(): array {
		return array(
			'lookup table available'                      => array( false ),
			'lookup table generating (postmeta fallback)' => array( true ),
		);
	}

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
			'Uninitialized prices data should fail validation to trigger a rebuild'
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

		// Test empty children data without a stored version (no prior cache entry).
		$empty_children_no_version = array(
			'all'     => array(),
			'visible' => array(),
		);

		$this->assertFalse(
			$this->invokeMethod( $data_store, 'validate_children_data', array( $empty_children_no_version, null ) ),
			'Empty children data without a stored version should fail validation to trigger a rebuild'
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
		$product = $this->get_variation_product_fixture();

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
		$product    = $this->get_variation_product_fixture();

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
		$product    = $this->get_variation_product_fixture();

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
		$product        = $this->get_variation_product_fixture();
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

		// Restore default state to avoid leaking into subsequent tests.
		WC()->customer->set_is_vat_exempt( false );

		remove_filter( 'wc_tax_enabled', $tax_enabled ? '__return_true' : '__return_false' );
		remove_filter( 'woocommerce_product_is_taxable', $taxable_product ? '__return_true' : '__return_false' );
		remove_filter( 'woocommerce_matched_rates', $tax_has_rates ? array( $this, '__return_rates' ) : '__return_empty_array' );
	}

	/**
	 * @testdox taxes_influence_price returns true even when customer is VAT exempt, because exempt prices differ from non-exempt.
	 */
	public function test_taxes_influence_price_returns_true_for_vat_exempt() {
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_product_is_taxable', '__return_true' );
		add_filter( 'woocommerce_matched_rates', array( $this, '__return_rates' ) );

		$product = $this->get_variation_product_fixture();

		$extended_data_store = $this->get_data_store_with_public_taxes_influence_price();

		// Non-exempt: taxes should influence price.
		WC()->customer->set_is_vat_exempt( false );
		$this->assertTrue( $extended_data_store->taxes_influence_price( $product ) );

		// VAT exempt: taxes should STILL influence price because the displayed
		// prices are different (tax removed), requiring separate cache entries.
		WC()->customer->set_is_vat_exempt( true );
		$this->assertTrue( $extended_data_store->taxes_influence_price( $product ) );

		// Restore default state to avoid leaking into other tests.
		WC()->customer->set_is_vat_exempt( false );

		remove_filter( 'wc_tax_enabled', '__return_true' );
		remove_filter( 'woocommerce_product_is_taxable', '__return_true' );
		remove_filter( 'woocommerce_matched_rates', array( $this, '__return_rates' ) );
	}

	/**
	 * @testdox The woocommerce_variable_product_taxes_influence_price filter can override the default decision.
	 *
	 * @testWith [true,  true,  true,  true,  true]
	 *           [true,  true,  true,  false, false]
	 *           [false, true,  false, true,  true]
	 *           [true,  false, false, true,  true]
	 *           [false, false, false, true,  true]
	 *           [true,  false, false, false, false]
	 *
	 * @param bool $taxable_product  Product is taxable or not.
	 * @param bool $tax_has_rates    Product tax has defined rates or not.
	 * @param bool $expected_default Value the filter callback should receive as the default decision.
	 * @param bool $filter_returns   Value the filter callback will return.
	 * @param bool $expected         Expected return value from taxes_influence_price.
	 */
	public function test_taxes_influence_price_filter_overrides_default( bool $taxable_product, bool $tax_has_rates, bool $expected_default, bool $filter_returns, bool $expected ) {
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_product_is_taxable', $taxable_product ? '__return_true' : '__return_false' );
		add_filter( 'woocommerce_matched_rates', $tax_has_rates ? array( $this, '__return_rates' ) : '__return_empty_array' );

		$received_default = null;
		$received_product = null;
		$filter_callback  = function ( $default_value, $product ) use ( $filter_returns, &$received_default, &$received_product ) {
			$received_default = $default_value;
			$received_product = $product;
			return $filter_returns;
		};
		add_filter( 'woocommerce_variable_product_taxes_influence_price', $filter_callback, 10, 2 );

		$product             = $this->get_variation_product_fixture();
		$extended_data_store = $this->get_data_store_with_public_taxes_influence_price();

		$this->assertSame( $expected, $extended_data_store->taxes_influence_price( $product ) );
		$this->assertSame( $expected_default, $received_default, 'Filter callback should receive the default decision.' );
		$this->assertSame( $product->get_id(), $received_product->get_id(), 'Filter callback should receive the product being evaluated.' );

		remove_filter( 'woocommerce_variable_product_taxes_influence_price', $filter_callback, 10 );
		remove_filter( 'wc_tax_enabled', '__return_true' );
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
		$product        = $this->get_variation_product_fixture();
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
		$product        = $this->get_variation_product_fixture();
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
	 * @testdox get_price_hash includes callback signatures via CallbackUtil when the legacy algorithm is disabled.
	 */
	public function test_get_price_hash_uses_callback_util_when_legacy_algorithm_is_disabled(): void {
		add_filter( 'woocommerce_use_legacy_get_variations_price_hash', '__return_false' );

		$product             = WC_Helper_Product::create_variation_product();
		$extended_data_store = $this->get_data_store_with_public_get_price_hash();

		$hash_without_callback = $extended_data_store->get_price_hash( $product, false );

		$callback = static fn( $price ) => $price;
		add_filter( 'woocommerce_variation_prices_price', $callback );

		$hash_with_callback = $extended_data_store->get_price_hash( $product, false );

		// Adding a price callback must change the hash, proving the CallbackUtil path
		// is active and captures new hook registrations.
		$this->assertNotSame( $hash_without_callback, $hash_with_callback );

		remove_filter( 'woocommerce_variation_prices_price', $callback );
		remove_filter( 'woocommerce_use_legacy_get_variations_price_hash', '__return_false' );

		$product->delete();
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
	 * Get a data store instance with taxes_influence_price() exposed as public.
	 *
	 * @return object Data store with public taxes_influence_price method.
	 */
	private function get_data_store_with_public_taxes_influence_price(): object {
		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		return new class() extends WC_Product_Variable_Data_Store_CPT {
			public function taxes_influence_price( $product ): bool {
				return parent::taxes_influence_price( $product );
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
			'compound' => 'no',
		);
	}

	/**
	 * @testdox Test read_price_data method works even when price validation fails
	 */
	public function test_read_price_data_with_validation_failure() {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = $this->get_variation_product_fixture();

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
		$product    = $this->get_variation_product_fixture();

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
	 * @testdox read_attributes migrates child variation meta keys affected by the sanitize_title BC break.
	 */
	public function test_read_attributes_addresses_bc_break_in_sanitize(): void {
		$product    = $this->get_variation_product_fixture();
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
	}

	/**
	 * @testdox read_attributes skips the child meta migration DB query when the product has no children.
	 */
	public function test_read_attributes_handles_bc_break_migration_when_no_children(): void {
		$product = new WC_Product_Variable();
		$product->set_name( 'Dummy Variable Product' );
		$product->save();
		$product_id = $product->get_id();

		// Simulate a pre-BC-break attribute key containing a slash (is_variation required to trigger migration).
		update_post_meta(
			$product_id,
			'_product_attributes',
			array(
				'Size/Size' => array(
					'name'         => 'Size/Size',
					'value'        => 'small | large',
					'position'     => 0,
					'is_visible'   => 1,
					'is_variation' => 1,
					'is_taxonomy'  => 0,
				),
			)
		);

		// Reload — no children to migrate; should load without a DB error.
		$product = wc_get_product( $product_id );

		$this->assertInstanceOf( WC_Product_Variable::class, $product );

		$product->delete();
	}

	/**
	 * @testdox read_attributes forces update_attributes() even when no child meta rows need migrating.
	 */
	public function test_read_attributes_bc_migration_runs_force_update_when_no_old_rows_exist(): void {
		$product    = WC_Helper_Product::create_variation_product();
		$product_id = $product->get_id();

		// Inject a slash-containing attribute key that triggers the BC migration condition.
		// No child has an 'attribute_Size/Size' meta row, so $old_meta_rows will be empty.
		update_post_meta(
			$product_id,
			'_product_attributes',
			array(
				'Size/Size' => array(
					'name'         => 'Size/Size',
					'value'        => 'small | large',
					'position'     => 0,
					'is_visible'   => 1,
					'is_variation' => 1,
					'is_taxonomy'  => 0,
				),
			)
		);

		// Reload — $force_update = true fires even though no rows were migrated, so
		// update_attributes() must re-persist the attribute with the sanitised key.
		$product = wc_get_product( $product_id );

		// The attribute must survive intact.
		$attributes = $product->get_attributes();
		$this->assertCount( 1, $attributes );
		$this->assertSame( 'Size/Size', array_values( $attributes )[0]->get_name() );

		// update_attributes() must have re-written _product_attributes with the normalised key.
		$stored = get_post_meta( $product_id, '_product_attributes', true );
		$this->assertArrayHasKey( sanitize_title( 'Size/Size' ), $stored );
		$this->assertArrayNotHasKey( 'Size/Size', $stored );

		$product->delete();
	}

	/**
	 * @testdox read_attributes silently skips an attribute whose taxonomy is no longer registered.
	 */
	public function test_read_attributes_skips_unknown_taxonomy(): void {
		$product    = WC_Helper_Product::create_variation_product();
		$product_id = $product->get_id();

		// Inject a stale taxonomy attribute that is no longer registered.
		$stored                   = get_post_meta( $product_id, '_product_attributes', true );
		$stored['pa_nonexistent'] = array(
			'name'         => 'pa_nonexistent',
			'value'        => '',
			'position'     => 1,
			'is_visible'   => 1,
			'is_variation' => 0,
			'is_taxonomy'  => 1,
		);
		update_post_meta( $product_id, '_product_attributes', $stored );

		// Reload — the unknown taxonomy must not surface as an attribute.
		$product         = wc_get_product( $product_id );
		$attribute_names = array_map( static fn( $attribute ) => $attribute->get_name(), $product->get_attributes() );

		$this->assertNotContains( 'pa_nonexistent', $attribute_names );

		$product->delete();
	}

	/**
	 * @testdox read_attributes loads a text (non-taxonomy) attribute from the stored meta value string.
	 */
	public function test_read_attributes_returns_text_attribute(): void {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'Small', 'Medium', 'Large' ) );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Dummy Variable Product' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		// Reload from DB so read_attributes() runs the non-taxonomy branch.
		$product    = wc_get_product( $product->get_id() );
		$attributes = $product->get_attributes();

		$this->assertCount( 1, $attributes );
		$loaded = array_values( $attributes )[0];
		$this->assertSame( 0, $loaded->get_id() );
		$this->assertSame( 'Size', $loaded->get_name() );
		$this->assertSame( array( 'Small', 'Medium', 'Large' ), $loaded->get_options() );

		$product->delete();
	}

	/**
	 * @testdox read_product_data does not record variable product transient names in the notoptions cache when a persistent object cache is in use.
	 */
	public function test_read_product_data_does_not_prime_transients_with_object_cache() {
		$product    = $this->get_variation_product_fixture();
		$product_id = $product->get_id();

		$option_names = array(
			'_transient_wc_var_prices_' . $product_id,
			'_transient_timeout_wc_var_prices_' . $product_id,
			'_transient_wc_product_children_' . $product_id,
			'_transient_timeout_wc_product_children_' . $product_id,
		);

		// Simulate a persistent object cache and start from a clean notoptions cache.
		$previous = wp_using_ext_object_cache( true );

		try {
			wp_cache_delete( 'notoptions', 'options' );

			// Force a fresh read so read_product_data() runs.
			$data_store = new WC_Product_Variable_Data_Store_CPT();
			$fresh      = new WC_Product_Variable();
			$fresh->set_id( $product_id );
			$data_store->read( $fresh );

			$notoptions = wp_cache_get( 'notoptions', 'options' );
		} finally {
			// Always restore. Cast to bool because wp_using_ext_object_cache( null ) is a
			// no-op, which would otherwise leak the simulated true state into later tests.
			wp_using_ext_object_cache( (bool) $previous );
		}

		$notoptions = is_array( $notoptions ) ? $notoptions : array();
		foreach ( $option_names as $option_name ) {
			$this->assertArrayNotHasKey(
				$option_name,
				$notoptions,
				'Variable product transient option names must not be added to notoptions when a persistent object cache is active.'
			);
		}
	}

	/**
	 * @testdox read_variation_attributes fetches all variation attribute values from DB on cache miss.
	 */
	public function test_read_variation_attributes_fetches_on_cache_miss(): void {
		$product    = WC_Helper_Product::create_variation_product();
		$product_id = $product->get_id();

		$cache_key = WC_Cache_Helper::get_cache_prefix( 'product_' . $product_id ) . 'product_variation_attributes_' . $product_id;
		wp_cache_delete( $cache_key, 'products' );

		$attributes = ( new WC_Product_Variable_Data_Store_CPT() )->read_variation_attributes( $product );

		$this->assertSame(
			array(
				'pa_size'   => array( 'small', 'large', 'huge' ),
				'pa_colour' => array(
					0 => 'red',
					2 => 'blue',
				),
				'pa_number' => array( '0', '1', '2' ),
			),
			$attributes
		);

		$product->delete();
	}

	/**
	 * @testdox read_variation_attributes returns the cached result on a second call.
	 */
	public function test_read_variation_attributes_returns_cached_result(): void {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();
		$product_id = $product->get_id();

		$cache_key = WC_Cache_Helper::get_cache_prefix( 'product_' . $product_id ) . 'product_variation_attributes_' . $product_id;
		wp_cache_delete( $cache_key, 'products' );

		$first  = $data_store->read_variation_attributes( $product );
		$second = $data_store->read_variation_attributes( $product );

		$this->assertSame( $first, $second );
		$this->assertSame( $first, wp_cache_get( $cache_key, 'products' ) );

		$product->delete();
	}

	/**
	 * @testdox read_variation_attributes returns an empty array when the product has no variation attributes.
	 */
	public function test_read_variation_attributes_returns_empty_for_no_attributes(): void {
		$product = new WC_Product_Variable();
		$product->save();

		$this->assertSame( array(), ( new WC_Product_Variable_Data_Store_CPT() )->read_variation_attributes( $product ) );

		$product->delete();
	}

	/**
	 * @testdox read_variation_attributes falls back to all taxonomy terms when the product has variation attributes but no children.
	 */
	public function test_read_variation_attributes_returns_all_terms_when_no_children(): void {
		$product = new WC_Product_Variable();
		$product->set_attributes( array( WC_Helper_Product::create_product_attribute_object( 'pattern', array( 'dots', 'stripes' ) ) ) );
		$product->save();

		$attributes = ( new WC_Product_Variable_Data_Store_CPT() )->read_variation_attributes( $product );

		$this->assertSame( array( 'dots', 'stripes' ), $attributes['pa_pattern'] );

		$product->delete();
	}

	/**
	 * @testdox read_variation_attributes returns only the assigned values for a non-taxonomy text attribute.
	 */
	public function test_read_variation_attributes_returns_assigned_text_attribute_values(): void {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'Small', 'Large', 'XL' ) );
		$attribute->set_variation( true );
		$attribute->set_id( 0 );

		$product = new WC_Product_Variable();
		$product->set_attributes( array( $attribute ) );
		$product->save();
		$product_id = $product->get_id();

		WC_Helper_Product::create_product_variation_object(
			$product_id,
			'DUMMY SKU TEXT ATTR',
			10,
			array( 'size' => 'Small' )
		);

		$product   = wc_get_product( $product_id );
		$cache_key = WC_Cache_Helper::get_cache_prefix( 'product_' . $product_id ) . 'product_variation_attributes_' . $product_id;
		wp_cache_delete( $cache_key, 'products' );

		$attributes = ( new WC_Product_Variable_Data_Store_CPT() )->read_variation_attributes( $product );

		$this->assertSame( array( 'Small' ), $attributes['Size'] );

		$product->delete();
	}

	/**
	 * @testdox read_variation_attributes resolves text attribute values via slug matching on pre-2.4 products.
	 */
	public function test_read_variation_attributes_resolves_pre24_text_attribute_slugs(): void {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'Small', 'Large', 'XL' ) );
		$attribute->set_variation( true );
		$attribute->set_id( 0 );

		$product = new WC_Product_Variable();
		$product->set_attributes( array( $attribute ) );
		$product->save();
		$product_id = $product->get_id();

		// Mark this as a pre-2.4 product so the slug-matching branch is taken.
		update_post_meta( $product_id, '_product_version', '2.3.0' );

		// Create a variation whose attribute is stored as the slug ('small'), not the display value ('Small').
		WC_Helper_Product::create_product_variation_object(
			$product_id,
			'DUMMY SKU PRE24',
			10,
			array( 'size' => 'Small' )
		);

		$product      = wc_get_product( $product_id );
		$child_ids    = $product->get_children();
		$variation_id = current( $child_ids );

		// Overwrite the stored value with the slug form to simulate pre-2.4 database state.
		update_post_meta( $variation_id, 'attribute_size', 'small' );

		$cache_key = WC_Cache_Helper::get_cache_prefix( 'product_' . $product_id ) . 'product_variation_attributes_' . $product_id;
		wp_cache_delete( $cache_key, 'products' );

		$attributes = ( new WC_Product_Variable_Data_Store_CPT() )->read_variation_attributes( $product );

		// The slug 'small' must be resolved back to the full display value 'Small'.
		$this->assertSame( array( 'Small' ), $attributes['Size'] );

		$product->delete();
	}

	/**
	 * @testdox child_has_weight returns true when at least one visible child has a weight set.
	 */
	public function test_child_has_weight_returns_true_when_child_has_weight(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_visible_children();

		update_post_meta( current( $child_ids ), '_weight', '1.5' );

		$this->assertTrue( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_weight( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_weight returns false when no visible child has a weight set.
	 */
	public function test_child_has_weight_returns_false_when_no_child_has_weight(): void {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();
		$child_ids  = $product->get_visible_children();

		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_weight', '1.5' );
		}

		$this->assertTrue( $data_store->child_has_weight( $product ) );

		foreach ( $child_ids as $child_id ) {
			delete_post_meta( $child_id, '_weight' );
		}

		$this->assertFalse( $data_store->child_has_weight( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_weight returns false when the product has no visible children.
	 */
	public function test_child_has_weight_returns_false_for_no_children(): void {
		$product = new WC_Product_Variable();
		$product->save();

		$this->assertFalse( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_weight( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_weight returns false when a child has weight set to zero.
	 */
	public function test_child_has_weight_returns_false_when_child_weight_is_zero(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_visible_children();

		update_post_meta( current( $child_ids ), '_weight', '0' );

		$this->assertFalse( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_weight( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_dimensions returns true when at least one visible child has a dimension set.
	 */
	public function test_child_has_dimensions_returns_true_when_child_has_dimension(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_visible_children();

		update_post_meta( current( $child_ids ), '_length', '10' );

		$this->assertTrue( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_dimensions( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_dimensions returns false when no visible child has any dimension set.
	 */
	public function test_child_has_dimensions_returns_false_when_no_child_has_dimensions(): void {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();
		$child_ids  = $product->get_visible_children();

		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_length', '10' );
		}

		$this->assertTrue( $data_store->child_has_dimensions( $product ) );

		foreach ( $child_ids as $child_id ) {
			delete_post_meta( $child_id, '_length' );
		}

		$this->assertFalse( $data_store->child_has_dimensions( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_dimensions returns false when the product has no visible children.
	 */
	public function test_child_has_dimensions_returns_false_for_no_children(): void {
		$product = new WC_Product_Variable();
		$product->save();

		$this->assertFalse( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_dimensions( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_dimensions returns true when a child has only width set.
	 */
	public function test_child_has_dimensions_returns_true_when_child_has_width(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_visible_children();

		update_post_meta( current( $child_ids ), '_width', '5' );

		$this->assertTrue( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_dimensions( $product ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_dimensions returns false when a child has a dimension set to zero.
	 */
	public function test_child_has_dimensions_returns_false_when_dimension_is_zero(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_visible_children();

		update_post_meta( current( $child_ids ), '_length', '0' );

		$this->assertFalse( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_dimensions( $product ) );

		$product->delete();
	}

	/**
	 * @dataProvider provider_lookup_table_generating
	 * @testdox child_has_stock_status returns true when at least one child has the given status.
	 *
	 * @param bool $lookup_table_generating Whether the lookup table is currently being generated.
	 */
	public function test_child_has_stock_status_returns_true_when_child_matches( bool $lookup_table_generating ): void {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();
		$child_ids  = $product->get_children();

		$variation = wc_get_product( current( $child_ids ) );
		$variation->set_stock_status( ProductStockStatus::ON_BACKORDER );
		$variation->save();

		update_option( 'woocommerce_product_lookup_table_is_generating', $lookup_table_generating );

		$this->assertTrue( $data_store->child_has_stock_status( $product, ProductStockStatus::ON_BACKORDER ) );
		$this->assertFalse( $data_store->child_has_stock_status( $product, ProductStockStatus::OUT_OF_STOCK ) );

		$product->delete();
	}

	/**
	 * @testdox child_has_stock_status returns false when the product has no children.
	 */
	public function test_child_has_stock_status_returns_false_when_no_children(): void {
		$product = new WC_Product_Variable();
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		$this->assertFalse( ( new WC_Product_Variable_Data_Store_CPT() )->child_has_stock_status( $product, ProductStockStatus::IN_STOCK ) );

		$product->delete();
	}

	/**
	 * @testdox sync_managed_variation_stock_status updates children that do not manage their own stock.
	 */
	public function test_sync_managed_variation_stock_status_updates_unmanaged_children(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$child_ids = $product->get_children();
		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_manage_stock', 'no' );
			update_post_meta( $child_id, '_stock_status', ProductStockStatus::IN_STOCK );
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_managed_variation_stock_status( $product );

		foreach ( $child_ids as $child_id ) {
			$this->assertSame( ProductStockStatus::OUT_OF_STOCK, get_post_meta( $child_id, '_stock_status', true ) );
		}
		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_managed_variation_stock_status does not touch children that manage their own stock.
	 */
	public function test_sync_managed_variation_stock_status_skips_managed_children(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$child_ids = $product->get_children();
		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_manage_stock', 'yes' );
			update_post_meta( $child_id, '_stock_status', ProductStockStatus::IN_STOCK );
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_managed_variation_stock_status( $product );

		foreach ( $child_ids as $child_id ) {
			$this->assertSame( ProductStockStatus::IN_STOCK, get_post_meta( $child_id, '_stock_status', true ) );
		}
		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_managed_variation_stock_status does nothing when the parent does not manage stock.
	 */
	public function test_sync_managed_variation_stock_status_skips_when_parent_does_not_manage_stock(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_children();

		// Save children as out of stock (updates lookup table) so WC computes the parent as out of stock.
		foreach ( $child_ids as $child_id ) {
			$variation = wc_get_product( $child_id );
			$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
			$variation->save();
		}
		$product->set_manage_stock( false );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		// Now set children to in stock via raw postmeta — the method should ignore them since the parent does not manage stock.
		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_manage_stock', 'no' );
			update_post_meta( $child_id, '_stock_status', ProductStockStatus::IN_STOCK );
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_managed_variation_stock_status( $product );

		foreach ( $child_ids as $child_id ) {
			$this->assertSame( ProductStockStatus::IN_STOCK, get_post_meta( $child_id, '_stock_status', true ) );
		}
		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_managed_variation_stock_status skips unmanaged children already at the correct status.
	 */
	public function test_sync_managed_variation_stock_status_skips_children_already_at_correct_status(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$child_ids = $product->get_children();
		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_manage_stock', 'no' );
			update_post_meta( $child_id, '_stock_status', ProductStockStatus::OUT_OF_STOCK );
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_managed_variation_stock_status( $product );

		foreach ( $child_ids as $child_id ) {
			$this->assertSame( ProductStockStatus::OUT_OF_STOCK, get_post_meta( $child_id, '_stock_status', true ) );
		}
		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_managed_variation_stock_status updates only unmanaged children when the product has a mix of managed and unmanaged children.
	 */
	public function test_sync_managed_variation_stock_status_updates_only_unmanaged_in_mixed_children(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$child_ids   = $product->get_children();
		$half        = intdiv( count( $child_ids ), 2 );
		$managed_ids = array_slice( $child_ids, 0, $half );

		foreach ( $child_ids as $child_id ) {
			$is_managed = in_array( $child_id, $managed_ids, true );
			update_post_meta( $child_id, '_manage_stock', $is_managed ? 'yes' : 'no' );
			update_post_meta( $child_id, '_stock_status', ProductStockStatus::IN_STOCK );
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_managed_variation_stock_status( $product );

		foreach ( $child_ids as $child_id ) {
			$expected_status = in_array( $child_id, $managed_ids, true ) ? ProductStockStatus::IN_STOCK : ProductStockStatus::OUT_OF_STOCK;
			$this->assertSame( $expected_status, get_post_meta( $child_id, '_stock_status', true ) );
		}
		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_managed_variation_stock_status backfills unmanaged children that have no _stock_status meta row or a NULL value.
	 */
	public function test_sync_managed_variation_stock_status_backfills_children_with_missing_status(): void {
		global $wpdb;

		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$child_ids = $product->get_children();
		foreach ( array_values( $child_ids ) as $i => $child_id ) {
			update_post_meta( $child_id, '_manage_stock', 'no' );
			if ( 1 === $i % 2 ) {
				// Even index: simulate a NULL meta_value being stored.
				$wpdb->update(
					$wpdb->postmeta,
					array( 'meta_value' => null ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					array(
						'post_id'  => $child_id,
						'meta_key' => '_stock_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					),
					array( '%s' ),
					array( '%d', '%s' )
				);
			} else {
				// Odd index: simulate a variation created outside WC's save pipeline — no _stock_status row.
				$wpdb->delete(
					$wpdb->postmeta,
					array(
						'post_id'  => $child_id,
						'meta_key' => '_stock_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					)
				);
			}
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_managed_variation_stock_status( $product );

		foreach ( $child_ids as $child_id ) {
			$this->assertSame( ProductStockStatus::OUT_OF_STOCK, get_post_meta( $child_id, '_stock_status', true ) );
		}

		$product->delete();
	}

	/**
	 * @testdox sync_managed_variation_stock_status does nothing when the product has no children.
	 */
	public function test_sync_managed_variation_stock_status_does_nothing_when_no_children(): void {
		$product = new WC_Product_Variable();
		$product->set_manage_stock( true );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		( new WC_Product_Variable_Data_Store_CPT() )->sync_managed_variation_stock_status( $product );

		$this->assertSame( array(), $product->get_children() );
		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_price skips empty-string and SQL-NULL prices while still storing valid sibling prices from the same set.
	 */
	public function test_sync_price_stores_valid_prices_when_mixed_with_empty(): void {
		global $wpdb;

		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_visible_children();

		// First child: empty string — exercises the `'' === $price` branch.
		$empty_string_child = $child_ids[0];
		update_post_meta( $empty_string_child, '_price', '' );

		// Second child: SQL NULL — exercises the `is_null( $price )` branch. Direct SQLs as update_post_meta( null ) saves empty string.
		$null_child_id = $child_ids[1];
		unset( $child_ids[0], $child_ids[1] );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_price'", $null_child_id ) );
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES (%d, %s, NULL)", $null_child_id, '_price' ) );

		// Remaining children: a known valid price.
		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_price', '12.00' );
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_price( $product );

		// Only '12.00' must appear — neither the empty-string nor the NULL price must reach the parent.
		$this->assertSame( array( '12.00' ), get_post_meta( $product->get_id(), '_price', false ) );

		$product->delete();
	}

	/**
	 * @testdox sync_price stores only distinct child prices on the parent.
	 */
	public function test_sync_price_stores_only_distinct_child_prices_on_parent(): void {
		$data_store = new WC_Product_Variable_Data_Store_CPT();
		$product    = WC_Helper_Product::create_variation_product();
		$child_ids  = $product->get_visible_children();

		$data_store->sync_price( $product );

		$this->assertSame(
			array( 10.0, 15.0, 16.0, 17.0, 18.0, 19.0 ),
			array_map( 'floatval', get_post_meta( $product->get_id(), '_price', false ) )
		);

		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_price', '9.99' );
		}

		$data_store->sync_price( $product );

		$this->assertSame( array( 9.99 ), array_map( 'floatval', get_post_meta( $product->get_id(), '_price', false ) ) );

		$product->delete();
	}

	/**
	 * @testdox sync_price removes all price meta when the product has no visible children.
	 */
	public function test_sync_price_clears_price_when_no_visible_children(): void {
		$product = new WC_Product_Variable();
		$product->save();

		add_post_meta( $product->get_id(), '_price', '99' );

		( new WC_Product_Variable_Data_Store_CPT() )->sync_price( $product );

		$this->assertSame( array(), get_post_meta( $product->get_id(), '_price', false ) );

		$product->delete();
	}

	/**
	 * @testdox sync_price skips children with an empty price and stores no price meta.
	 */
	public function test_sync_price_skips_empty_child_prices(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_visible_children();

		foreach ( $child_ids as $child_id ) {
			update_post_meta( $child_id, '_price', '' );
		}

		( new WC_Product_Variable_Data_Store_CPT() )->sync_price( $product );

		$this->assertSame( array(), get_post_meta( $product->get_id(), '_price', false ) );

		$product->delete();
	}

	/**
	 * @dataProvider provider_lookup_table_generating
	 * @testdox sync_stock_status sets instock when at least one child is in stock.
	 *
	 * @param bool $lookup_table_generating Whether the lookup table is currently being generated.
	 */
	public function test_sync_stock_status_sets_instock_when_any_child_in_stock( bool $lookup_table_generating ): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_children();

		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		foreach ( $child_ids as $child_id ) {
			$variation = wc_get_product( $child_id );
			$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
			$variation->save();
		}
		$variation = wc_get_product( current( $child_ids ) );
		$variation->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation->save();

		update_option( 'woocommerce_product_lookup_table_is_generating', $lookup_table_generating );

		( new WC_Product_Variable_Data_Store_CPT() )->sync_stock_status( $product );

		$this->assertSame( ProductStockStatus::IN_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @dataProvider provider_lookup_table_generating
	 * @testdox sync_stock_status sets on_backorder when no child is in stock but at least one is on backorder.
	 *
	 * @param bool $lookup_table_generating Whether the lookup table is currently being generated.
	 */
	public function test_sync_stock_status_sets_on_backorder_when_backorder_exists_and_none_in_stock( bool $lookup_table_generating ): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_children();

		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		foreach ( $child_ids as $child_id ) {
			$variation = wc_get_product( $child_id );
			$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
			$variation->save();
		}
		$variation = wc_get_product( current( $child_ids ) );
		$variation->set_stock_status( ProductStockStatus::ON_BACKORDER );
		$variation->save();

		update_option( 'woocommerce_product_lookup_table_is_generating', $lookup_table_generating );

		( new WC_Product_Variable_Data_Store_CPT() )->sync_stock_status( $product );

		$this->assertSame( ProductStockStatus::ON_BACKORDER, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_stock_status sets outofstock when all children are out of stock.
	 */
	public function test_sync_stock_status_sets_outofstock_when_all_children_outofstock(): void {
		$product = WC_Helper_Product::create_variation_product();

		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
			$variation->save();
		}

		$product->set_stock_status( ProductStockStatus::IN_STOCK );

		( new WC_Product_Variable_Data_Store_CPT() )->sync_stock_status( $product );

		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_stock_status sets outofstock when the product has no children.
	 */
	public function test_sync_stock_status_sets_outofstock_when_no_children(): void {
		$product = new WC_Product_Variable();
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		( new WC_Product_Variable_Data_Store_CPT() )->sync_stock_status( $product );

		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		$product->delete();
	}

	/**
	 * @testdox sync_stock_status sets instock when children include both in-stock and on-backorder.
	 */
	public function test_sync_stock_status_instock_takes_priority_over_on_backorder(): void {
		$product   = WC_Helper_Product::create_variation_product();
		$child_ids = $product->get_children();

		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );

		foreach ( $child_ids as $child_id ) {
			$variation = wc_get_product( $child_id );
			$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
			$variation->save();
		}

		$variation = wc_get_product( current( $child_ids ) );
		$variation->set_stock_status( ProductStockStatus::ON_BACKORDER );
		$variation->save();

		$variation = wc_get_product( next( $child_ids ) );
		$variation->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation->save();

		( new WC_Product_Variable_Data_Store_CPT() )->sync_stock_status( $product );

		$this->assertSame( ProductStockStatus::IN_STOCK, $product->get_stock_status() );

		$product->delete();
	}
}
