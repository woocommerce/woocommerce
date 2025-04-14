<?php
/**
 * WCAdminHelper tests
 *
 * @package WooCommerce\Admin\Tests\WCAdminHelper
 */

use Automattic\WooCommerce\Admin\WCAdminHelper;

/**
 * WC_Admin_Tests_Admin_Helper Class
 *
 * @package WooCommerce\Admin\Tests\WCAdminHelper
 */
class WC_Admin_Tests_Admin_Helper extends WC_Unit_Test_Case {

	/**
	 * Store original permalink structure for restoration.
	 *
	 * @var string
	 */
	private static $original_permalink_structure;

	/**
	 * Store original WooCommerce permalinks for restoration.
	 *
	 * @var array
	 */
	private static $original_wc_permalinks;

	/**
	 * Store product ID for cleanup.
	 *
	 * @var int
	 */
	private static $product_id;

	/**
	 * Set up before class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Use a block theme so that product post type can be registered with has_archive = `shop`.
		switch_theme( 'twentytwentyfour' );

		// Unregister taxonomies and post type so that they can be registered again in WC_Unit_Test_Case::setUp().
		unregister_taxonomy( 'product_type' );
		unregister_taxonomy( 'product_cat' );
		unregister_taxonomy( 'product_tag' );
		unregister_post_type( 'product' );

		// Store original permalink structure for restoration.
		self::$original_permalink_structure = get_option( 'permalink_structure' );
		self::$original_wc_permalinks       = get_option( 'woocommerce_permalinks', array() );
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		// Create a product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( 'publish' );
		$product->save();
		self::$product_id = $product->get_id();

		// Flush rewrite rules.
		$wp_rewrite->init();
		$wp_rewrite->flush_rules( true );
	}

	/**
	 * Tear down after class.
	 */
	public static function tearDownAfterClass(): void {
		// Restore original permalink structure.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( self::$original_permalink_structure );
		update_option( 'woocommerce_permalinks', self::$original_wc_permalinks );

		// Clean up product.
		WC_Helper_Product::delete_product( self::$product_id );

		// Flush rewrite rules one final time.
		$wp_rewrite->flush_rules();
		parent::tearDownAfterClass();
	}

	/**
	 * Test get_wcadmin_active_for_in_seconds_with with invalid timestamp option.
	 */
	public function test_get_wcadmin_active_for_in_seconds_with_invalid_timestamp_option() {
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, 'invalid-time' );
		$this->assertEquals( is_numeric( WCAdminHelper::get_wcadmin_active_for_in_seconds() ), true );
	}


	/**
	 * Test wc_admin_active_for one hour
	 */
	public function test_is_wc_admin_active_for_one_hour() {
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, time() - ( HOUR_IN_SECONDS * 10 ) );

		// Active for one hour - true.
		$active_for = WCAdminHelper::is_wc_admin_active_for( HOUR_IN_SECONDS );
		$this->assertEquals( true, $active_for );
	}

	/**
	 * Test wc_admin_active_for 7 days
	 */
	public function test_is_wc_admin_active_for_7_days() {
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, time() - ( HOUR_IN_SECONDS * 10 ) );
		// Active for 7 days - false.
		$active_for = WCAdminHelper::is_wc_admin_active_for( DAY_IN_SECONDS * 7 );
		$this->assertEquals( false, $active_for );
	}

	/**
	 * Test wc_admin_active_in_date_range with invalid range.
	 */
	public function test_is_wc_admin_active_in_date_range_with_invalid_range() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( '"random-range" range is not supported, use one of: week-1, week-1-4, month-1-3, month-3-6, month-6+' );

		WCAdminHelper::is_wc_admin_active_in_date_range( 'random-range' );
	}

	/**
	 * Test wc_admin_active_in_date_range with custom start date.
	 */
	public function test_is_wc_admin_active_in_date_range_with_custom_start_date() {
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, time() - DAY_IN_SECONDS );
		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( 'week-1', 2 * DAY_IN_SECONDS );
		$this->assertEquals( $active_for, false );

		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, time() - ( 4 * DAY_IN_SECONDS ) );
		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( 'week-1', 2 * DAY_IN_SECONDS );
		$this->assertEquals( $active_for, true );
	}

	/**
	 * Test wc_admin_active_in_date_range with times right around a date range.
	 */
	public function test_is_wc_admin_not_active_around_date_range() {
		// one minute before 7 days.
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, ( time() - ( 7 * DAY_IN_SECONDS ) ) + MINUTE_IN_SECONDS );
		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( 'week-1-4' );
		$this->assertEquals( $active_for, false );

		// one minute after 28 days.
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, ( time() - ( 28 * DAY_IN_SECONDS ) ) - MINUTE_IN_SECONDS );
		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( 'week-1-4' );
		$this->assertEquals( $active_for, false );
	}

	/**
	 * Test wc_admin_active_in_date_range with times within a date range.
	 */
	public function test_is_wc_admin_active_within_date_range() {
		// one minute after 7 days.
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, ( time() - ( 7 * DAY_IN_SECONDS ) ) - MINUTE_IN_SECONDS );
		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( 'week-1-4' );
		$this->assertEquals( $active_for, true );

		// one minute before 28 days.
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, ( time() - ( 28 * DAY_IN_SECONDS ) ) + MINUTE_IN_SECONDS );
		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( 'week-1-4' );
		$this->assertEquals( $active_for, true );

		// 10 days.
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, ( time() - ( 10 * DAY_IN_SECONDS ) ) );
		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( 'week-1-4' );
		$this->assertEquals( $active_for, true );
	}

	/**
	 * @dataProvider range_provider
	 * Test wc_admin_active_in_date_range with data provided from range_provider.
	 *
	 * @param number  $store_age age in seconds of store.
	 * @param string  $range expected store range.
	 * @param boolean $expected expected boolean value.
	 */
	public function test_is_wc_admin_active_in_date_range( $store_age, $range, $expected ) {
		// 1 day.
		update_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION, time() - $store_age );

		$active_for = WCAdminHelper::is_wc_admin_active_in_date_range( $range );
		$this->assertEquals( $expected, $active_for );
	}

	/**
	 * @return array[] list of range options.
	 */
	public function range_provider() {
		return array(
			'1 day old store within week?'             => array( DAY_IN_SECONDS, 'week-1', true ),
			'10 day old store not within week?'        => array( 10 * DAY_IN_SECONDS, 'week-1', false ),
			'10 day old store within 1-4 weeks?'       => array( 10 * DAY_IN_SECONDS, 'week-1-4', true ),
			'1 day old store not within 1-4 weeks?'    => array( DAY_IN_SECONDS, 'week-1-4', false ),
			'2 month old store within 1-3 months?'     => array( 2 * MONTH_IN_SECONDS, 'month-1-3', true ),
			'5 month old store not within 1-3 months?' => array( 5 * MONTH_IN_SECONDS, 'month-1-3', false ),
			'5 month old store within 3-6 months?'     => array( 5 * MONTH_IN_SECONDS, 'month-3-6', true ),
			'7 month old store not within 3-6 months?' => array( 7 * MONTH_IN_SECONDS, 'month-3-6', false ),
			'9 month old store within 6+ months?'      => array( 9 * MONTH_IN_SECONDS, 'month-6+', true ),
			'2 month old store not within 6+ months?'  => array( 2 * MONTH_IN_SECONDS, 'month-6+', false ),
		);
	}

	/**
	 * Test is_fresh_site with registered date.
	 */
	public function test_is_fresh_site_user_registered_less_than_a_month() {
		update_option( 'fresh_site', '1' );
		$user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user );
		$this->assertTrue( WCAdminHelper::is_site_fresh() );

		// Update registered date to January.
		// The function should return false.
		wp_update_user(
			array(
				'ID'              => $user,
				'user_registered' => '2024-01-27 20:56:29',
			)
		);
		$this->assertFalse( WCAdminHelper::is_site_fresh() );
	}

	/**
	 * Test is_fresh_site with fresh_site option.
	 */
	public function test_is_fresh_site_fresh_site_option_must_be_1() {
		update_option( 'fresh_site', '0' );
		$user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user );
		$this->assertFalse( WCAdminHelper::is_site_fresh() );

		update_option( 'fresh_site', '1' );
		$this->assertTrue( WCAdminHelper::is_site_fresh() );
	}

	/**
	 * Get store page test data. This data is used to test is_store_page function.
	 *
	 * We don't use the data provider in this test because data provider are executed before setUpBeforeClass and cause other tests to fail since we need to create pages to generate the test data.
	 *
	 * @return array[] list of store page test data.
	 */
	public function get_store_page_test_data() {
		// Make sure pages are created.
		WC_Install::create_pages();

		return array(
			// Should basic store pages return true.
			array( 'cart', get_permalink( wc_get_page_id( 'cart' ) ), true ),
			array( 'shop', get_permalink( wc_get_page_id( 'shop' ) ), true ),
			array( 'checkout', get_permalink( wc_get_page_id( 'checkout' ) ), true ),
			array( 'product archive', get_post_type_archive_link( 'product' ), true ),
			array( 'product', get_permalink( self::$product_id ), true ),
			// Should return true if a shop page contains a query param.
			array( 'shop with query', get_permalink( wc_get_page_id( 'shop' ) ) . '?query=test', true ),
			// Should non-store pages return false.
			array( 'about-us', home_url( '/about-us/' ), false ),
			array( 'shopping-url', home_url( '/shopping-url/' ), false ),
			array( 'page with query', home_url( '/test?param1=value1&param2=value2' ), false ),
		);
	}

	/**
	 *
	 * Test is_current_page_store_page function with different URLs for basic store pages.
	 *
	 */
	public function test_is_current_page_store_page() {
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules( true );

		$test_data = $this->get_store_page_test_data();

		foreach ( $test_data as $data ) {
			list( $page_name, $url, $expected_result ) = $data;
			$this->go_to( $url );
			$result = WCAdminHelper::is_current_page_store_page();
			$this->assertEquals( $expected_result, $result, 'Test failed for ' . $page_name . ' with URL: ' . $url );
		}
	}

	/**
	 * Test is_current_page_store_page for product category, and product tag pages.
	 */
	public function test_is_current_page_store_page_for_category_and_tag() {
		// Create a product category.
		$category_name = 'Test Category ' . time();
		$category      = term_exists( $category_name, 'product_cat' );
		if ( ! $category ) {
			$category = wp_insert_term(
				$category_name,
				'product_cat',
				array(
					'slug' => sanitize_title( $category_name ),
				)
			);
		}

		// Create a product tag.
		$tag_name = 'Test Tag ' . time();
		$tag      = term_exists( $tag_name, 'product_tag' );
		if ( ! $tag ) {
			$tag = wp_insert_term( $tag_name, 'product_tag' );
		}

		flush_rewrite_rules();

		// Test product category page.
		$term_link = get_term_link( $category['term_id'], 'product_cat' );
		$this->go_to( $term_link );
		$is_store_page = WCAdminHelper::is_current_page_store_page();
		$this->assertTrue( $is_store_page, 'Failed to identify product category as store page ' . $term_link );

		// Test product tag page.
		$tag_link = get_term_link( $tag['term_id'], 'product_tag' );
		$this->go_to( $tag_link );
		$is_store_page = WCAdminHelper::is_current_page_store_page();
		$this->assertTrue( $is_store_page, 'Failed to identify product tag as store page ' . $tag_link );

		// Clean up.
		wp_delete_term( $category['term_id'], 'product_cat' );
		wp_delete_term( $tag['term_id'], 'product_tag' );
	}

	/**
	 * Test is_current_page_store_page when permalink structure is plain.
	 */
	public function test_is_current_page_store_page_when_permalink_structure_is_plain() {
		global $wp_rewrite;
		// Set permalink structure to plain .
		$wp_rewrite->set_permalink_structure( '' );
		delete_option( 'woocommerce_permalinks' );
		$wp_rewrite->flush_rules();

		$test_data = $this->get_store_page_test_data();
		foreach ( $test_data as $data ) {
			list( $page_name, $url, $expected_result ) = $data;
			$this->go_to( $url );
			$result = WCAdminHelper::is_current_page_store_page();
			$this->assertEquals( $expected_result, $result, 'Test failed for ' . $page_name . ' with URL: ' . $url );
		}
	}

	/**
	 * Test is_current_page_store_page when store page is not set.
	 */
	public function test_is_current_page_store_page_when_store_page_is_not_set() {
		// unset shop page.
		add_filter( 'woocommerce_get_shop_page_id', '__return_false' );

		$this->go_to( home_url( '/?post_type=product' ) );
		$this->assertTrue( WCAdminHelper::is_current_page_store_page(), 'Failed to identify product archive as store page' );

		remove_filter( 'woocommerce_get_shop_page_id', '__return_false' );
	}

	/**
	 * Test is_current_page_store_page with a non-store page that has a similar URL pattern.
	 */
	public function test_is_current_page_store_page_with_similar_url() {
		global $wp_rewrite;

		// Set up permalinks.
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		// Create a regular page with a shopping-related URL.
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Shopping URL',
				'post_name'    => 'shopping-url',
				'post_content' => 'This is a regular page with a shopping-related URL',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		// Go to the page.
		$page_url = get_permalink( $page_id );
		$this->go_to( $page_url );

		// Test if the page is a store page.
		$is_store_page = WCAdminHelper::is_current_page_store_page();
		$this->assertFalse( $is_store_page, 'Incorrectly identified regular page as store page ' . $page_url );

		// Clean up.
		wp_delete_post( $page_id, true );
	}

	/**
	 * Copied and modified from https://github.com/WordPress/wordpress-develop/blob/126e3bcc2b41c06c92f95d1796c2766bfbb19f86/tests/phpunit/includes/abstract-testcase.php#L1212.
	 *
	 * Sets the global state to as if a given URL has been requested.
	 *
	 * This sets:
	 * - The super globals.
	 * - The globals.
	 * - The query variables.
	 * - The main query.
	 *
	 * @param string $url The URL for the request.
	 */
	public function go_to( $url ) {
		/*
		 * Note: the WP and WP_Query classes like to silently fetch parameters
		 * from all over the place (globals, GET, etc), which makes it tricky
		 * to run them more than once without very carefully clearing everything.
		 */
		$_GET  = array();
		$_POST = array();
		foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow', 'current_screen' ) as $v ) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}
		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = isset( $parts['path'] ) ? $parts['path'] : '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the URL query vars into $_GET.
				wp_parse_str( $parts['query'], $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		} else {
			$req = $url;
		}
		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset( $_SERVER['PATH_INFO'] );

		wp_cache_flush();
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );
		$GLOBALS['wp_the_query'] = new WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$public_query_vars  = $GLOBALS['wp']->public_query_vars;
		$private_query_vars = $GLOBALS['wp']->private_query_vars;

		$GLOBALS['wp']                     = new WP(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp']->public_query_vars  = $public_query_vars;
		$GLOBALS['wp']->private_query_vars = $private_query_vars;

		$this->cleanup_query_vars();
		wp_reset_query(); // phpcs:ignore WordPress.WP.DiscouragedFunctions.wp_reset_query_wp_reset_query

		$GLOBALS['wp']->main( $parts['query'] );
	}

	/**
	 * Copied and modified from https://github.com/WordPress/wordpress-develop/blob/126e3bcc2b41c06c92f95d1796c2766bfbb19f86/tests/phpunit/includes/utils.php#L524.
	 *
	 * Clean out globals to stop them polluting wp and wp_query.
	 *
	 */
	private function cleanup_query_vars() {
		foreach ( $GLOBALS['wp']->public_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		foreach ( $GLOBALS['wp']->private_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		foreach ( get_taxonomies( array(), 'objects' ) as $t ) {
			if ( $t->publicly_queryable && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}

		foreach ( get_post_types( array(), 'objects' ) as $t ) {
			if ( is_post_type_viewable( $t ) && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}
	}
}
