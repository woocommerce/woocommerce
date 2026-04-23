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
}
