<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin\Analytics;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\Analytics\RestController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the Back in Stock Notifications analytics REST controller.
 */
class RestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private int $admin_user;

	/**
	 * Customer user ID.
	 *
	 * @var int
	 */
	private int $customer_user;

	/**
	 * Set up test environment: register routes, create users, clear tables.
	 */
	public function setUp(): void {
		parent::setUp();

		// The controller auto-registers on rest_api_init in production, but in
		// test the constant may or may not be defined at server bootstrap.
		// Explicitly register to guarantee routes exist.
		( new RestController() )->register_routes();

		$this->admin_user = $this->factory->user->create(
			array( 'role' => 'administrator' )
		);

		$this->customer_user = $this->factory->user->create(
			array( 'role' => 'customer' )
		);

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
		parent::tearDown();
	}

	/**
	 * Helper: seed a notification with the given properties and save.
	 *
	 * @param array $props Override properties.
	 * @return Notification
	 */
	private function seed_notification( array $props ): Notification {
		$notification = new Notification();
		$notification->set_product_id( (int) ( $props['product_id'] ?? 1 ) );
		$notification->set_user_id( (int) ( $props['user_id'] ?? 1 ) );
		$notification->set_user_email( (string) ( $props['user_email'] ?? 'seeder@example.test' ) );
		$notification->set_status( (string) ( $props['status'] ?? NotificationStatus::ACTIVE ) );

		if ( isset( $props['date_created'] ) ) {
			$notification->set_date_created( $props['date_created'] );
		}
		if ( isset( $props['date_notified'] ) ) {
			$notification->set_date_notified( $props['date_notified'] );
		}

		$notification->save();
		return $notification;
	}

	/**
	 * Summary endpoint returns correct totals and per-product rows for a seeded store.
	 */
	public function test_summary_returns_expected_shape_and_counts(): void {
		wp_set_current_user( $this->admin_user );

		// Seed: product 101 (3 active, 1 sent), product 202 (1 active), product 303 (1 cancelled).
		$this->seed_notification( array( 'product_id' => 101, 'user_id' => 11, 'status' => NotificationStatus::ACTIVE ) );
		$this->seed_notification( array( 'product_id' => 101, 'user_id' => 12, 'status' => NotificationStatus::ACTIVE ) );
		$this->seed_notification( array( 'product_id' => 101, 'user_id' => 13, 'status' => NotificationStatus::ACTIVE ) );
		$this->seed_notification(
			array(
				'product_id'    => 101,
				'user_id'       => 14,
				'status'        => NotificationStatus::SENT,
				'date_notified' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
		$this->seed_notification( array( 'product_id' => 202, 'user_id' => 21, 'status' => NotificationStatus::ACTIVE ) );
		$this->seed_notification( array( 'product_id' => 303, 'user_id' => 31, 'status' => NotificationStatus::CANCELLED ) );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/summary' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'totals', $data );
		$this->assertArrayHasKey( 'all_time', $data['totals'] );
		$this->assertArrayHasKey( 'this_week', $data['totals'] );

		$this->assertSame( 6, $data['totals']['all_time']['total_signups'] );
		$this->assertSame( 4, $data['totals']['all_time']['active_signups'] );
		$this->assertSame( 1, $data['totals']['all_time']['notifications_sent'] );
		$this->assertSame( 1, $data['totals']['all_time']['cancelled'] );

		$this->assertArrayHasKey( 'products', $data );
		$this->assertIsArray( $data['products'] );
		$this->assertGreaterThanOrEqual( 3, count( $data['products'] ) );

		// First product should be 101 (most active).
		$this->assertSame( 101, $data['products'][0]['product_id'] );
		$this->assertSame( 3, $data['products'][0]['active_signups'] );
		$this->assertSame( 4, $data['products'][0]['total_signups'] );
		$this->assertSame( 1, $data['products'][0]['notifications_sent'] );
		$this->assertArrayHasKey( 'product_name', $data['products'][0] );
		$this->assertArrayHasKey( 'product_edit_link', $data['products'][0] );

		$this->assertArrayHasKey( 'total', $data );
		$this->assertSame( 3, $data['total'] );
	}

	/**
	 * Timeseries endpoint returns dense dated rows spanning the requested window.
	 */
	public function test_timeseries_returns_dense_window(): void {
		wp_set_current_user( $this->admin_user );

		$today            = gmdate( 'Y-m-d H:i:s' );
		$three_days_ago   = gmdate( 'Y-m-d H:i:s', time() - ( 3 * DAY_IN_SECONDS ) );

		$this->seed_notification(
			array(
				'product_id'   => 501,
				'user_id'      => 51,
				'status'       => NotificationStatus::ACTIVE,
				'date_created' => $today,
			)
		);
		$this->seed_notification(
			array(
				'product_id'    => 501,
				'user_id'       => 52,
				'status'        => NotificationStatus::SENT,
				'date_created'  => $three_days_ago,
				'date_notified' => $today,
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/timeseries' );
		$request->set_param( 'days', 7 );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'rows', $data );
		$this->assertCount( 7, $data['rows'] );
		$this->assertSame( 7, $data['days'] );

		// Each row has the three required keys.
		foreach ( $data['rows'] as $row ) {
			$this->assertArrayHasKey( 'date', $row );
			$this->assertArrayHasKey( 'signups', $row );
			$this->assertArrayHasKey( 'notifications_sent', $row );
		}

		// The most recent row should be today and include at least one signup / notification.
		$last = end( $data['rows'] );
		$this->assertSame( gmdate( 'Y-m-d' ), $last['date'] );
		$this->assertGreaterThanOrEqual( 1, $last['signups'] );
		$this->assertGreaterThanOrEqual( 1, $last['notifications_sent'] );
	}

	/**
	 * Top-demand endpoint caps at 10 rows even when more products qualify.
	 */
	public function test_top_demand_caps_at_ten(): void {
		wp_set_current_user( $this->admin_user );

		// 12 distinct products, each with one active signup so they all qualify.
		for ( $i = 1; $i <= 12; $i++ ) {
			$this->seed_notification(
				array(
					'product_id' => 1000 + $i,
					'user_id'    => 9000 + $i,
					'status'     => NotificationStatus::ACTIVE,
				)
			);
		}

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/top-demand' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'rows', $data );
		$this->assertCount( 10, $data['rows'] );

		// Every returned row must have active_signups > 0.
		foreach ( $data['rows'] as $row ) {
			$this->assertArrayHasKey( 'product_id', $row );
			$this->assertArrayHasKey( 'active_signups', $row );
			$this->assertGreaterThan( 0, $row['active_signups'] );
		}
	}

	/**
	 * Recent-activity endpoint returns sent notifications sorted newest-first.
	 */
	public function test_recent_activity_returns_dispatched_notifications(): void {
		wp_set_current_user( $this->admin_user );

		$now = time();
		// Three sent notifications and one still active (should be excluded).
		$this->seed_notification(
			array(
				'product_id'    => 701,
				'user_id'       => 71,
				'status'        => NotificationStatus::SENT,
				'date_notified' => gmdate( 'Y-m-d H:i:s', $now - 300 ),
			)
		);
		$this->seed_notification(
			array(
				'product_id'    => 702,
				'user_id'       => 72,
				'status'        => NotificationStatus::SENT,
				'date_notified' => gmdate( 'Y-m-d H:i:s', $now - 100 ),
			)
		);
		$this->seed_notification(
			array(
				'product_id'    => 703,
				'user_id'       => 73,
				'status'        => NotificationStatus::SENT,
				'date_notified' => gmdate( 'Y-m-d H:i:s', $now - 200 ),
			)
		);
		$this->seed_notification(
			array(
				'product_id' => 704,
				'user_id'    => 74,
				'status'     => NotificationStatus::ACTIVE,
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/recent' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'rows', $data );
		$this->assertCount( 3, $data['rows'] );

		// Sorted newest first.
		$this->assertSame( 702, $data['rows'][0]['product_id'] );
		$this->assertSame( 703, $data['rows'][1]['product_id'] );
		$this->assertSame( 701, $data['rows'][2]['product_id'] );

		foreach ( $data['rows'] as $row ) {
			$this->assertArrayHasKey( 'product_name', $row );
			$this->assertArrayHasKey( 'product_edit_link', $row );
			$this->assertArrayHasKey( 'date_notified', $row );
		}
	}

	/**
	 * Non-admin (customer) hitting the summary endpoint is rejected.
	 */
	public function test_non_admin_cannot_view_summary(): void {
		wp_set_current_user( $this->customer_user );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/summary' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Non-admin hitting the timeseries endpoint is rejected.
	 */
	public function test_non_admin_cannot_view_timeseries(): void {
		wp_set_current_user( $this->customer_user );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/timeseries' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Unauthenticated (logged-out) request is rejected.
	 */
	public function test_unauthenticated_cannot_view_top_demand(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/top-demand' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Summary endpoint exposes today and this_month windows alongside the
	 * existing all_time and this_week buckets.
	 */
	public function test_summary_exposes_today_and_this_month_buckets(): void {
		wp_set_current_user( $this->admin_user );

		// One signup today, one 10 days ago, one 60 days ago.
		$now = time();
		$this->seed_notification(
			array(
				'product_id'   => 401,
				'user_id'      => 41,
				'status'       => NotificationStatus::ACTIVE,
				'date_created' => gmdate( 'Y-m-d H:i:s', $now ),
			)
		);
		$this->seed_notification(
			array(
				'product_id'   => 401,
				'user_id'      => 42,
				'status'       => NotificationStatus::ACTIVE,
				'date_created' => gmdate( 'Y-m-d H:i:s', $now - ( 10 * DAY_IN_SECONDS ) ),
			)
		);
		$this->seed_notification(
			array(
				'product_id'   => 401,
				'user_id'      => 43,
				'status'       => NotificationStatus::ACTIVE,
				'date_created' => gmdate( 'Y-m-d H:i:s', $now - ( 60 * DAY_IN_SECONDS ) ),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/summary' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$totals = $response->get_data()['totals'];
		$this->assertArrayHasKey( 'today', $totals );
		$this->assertArrayHasKey( 'this_month', $totals );
		$this->assertArrayHasKey( 'this_week', $totals );
		$this->assertArrayHasKey( 'all_time', $totals );

		$this->assertSame( 1, $totals['today']['total_signups'] );
		$this->assertSame( 2, $totals['this_month']['total_signups'] );
		$this->assertSame( 3, $totals['all_time']['total_signups'] );
	}

	/**
	 * `/top-demand?sort_by=most_overdue` ranks products by their oldest unfulfilled
	 * sign-up.
	 */
	public function test_top_demand_most_overdue_orders_by_oldest_signup(): void {
		wp_set_current_user( $this->admin_user );

		$now = time();

		// Product 501: oldest signup 30 days ago.
		$this->seed_notification(
			array(
				'product_id'   => 501,
				'user_id'      => 51,
				'status'       => NotificationStatus::ACTIVE,
				'date_created' => gmdate( 'Y-m-d H:i:s', $now - ( 30 * DAY_IN_SECONDS ) ),
			)
		);

		// Product 502: oldest signup 60 days ago.
		$this->seed_notification(
			array(
				'product_id'   => 502,
				'user_id'      => 52,
				'status'       => NotificationStatus::ACTIVE,
				'date_created' => gmdate( 'Y-m-d H:i:s', $now - ( 60 * DAY_IN_SECONDS ) ),
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/top-demand' );
		$request->set_param( 'sort_by', 'most_overdue' );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$rows = $response->get_data()['rows'];
		$this->assertSame( 502, $rows[0]['product_id'] );
		$this->assertSame( 501, $rows[1]['product_id'] );
		$this->assertGreaterThan(
			$rows[1]['days_overdue'],
			$rows[0]['days_overdue']
		);
	}

	/**
	 * `/top-demand?sort_by=period_signups&window=week` only counts sign-ups
	 * created inside the requested window.
	 */
	public function test_top_demand_period_signups_filters_by_window(): void {
		wp_set_current_user( $this->admin_user );

		$now = time();

		// Product 601: 2 signups in the last 3 days, 5 signups 60 days ago.
		for ( $i = 0; $i < 2; $i++ ) {
			$this->seed_notification(
				array(
					'product_id'   => 601,
					'user_id'      => 600 + $i,
					'status'       => NotificationStatus::ACTIVE,
					'date_created' => gmdate( 'Y-m-d H:i:s', $now - ( 3 * DAY_IN_SECONDS ) ),
				)
			);
		}
		for ( $i = 0; $i < 5; $i++ ) {
			$this->seed_notification(
				array(
					'product_id'   => 601,
					'user_id'      => 700 + $i,
					'status'       => NotificationStatus::ACTIVE,
					'date_created' => gmdate( 'Y-m-d H:i:s', $now - ( 60 * DAY_IN_SECONDS ) ),
				)
			);
		}

		// Product 602: 4 signups in the last 3 days, 0 older.
		for ( $i = 0; $i < 4; $i++ ) {
			$this->seed_notification(
				array(
					'product_id'   => 602,
					'user_id'      => 800 + $i,
					'status'       => NotificationStatus::ACTIVE,
					'date_created' => gmdate( 'Y-m-d H:i:s', $now - ( 3 * DAY_IN_SECONDS ) ),
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc-analytics/back-in-stock/top-demand' );
		$request->set_param( 'sort_by', 'period_signups' );
		$request->set_param( 'window', 'week' );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'period_signups', $data['sort_by'] );
		$this->assertSame( 'week', $data['window'] );

		$rows = $data['rows'];
		// Inside the week window, product 602 should rank above product 601
		// (4 vs 2 signups), and 601's older 60-day signups must not leak into
		// the count.
		$this->assertSame( 602, $rows[0]['product_id'] );
		$this->assertSame( 4, $rows[0]['signups'] );
		$this->assertSame( 601, $rows[1]['product_id'] );
		$this->assertSame( 2, $rows[1]['signups'] );
	}
}
