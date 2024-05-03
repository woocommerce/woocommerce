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
class WC_Admin_Tests_Admin_Helper extends WP_UnitTestCase {

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
}
