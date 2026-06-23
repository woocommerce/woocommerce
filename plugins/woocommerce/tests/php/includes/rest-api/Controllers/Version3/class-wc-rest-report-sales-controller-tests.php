<?php

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests for WC_REST_Report_Sales_Controller (v3), focused on the per-period
 * `refunds` field added to fix WOOPLUG-104 / GH #27552.
 */
class WC_REST_Report_Sales_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Stores the previous HPOS state. The legacy sales report queries posts directly.
	 *
	 * @var bool
	 */
	private static $hpos_prev_state;

	/**
	 * Disable HPOS before tests — legacy sales report queries posts directly.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		include_once WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php';
		include_once WC()->plugin_path() . '/includes/admin/reports/class-wc-report-sales-by-date.php';
		self::$hpos_prev_state = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		OrderHelper::toggle_cot_feature_and_usage( false );
	}

	/**
	 * Restore HPOS state after tests.
	 */
	public static function tearDownAfterClass(): void {
		OrderHelper::toggle_cot_feature_and_usage( self::$hpos_prev_state );
		parent::tearDownAfterClass();
	}

	/**
	 * Set up the test user and clear cached report data.
	 *
	 * WC_Admin_Report holds its query cache in a protected static array
	 * (`$cached_results`) that survives transactional rollback, so each
	 * test must reset it explicitly to avoid leakage across tests.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user(
			$this->factory->user->create( array( 'role' => 'administrator' ) )
		);
		delete_transient( 'wc_report_sales_by_date' );

		$reflection = new ReflectionClass( WC_Admin_Report::class );
		foreach ( array( 'cached_results', 'transients_to_update' ) as $property_name ) {
			$property = $reflection->getProperty( $property_name );
			$property->setAccessible( true );
			$property->setValue( null, array() );
		}
	}

	/**
	 * Helper: invoke the v3 controller and return the response body as an array.
	 *
	 * @param string $period Period to request.
	 * @return array
	 */
	private function get_report( string $period = 'month' ): array {
		$request = new WP_REST_Request( 'GET', '/wc/v3/reports/sales' );
		$request->set_param( 'period', $period );

		$controller = new WC_REST_Report_Sales_Controller();
		$response   = $controller->prepare_item_for_response( null, $request );
		$this->assertInstanceOf( WP_REST_Response::class, $response );

		return $response->get_data();
	}

	/**
	 * @testdox Should populate per-day refunds when an order is refunded on the same day.
	 *
	 * The sum assertion holds for this scenario (single same-day refund inside the range), but
	 * is not a general invariant: `total_refunds` comes from a different query (`full_refunds`)
	 * that counts a refunded-status order's full parent total whenever any of its refund posts
	 * falls in the range, while per-period `refunds` come from `refund_lines` (per-refund-post
	 * amounts). The two can diverge when refunds straddle the report range boundary.
	 */
	public function test_refunds_field_populated_for_same_day_refund_in_range(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		wc_create_refund(
			array(
				'amount'   => 7,
				'order_id' => $order->get_id(),
			)
		);

		$data  = $this->get_report( 'month' );
		$today = current_datetime()->format( 'Y-m-d' );

		$this->assertArrayHasKey( $today, $data['totals'], 'Today\'s bucket should exist in the response.' );
		$this->assertArrayHasKey( 'refunds', $data['totals'][ $today ], 'Per-day record should expose a refunds field.' );
		$this->assertSame( '7.00', $data['totals'][ $today ]['refunds'], 'Today\'s refunds should equal the refund amount.' );
		$this->assertSame( $data['total_refunds'], (float) array_sum( wp_list_pluck( $data['totals'], 'refunds' ) ), 'Per-period refunds should sum to top-level total_refunds in this same-day scenario.' );
	}

	/**
	 * @testdox Should attribute the refund to the refund date when it differs from the order date.
	 */
	public function test_refunds_attributed_to_refund_date_not_order_date(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$refund = wc_create_refund(
			array(
				'amount'   => 5,
				'order_id' => $order->get_id(),
			)
		);

		$yesterday        = current_datetime()->modify( '-1 day' )->format( 'Y-m-d' );
		$backdated_string = $yesterday . ' 12:00:00';
		wp_update_post(
			array(
				'ID'            => $refund->get_id(),
				'post_date'     => $backdated_string,
				'post_date_gmt' => $backdated_string,
			)
		);

		$data  = $this->get_report( 'month' );
		$today = current_datetime()->format( 'Y-m-d' );

		$this->assertArrayHasKey( $yesterday, $data['totals'], 'Yesterday\'s bucket should exist in the response.' );
		$this->assertSame( '5.00', $data['totals'][ $yesterday ]['refunds'], 'Refund should be attributed to the refund date.' );
		$this->assertSame( '0.00', $data['totals'][ $today ]['refunds'], 'Order date bucket should not include the refund.' );
	}

	/**
	 * @testdox Should expose refunds as a formatted decimal string of "0.00" on days with no refunds.
	 */
	public function test_refunds_is_zero_string_on_days_without_refunds(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$data = $this->get_report( 'month' );

		foreach ( $data['totals'] as $period => $bucket ) {
			$this->assertArrayHasKey( 'refunds', $bucket, "Bucket $period should always include a refunds key." );
			$this->assertSame( '0.00', $bucket['refunds'], "Bucket $period should have refunds = '0.00' when no refund occurred." );
		}
	}

	/**
	 * @testdox Should bucket refunds by local time, matching the sales/orders bucketing, in a non-UTC site.
	 *
	 * Regression for the day-mismatch hazard: if the controller's refund loop bucketed by UTC
	 * (gmdate) while the sales/orders/items/coupons loops bucket by local time (date), a refund
	 * placed near midnight local time would line up under a different day than its order — making
	 * per-row net sales (sales - refunds) wrong.
	 */
	public function test_refunds_bucketed_by_local_time_in_non_utc_site(): void {
		$previous_php_tz = date_default_timezone_get();
		$previous_wp_tz  = get_option( 'timezone_string' );

		// Pacific/Auckland is UTC+12 (or +13 in DST) — large enough that a local-time-of-02:00
		// is the previous calendar day in UTC, surfacing any date()/gmdate() mismatch.
		update_option( 'timezone_string', 'Pacific/Auckland' );
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- Need to change the PHP timezone to exercise local-vs-UTC date bucketing.
		date_default_timezone_set( 'Pacific/Auckland' );

		try {
			$order = WC_Helper_Order::create_order();
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();

			$refund = wc_create_refund(
				array(
					'amount'   => 3,
					'order_id' => $order->get_id(),
				)
			);

			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Need local-zone date for the assertion.
			$local_today     = date( 'Y-m-d' );
			$local_post_date = $local_today . ' 02:00:00';
			wp_update_post(
				array(
					'ID'        => $refund->get_id(),
					'post_date' => $local_post_date,
				)
			);

			$data = $this->get_report( 'month' );

			$this->assertArrayHasKey(
				$local_today,
				$data['totals'],
				'Local-time today should exist as a bucket key.'
			);
			$this->assertSame(
				'3.00',
				$data['totals'][ $local_today ]['refunds'],
				'Refund must bucket by local time so it lines up with sales/orders in the same row.'
			);
		} finally {
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- Restore the original PHP timezone.
			date_default_timezone_set( $previous_php_tz );
			update_option( 'timezone_string', $previous_wp_tz );
		}
	}

	/**
	 * @testdox Should advertise refunds in the totals schema as a decimal string property.
	 */
	public function test_schema_describes_refunds_field(): void {
		$controller = new WC_REST_Report_Sales_Controller();
		$schema     = $controller->get_item_schema();

		$this->assertSame( 'object', $schema['properties']['totals']['type'], 'totals should be an object, not an array.' );
		$this->assertArrayHasKey( 'additionalProperties', $schema['properties']['totals'], 'totals should describe per-period buckets via additionalProperties.' );

		$bucket_schema = $schema['properties']['totals']['additionalProperties'];
		$this->assertSame( 'object', $bucket_schema['type'] );
		$this->assertArrayHasKey( 'refunds', $bucket_schema['properties'], 'Per-period bucket should declare a refunds property.' );
		$this->assertSame( 'string', $bucket_schema['properties']['refunds']['type'], 'refunds should be a string (decimal).' );
	}
}
