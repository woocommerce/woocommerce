<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
/**
 * Core functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

/**
 * Class WC_Core_Functions_Test
 */
class WC_Core_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Test wc_ascii_uasort_comparison() function.
	 */
	public function test_wc_ascii_uasort_comparison() {
		$unsorted_values = array(
			'ET' => 'Éthiopie',
			'ES' => 'Espagne',
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
		);

		$sorted_values = $unsorted_values;
		uasort( $sorted_values, 'wc_ascii_uasort_comparison' );

		$this->assertSame( array( 'Afghanistan', 'Åland Islands', 'Espagne', 'Éthiopie' ), array_values( $sorted_values ) );
	}

	/**
	 * Test wc_asort_by_locale() function.
	 */
	public function test_wc_asort_by_locale() {
		$unsorted_values = array(
			'ET' => 'Éthiopie',
			'ES' => 'Espagne',
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
		);

		$sorted_values = $unsorted_values;
		wc_asort_by_locale( $sorted_values );

		$this->assertSame( array( 'Afghanistan', 'Åland Islands', 'Espagne', 'Éthiopie' ), array_values( $sorted_values ) );
	}

	/**
	 * @testdDox wc_get_rounding_precision returns the value of wc_get_price_decimals()+2, but with a minimum of WC_ROUNDING_PRECISION (6)
	 *
	 * @testWith [0, 6]
	 *           [2, 6]
	 *           [4, 6]
	 *           [5, 7]
	 *           [6, 8]
	 *           [7, 9]
	 *
	 * @param int $decimals Value returned by wc_get_price_decimals().
	 * @param int $expected Expected value returned by the function.
	 */
	public function test_wc_get_rounding_precision( $decimals, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function () use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_get_rounding_precision();
		$this->assertEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * @testDox wc_add_number_precision moves the decimal point to the right as many places as wc_get_price_decimals() says, and (optionally) properly rounds the result.
	 *
	 * @testWith [2, 1.23456789, false, 123.456789]
	 *           [2, 1.23456789, true, 123.4568]
	 *           [2, 1.235, false, 123.5]
	 *           [2, 1.235, true, 123.5]
	 *           [4, 1.23456789, false, 12345.6789]
	 *           [4, 1.23456789, true, 12345.68]
	 *           [5, 1.23456789, false, 123456.789]
	 *           [5, 1.23456789, true, 123456.79]
	 *           [2, null, false, 0]
	 *           [2, null, true, 0]
	 *
	 * @param int   $decimals Value returned by wc_get_price_decimals().
	 * @param mixed $value Value to pass to the function.
	 * @param bool  $round Whether to round the result or not.
	 * @param float $expected Expected value returned by the function.
	 */
	public function test_wc_add_number_precision( $decimals, $value, $round, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function () use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_add_number_precision( $value, $round );
		$this->assertFloatEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * @testWith [2, 123.4567, 1.234567]
	 *           [2, 123.5, 1.235]
	 *           [5, 123.4567, 0.001234567]
	 *           [2, null, 0]
	 *
	 * @param int   $decimals Value returned by wc_get_price_decimals().
	 * @param mixed $value Value to pass to the function.
	 * @param float $expected Expected value returned by the function.
	 */
	public function test_wc_remove_number_precision( $decimals, $value, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function () use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_remove_number_precision( $value );
		$this->assertEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * Test wc_help_tip() function.
	 */
	public function test_wc_help_tip_strips_html() {
		$expected = '<span class="woocommerce-help-tip" tabindex="0" aria-label="Strong text regular text" data-tip="&lt;strong&gt;Strong text&lt;/strong&gt; regular text"></span>';
		$this->assertEquals( $expected, wc_help_tip( '<strong>Strong text</strong> regular text', false ) );
		$this->assertEquals( $expected, wc_help_tip( '<strong>Strong text</strong> regular text', true ) );
	}

	/**
	 * Test wc_get_customer_default_location() function.
	 */
	public function test_wc_get_customer_default_location() {
		/**
		 * Test with none of the options set. In this case the location should be empty.
		 *
		 * woocommerce_default_country is set to 'US:CA' by default unless it was defined during setup.
		 */
		delete_option( 'woocommerce_default_customer_address' );
		delete_option( 'woocommerce_default_country' );
		delete_option( 'woocommerce_allowed_countries' );
		delete_option( 'woocommerce_specific_allowed_countries' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( 'CA', $result['state'] );

		// Test with a default address defined during setup. This country has states.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'DE:LS' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'DE', $result['country'] );
		$this->assertEquals( 'LS', $result['state'] );

		// Test with a default address defined during setup. This country has no states.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'GB' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'GB', $result['country'] );
		$this->assertEquals( '', $result['state'] );

		// Test with default address, but specific countries set. Address is allowed.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'DE:LS' );
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'DE', 'AT', 'CH' ) );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'DE', $result['country'] );
		$this->assertEquals( 'LS', $result['state'] );

		// Test with default address, but specific countries set. Address is not allowed.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'DE:LS' );
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'GB' ) );
		$result = wc_get_customer_default_location();
		$this->assertEquals( '', $result['country'] );
		$this->assertEquals( '', $result['state'] );

		// Test with no default address.
		update_option( 'woocommerce_default_customer_address', '' );
		update_option( 'woocommerce_default_country', 'GB' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( '', $result['country'] );
		$this->assertEquals( '', $result['state'] );

		// Test with geolocation.
		update_option( 'woocommerce_default_customer_address', 'geolocation' );
		update_option( 'woocommerce_default_country', 'GB' );
		delete_option( 'woocommerce_allowed_countries' );
		delete_option( 'woocommerce_specific_allowed_countries' );
		add_filter(
			'woocommerce_geolocate_ip',
			function () {
				return 'FR';
			},
			10
		);
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'FR', $result['country'] );
		$this->assertEquals( '', $result['state'] );
		remove_all_filters( 'woocommerce_geolocate_ip' );

		// Test with geolocation but geolocated country is not allowed.
		update_option( 'woocommerce_default_customer_address', 'geolocation' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'GB' ) );
		add_filter(
			'woocommerce_geolocate_ip',
			function () {
				return 'FR';
			},
			10
		);
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'GB', $result['country'] );
		$this->assertEquals( '', $result['state'] );
		remove_all_filters( 'woocommerce_geolocate_ip' );
	}

	/**
	 * Test wc_delete_transients() function.
	 */
	public function test_wc_delete_transients() {
		// Set up test transients.
		$transient_name1 = 'wc_test_transient_1';
		$transient_name2 = 'wc_test_transient_2';

		set_transient( $transient_name1, 'test_value_1', HOUR_IN_SECONDS );
		set_transient( $transient_name2, 'test_value_2', HOUR_IN_SECONDS );

		// Verify transients exist before deletion.
		$this->assertEquals( 'test_value_1', get_transient( $transient_name1 ) );
		$this->assertEquals( 'test_value_2', get_transient( $transient_name2 ) );

		// Delete the transients.
		_wc_delete_transients( array( $transient_name1, $transient_name2 ) );

		// Verify transients are deleted.
		$this->assertFalse( get_transient( $transient_name1 ) );
		$this->assertFalse( get_transient( $transient_name2 ) );

		// Test with a single transient name (string instead of array).
		$transient_name3 = 'wc_test_transient_3';
		set_transient( $transient_name3, 'test_value_3', HOUR_IN_SECONDS );
		$this->assertEquals( 'test_value_3', get_transient( $transient_name3 ) );

		// Pass the transient name as an array element
		_wc_delete_transients( array( $transient_name3 ) );

		$this->assertFalse( get_transient( $transient_name3 ) );

		// Test with a transient that does not exist.
		$wc_test_transient_not_existing = 'wc_test_transient_not_existing';
		$this->assertTrue( _wc_delete_transients( array( $wc_test_transient_not_existing ) ) );

		// Test with empty input.
		$this->assertFalse( _wc_delete_transients( array() ) );
		$this->assertFalse( _wc_delete_transients( '' ) );

		// Test with other non-array arguments.
		$this->assertFalse( _wc_delete_transients( 'test' ) );
		$this->assertFalse( _wc_delete_transients( null ) );
		$this->assertFalse( _wc_delete_transients( 123 ) );
		$this->assertFalse( _wc_delete_transients( true ) );
		$this->assertFalse( _wc_delete_transients( new stdClass() ) );
	}

	/**
	 * Helper to set the product permalink structure for a test.
	 *
	 * @param string $product_base The product_base value to use.
	 */
	private function set_product_permalink_base( $product_base ) {
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'  => $product_base,
				'category_base' => 'product-category',
				'tag_base'      => 'product-tag',
			)
		);
	}

	/**
	 * @testdox Should reassign the product query var when the numeric slug is misinterpreted as a page.
	 */
	public function test_wc_fix_numeric_product_slug_request_reassigns_numeric_slug() {
		$original = get_option( 'woocommerce_permalinks' );
		$this->set_product_permalink_base( '/shop/%product_cat%' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Numeric Slug Product' );
		$product->set_slug( '12345' );
		$product->set_status( 'publish' );
		$product->save();

		$query_vars = wc_fix_numeric_product_slug_request(
			array(
				'product' => 'qa-child-cat',
				'page'    => '12345',
			)
		);

		$this->assertSame( '12345', $query_vars['product'], 'Numeric slug should become the product query var.' );
		$this->assertSame( '12345', $query_vars['name'], 'Numeric slug should also populate the name query var.' );
		$this->assertArrayNotHasKey( 'page', $query_vars, 'Page query var should be cleared once consumed.' );

		$product->delete( true );
		update_option( 'woocommerce_permalinks', is_array( $original ) ? $original : array() );
	}

	/**
	 * @testdox Should leave query vars untouched when product permalink does not use %product_cat%.
	 */
	public function test_wc_fix_numeric_product_slug_request_skips_without_product_cat_placeholder() {
		$original = get_option( 'woocommerce_permalinks' );
		$this->set_product_permalink_base( '/shop' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Numeric Slug Product Skip' );
		$product->set_slug( '67890' );
		$product->set_status( 'publish' );
		$product->save();

		$input      = array(
			'product' => 'qa-child-cat',
			'page'    => '67890',
		);
		$query_vars = wc_fix_numeric_product_slug_request( $input );

		$this->assertSame( $input, $query_vars, 'Query vars should be untouched without %product_cat%.' );

		$product->delete( true );
		update_option( 'woocommerce_permalinks', is_array( $original ) ? $original : array() );
	}

	/**
	 * @testdox Should leave query vars untouched when the existing product slug already resolves.
	 */
	public function test_wc_fix_numeric_product_slug_request_skips_when_existing_product_resolves() {
		$original = get_option( 'woocommerce_permalinks' );
		$this->set_product_permalink_base( '/shop/%product_cat%' );

		$existing = new WC_Product_Simple();
		$existing->set_name( 'Existing Product' );
		$existing->set_slug( 'existing-product' );
		$existing->set_status( 'publish' );
		$existing->save();

		$numeric = new WC_Product_Simple();
		$numeric->set_name( 'Numeric Page' );
		$numeric->set_slug( '42' );
		$numeric->set_status( 'publish' );
		$numeric->save();

		$input      = array(
			'product' => 'existing-product',
			'page'    => '42',
		);
		$query_vars = wc_fix_numeric_product_slug_request( $input );

		$this->assertSame( $input, $query_vars, 'Legitimate paged product requests should be preserved.' );

		$existing->delete( true );
		$numeric->delete( true );
		update_option( 'woocommerce_permalinks', is_array( $original ) ? $original : array() );
	}

	/**
	 * @testdox Should leave query vars untouched when no product matches the numeric slug.
	 */
	public function test_wc_fix_numeric_product_slug_request_skips_when_no_numeric_match() {
		$original = get_option( 'woocommerce_permalinks' );
		$this->set_product_permalink_base( '/shop/%product_cat%' );

		$input      = array(
			'product' => 'qa-child-cat',
			'page'    => '99999',
		);
		$query_vars = wc_fix_numeric_product_slug_request( $input );

		$this->assertSame( $input, $query_vars, 'Without a matching numeric product, query vars should be unchanged.' );

		update_option( 'woocommerce_permalinks', is_array( $original ) ? $original : array() );
	}

	/**
	 * @testdox Should leave query vars untouched when the page value is not purely numeric.
	 */
	public function test_wc_fix_numeric_product_slug_request_skips_non_numeric_page() {
		$original = get_option( 'woocommerce_permalinks' );
		$this->set_product_permalink_base( '/shop/%product_cat%' );

		$input      = array(
			'product' => 'qa-child-cat',
			'page'    => 'not-a-number',
		);
		$query_vars = wc_fix_numeric_product_slug_request( $input );

		$this->assertSame( $input, $query_vars, 'Non-numeric page values must be ignored.' );

		update_option( 'woocommerce_permalinks', is_array( $original ) ? $original : array() );
	}
}
