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
	 * Create a Shop page with one child.
	 *
	 * @param int    $parent_id Shop page parent ID.
	 * @param string $shop_slug Shop page slug.
	 * @return int Shop page ID.
	 */
	private function create_shop_page_tree( int $parent_id = 0, string $shop_slug = 'shop' ): int {
		$shop_page_id = self::factory()->post->create(
			array(
				'post_parent' => $parent_id,
				'post_type'   => 'page',
				'post_name'   => $shop_slug,
				'post_status' => 'publish',
			)
		);
		self::factory()->post->create(
			array(
				'post_parent' => $shop_page_id,
				'post_type'   => 'page',
				'post_name'   => 'sale',
				'post_status' => 'publish',
			)
		);

		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		return $shop_page_id;
	}

	/**
	 * @testdox Shop subpage rules should follow the current paths instead of the stored derived flag.
	 *
	 * @testWith ["/shop", true]
	 *           ["/shop/%product_cat%", true]
	 *           ["/webshop", false]
	 *           ["/shopper", false]
	 *           ["/catalog/shop", false]
	 *
	 * @param string $product_base Product permalink base.
	 * @param bool   $expected     Whether the Shop child rule should be added.
	 */
	public function test_wc_fix_rewrite_rules_derives_shop_subpage_rules_from_paths( string $product_base, bool $expected ): void {
		$this->create_shop_page_tree();
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => $product_base,
				'use_verbose_page_rules' => ! $expected,
			)
		);

		$original_rules = array( 'fallback/?$' => 'index.php?fallback=1' );
		$rules          = wc_fix_rewrite_rules( $original_rules );

		if ( $expected ) {
			$this->assertSame( 'shop/sale/?$', array_key_first( $rules ), 'The Shop child rule should take priority over product rules.' );
			$this->assertSame( 'index.php?pagename=shop/sale', $rules['shop/sale/?$'] );
		} else {
			$this->assertSame( $original_rules, $rules, 'Unrelated product paths should not add Shop child rules.' );
		}
	}

	/**
	 * @testdox Shop subpage rules should match a non-Latin Shop path in either encoded or decoded form.
	 *
	 * @testWith ["encoded"]
	 *           ["decoded"]
	 *
	 * @param string $product_base_form Form the product base is stored in.
	 */
	public function test_wc_fix_rewrite_rules_matches_a_non_latin_shop_path( string $product_base_form ): void {
		// WordPress stores a non-Latin page slug percent-encoded, while the product base is stored
		// as the merchant typed it, so the two only line up once both have been decoded.
		$shop_page_id = $this->create_shop_page_tree( 0, 'магазин' );
		$shop_uri     = (string) get_page_uri( $shop_page_id );
		$this->assertStringContainsString( '%d0%', $shop_uri, 'WordPress should store the non-Latin Shop slug percent-encoded.' );

		$product_base = 'encoded' === $product_base_form ? $shop_uri : rawurldecode( $shop_uri );
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => '/' . $product_base . '/%product_cat%',
				'use_verbose_page_rules' => false,
			)
		);

		$rules = wc_fix_rewrite_rules( array( 'fallback/?$' => 'index.php?fallback=1' ) );

		// Only the presence of the rule is asserted. WP_Rewrite::generate_rewrite_rules() reads the
		// `%d0%` byte pairs of a percent-encoded slug as rewrite tags, so the target it builds for
		// one is malformed. That predates deriving the match from the current paths.
		$this->assertArrayHasKey(
			$shop_uri . '/sale/?$',
			$rules,
			'A non-Latin Shop path should still add the Shop child rule.'
		);
	}

	/**
	 * @testdox Shop subpage rules should follow a changed Shop page path.
	 */
	public function test_wc_fix_rewrite_rules_follows_shop_page_reparenting(): void {
		$catalog_page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'catalog',
				'post_status' => 'publish',
			)
		);
		$shop_page_id    = $this->create_shop_page_tree();
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => '/catalog/shop',
				'use_verbose_page_rules' => false,
			)
		);

		$original_rules = array( 'fallback/?$' => 'index.php?fallback=1' );
		$this->assertSame( $original_rules, wc_fix_rewrite_rules( $original_rules ) );

		wp_update_post(
			array(
				'ID'          => $shop_page_id,
				'post_parent' => $catalog_page_id,
			)
		);

		$rules = wc_fix_rewrite_rules( $original_rules );

		$this->assertSame( 'catalog/shop/sale/?$', array_key_first( $rules ), 'The new Shop child path should take priority over product rules.' );
		$this->assertSame( 'index.php?pagename=catalog/shop/sale', $rules['catalog/shop/sale/?$'] );
	}

	/**
	 * @testdox Shop subpage rules should keep using a filtered Shop page after matching the configured Shop path.
	 */
	public function test_wc_fix_rewrite_rules_preserves_filtered_shop_page_rules(): void {
		$configured_shop_page_id = $this->create_shop_page_tree();
		$filtered_shop_page_id   = $this->create_shop_page_tree( 0, 'boutique' );
		update_option( 'woocommerce_shop_page_id', $configured_shop_page_id );
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => '/shop/%product_cat%',
				'use_verbose_page_rules' => true,
			)
		);

		$filter_shop_page_id = static function () use ( $filtered_shop_page_id ) {
			return $filtered_shop_page_id;
		};
		add_filter( 'woocommerce_get_shop_page_id', $filter_shop_page_id );

		try {
			$rules = wc_fix_rewrite_rules( array() );
		} finally {
			remove_filter( 'woocommerce_get_shop_page_id', $filter_shop_page_id );
		}

		$this->assertSame( 'index.php?pagename=boutique/sale', $rules['boutique/sale/?$'] );
	}

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
}
