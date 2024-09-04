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
	 * Set up before class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Ensure pages exist.
		WC_Install::create_pages();

		// Set up permalinks.
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => '/shop/%product_cat%',
				'category_base'          => 'product-category',
				'tag_base'               => 'product-tag',
				'attribute_base'         => 'test',
				'use_verbose_page_rules' => true,
			)
		);
	}

	/**
	 * Tear down after class.
	 */
	public static function tearDownAfterClass(): void {
		// Delete pages.
		wp_delete_post( get_option( 'woocommerce_shop_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_cart_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_checkout_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_myaccount_page_id' ), true );
		wp_delete_post( wc_terms_and_conditions_page_id(), true );
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
		return array(
			array( get_permalink( wc_get_page_id( 'cart' ) ), true ), // Test case 1: URL matches cart page.
			array( 'https://example.com/product-category/sample-category/', true ), // Test case 3: URL matches product category page.
			array( 'https://example.com/product-tag/sample-tag/', true ), // Test case 4: URL matches product tag page.
			array( 'https://example.com/shop/uncategorized/test/', true ), // Test case 5: URL matches product page.
			array( '/shop/t-shirt/test/', true ), // Test case 6: URL path matches product page.
			array( 'https://example.com/about-us/', false ), // Test case 7: URL does not match any store page.
		);
	}

	/**
	 *
	 * Test is_store_page function with different URLs.
	 *
	 */
	public function test_is_store_page() {
		global $wp_rewrite;

		$wp_rewrite = $this->getMockBuilder( 'WP_Rewrite' )->getMock(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$permalink_structure = array(
			'category_base' => 'product-category',
			'tag_base'      => 'product-tag',
			'product_base'  => 'product',
		);

		$wp_rewrite->expects( $this->any() )
			->method( 'generate_rewrite_rule' )
			->willReturn( array( 'shop/(.+?)/?$' => 'index.php?product_cat=$matches[1]&year=$matches[2]' ) );

		$test_data = $this->get_store_page_test_data();

		foreach ( $test_data as $data ) {
			list( $url, $expected_result ) = $data;
			$result                        = WCAdminHelper::is_store_page( $url );
			$this->assertEquals( $expected_result, $result );
		}
	}

	/**
	 * Test is_store_page with the defined post_type param.
	 */
	public function test_is_store_page_with_post_type() {
		// Test with post_type=product.
		$this->assertTrue( WCAdminHelper::is_store_page( 'https://example.com/?post_type=product' ) );
		// Test with post_type=product and other params.
		$this->assertTrue( WCAdminHelper::is_store_page( 'https://example.com/test?param1=value1&post_type=product&param2=value2' ) );

		// should return false if post_type is not product.
		$this->assertFalse( WCAdminHelper::is_store_page( 'https://example.com/test?param1=value1&param2=value2' ) );
	}

	/** Test product archive link is store page even if shop page not set. */
	public function test_is_store_page_even_if_shop_page_not_set() {
		$shop_page_id = get_option( 'woocommerce_shop_page_id' );

		// Unset shop page.
		add_filter(
			'woocommerce_get_shop_page_id',
			function () {
				return false;
			},
			10,
			1
		);
		global $wp_post_types;
		$wp_post_types['product']->has_archive = 'shop';

		$product_post_type = get_post_type_object( 'product' );

		$link = get_post_type_archive_link( 'product' );
		$this->assertTrue( WCAdminHelper::is_store_page( $link ) );
	}
}
