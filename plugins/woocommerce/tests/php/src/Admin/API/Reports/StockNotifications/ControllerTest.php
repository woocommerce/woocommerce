<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\StockNotifications;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use WC_Helper_Product;
use WC_REST_Unit_Test_Case;

/**
 * Stock notifications report API controller test.
 */
class ControllerTest extends WC_REST_Unit_Test_Case {

	use ReportTestTrait;

	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-analytics/reports/stock-notifications';

	/**
	 * Period covering 2026-03-01 to 2026-03-07.
	 *
	 * @var array
	 */
	const PERIOD = array(
		'after'  => '2026-03-01T00:00:00',
		'before' => '2026-03-07T23:59:59',
	);

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->set_up_stock_notifications_report();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		try {
			$this->tear_down_stock_notifications_report();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should register the report routes only when the feature is enabled.
	 * @testWith ["yes", 200]
	 *           ["no", 404]
	 *
	 * @param string $enabled         Feature option value.
	 * @param int    $expected_status Expected response status.
	 */
	public function test_routes_follow_the_feature_state( string $enabled, int $expected_status ): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, $enabled );

		$this->assertSame( 200, $this->request( '/wc-analytics/reports/stock', array() )->get_status(), 'Other analytics routes should exist either way.' );
		foreach ( array( self::ENDPOINT, self::ENDPOINT . '/stats' ) as $route ) {
			$this->assertSame( $expected_status, $this->request( $route, self::PERIOD )->get_status(), "Unexpected status for {$route}." );
		}
	}

	/**
	 * @testdox Should count a sign-up on the site-time day it was made, not the next UTC day.
	 */
	public function test_signups_use_site_timezone(): void {
		update_option( 'timezone_string', 'America/Los_Angeles' );
		$this->insert_row( 5, NotificationStatus::ACTIVE, '2026-03-10 06:30:00' );

		$local_day = $this->get_rows(
			array(
				'after'  => '2026-03-09T00:00:00',
				'before' => '2026-03-09T23:59:59',
			)
		);
		$next_day  = $this->get_rows(
			array(
				'after'  => '2026-03-10T00:00:00',
				'before' => '2026-03-10T23:59:59',
			)
		);

		$this->assertSame( 1, $local_day[5]['signups'], 'A sign-up at 23:30 local time belongs to that local day.' );
		$this->assertSame( 0, $next_day[5]['signups'], 'A sign-up at 23:30 local time does not belong to the next UTC day.' );
	}

	/**
	 * @testdox Should count cancelled and sent sign-ups but leave pending ones out entirely.
	 */
	public function test_signups_exclude_only_pending_rows(): void {
		$this->insert_row( 1, NotificationStatus::PENDING, '2026-03-02 10:00:00', 'a@example.com' );
		$this->insert_row( 1, NotificationStatus::CANCELLED, '2026-03-02 10:00:00', 'b@example.com' );
		$this->insert_row( 1, NotificationStatus::SENT, '2026-03-03 10:00:00', 'c@example.com' );
		$this->insert_row( 2, NotificationStatus::PENDING, '2026-03-02 10:00:00', 'd@example.com' );

		$rows = $this->get_rows( self::PERIOD );

		$this->assertSame( array( 1 ), array_keys( $rows ), 'A product with only pending sign-ups should not be listed.' );
		$this->assertSame( 2, $rows[1]['signups'], 'Cancelled and sent sign-ups count, pending ones do not.' );
		$this->assertSame( 2, $rows[1]['customers'], 'Pending sign-ups do not add customers.' );
		$this->assertSame( 0, $rows[1]['active_signups'] );
	}

	/**
	 * @testdox Should count active sign-ups whatever the period and measure the wait from the oldest one.
	 */
	public function test_active_signups_and_days_waiting(): void {
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-01-15 10:00:00' );
		$this->insert_active_row_days_ago( 2, 3 );
		$this->insert_active_row_days_ago( 2, 10 );
		$this->insert_row( 2, NotificationStatus::SENT, gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) );

		$rows = $this->get_rows( self::PERIOD );

		$this->assertSame( 0, $rows[1]['signups'], 'An active sign-up from before the period is not a sign-up in it.' );
		$this->assertSame( 1, $rows[1]['active_signups'], 'An active sign-up from before the period is still waiting.' );
		$this->assertSame( 2, $rows[2]['active_signups'], 'Only active rows count as waiting.' );
		$this->assertSame( 10, $rows[2]['days_waiting'], 'The wait is measured from the oldest active sign-up.' );
	}

	/**
	 * @testdox Should count customers by distinct email.
	 */
	public function test_customers_are_distinct_emails(): void {
		$this->insert_row( 1, NotificationStatus::SENT, '2026-03-02 10:00:00', 'same@example.com' );
		$this->insert_row( 1, NotificationStatus::CANCELLED, '2026-03-04 10:00:00', 'same@example.com' );

		$rows = $this->get_rows( self::PERIOD );

		$this->assertSame( 2, $rows[1]['signups'] );
		$this->assertSame( 1, $rows[1]['customers'] );
	}

	/**
	 * @testdox Should reject an unknown orderby and sort by active sign-ups, descending, by default.
	 */
	public function test_default_order_and_invalid_orderby(): void {
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		$this->insert_row( 2, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		$this->insert_row( 2, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		$this->insert_row( 3, NotificationStatus::SENT, '2026-03-02 10:00:00' );

		$this->assertSame( array( 2, 1, 3 ), array_column( $this->request( self::ENDPOINT, self::PERIOD )->get_data(), 'product_id' ) );
		$this->assertSame( 400, $this->request( self::ENDPOINT, array_merge( self::PERIOD, array( 'orderby' => 'date' ) ) )->get_status() );
	}

	/**
	 * @testdox Should accept orderby $orderby.
	 * @testWith ["product_id"]
	 *           ["signups"]
	 *           ["customers"]
	 *           ["active_signups"]
	 *           ["days_waiting"]
	 *
	 * @param string $orderby Orderby value.
	 */
	public function test_accepts_orderby( string $orderby ): void {
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );

		$response = $this->request( self::ENDPOINT, array_merge( self::PERIOD, array( 'orderby' => $orderby ) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 1 ), array_column( $response->get_data(), 'product_id' ) );
	}

	/**
	 * @testdox Should paginate the rows and report the totals in headers.
	 */
	public function test_pagination_headers(): void {
		foreach ( array( 1, 2, 3 ) as $product_id ) {
			$this->insert_row( $product_id, NotificationStatus::SENT, '2026-03-02 10:00:00' );
		}
		$args = array_merge(
			self::PERIOD,
			array(
				'per_page' => 2,
				'orderby'  => 'product_id',
				'order'    => 'asc',
			)
		);

		$first  = $this->request( self::ENDPOINT, $args );
		$second = $this->request( self::ENDPOINT, array_merge( $args, array( 'page' => 2 ) ) );

		$this->assertSame( 3, (int) $first->get_headers()['X-WP-Total'] );
		$this->assertSame( 2, (int) $first->get_headers()['X-WP-TotalPages'] );
		$this->assertSame( array( 1, 2 ), array_column( $first->get_data(), 'product_id' ) );
		$this->assertSame( array( 3 ), array_column( $second->get_data(), 'product_id' ) );
	}

	/**
	 * @testdox Should restrict the rows to the requested products.
	 */
	public function test_products_filter(): void {
		foreach ( array( 1, 2, 3 ) as $product_id ) {
			$this->insert_row( $product_id, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		}

		$rows = $this->get_rows( array_merge( self::PERIOD, array( 'products' => '1,3' ) ) );

		$this->assertEqualsCanonicalizing( array( 1, 3 ), array_keys( $rows ) );
	}

	/**
	 * @testdox Should add product details, with a placeholder for deleted products.
	 */
	public function test_extended_info(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$deleted_id = $product->get_id() + 1000;
		$this->insert_row( $product->get_id(), NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		$this->insert_row( $deleted_id, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );

		$rows = $this->get_rows( array_merge( self::PERIOD, array( 'extended_info' => 'true' ) ) );

		$this->assertSame(
			array(
				'name'      => $product->get_name(),
				'permalink' => $product->get_permalink(),
				'edit_url'  => get_edit_post_link( $product->get_id(), 'raw' ),
			),
			$rows[ $product->get_id() ]['extended_info']
		);
		$this->assertSame(
			array(
				'name'      => "#{$deleted_id} (Deleted)",
				'permalink' => '',
				'edit_url'  => '',
			),
			$rows[ $deleted_id ]['extended_info']
		);
	}

	/**
	 * @testdox Should serve cached data until a notification write bumps the cache version.
	 */
	public function test_notification_write_invalidates_cache(): void {
		$product = WC_Helper_Product::create_simple_product();
		$period  = array(
			'after'  => gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ) . 'T00:00:00',
			'before' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) . 'T23:59:59',
		);
		$this->insert_row( $product->get_id(), NotificationStatus::ACTIVE, gmdate( 'Y-m-d H:i:s' ) );
		$this->assertSame( 1, $this->get_rows( $period )[ $product->get_id() ]['signups'] );

		$this->insert_row( $product->get_id(), NotificationStatus::ACTIVE, gmdate( 'Y-m-d H:i:s' ) );
		$this->assertSame( 1, $this->get_rows( $period )[ $product->get_id() ]['signups'], 'A direct insert should not invalidate the cache.' );

		$version      = (int) get_option( StockNotificationsDataStore::REPORTS_VERSION_OPTION, 0 );
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'crud@example.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertSame( $version + 1, (int) get_option( StockNotificationsDataStore::REPORTS_VERSION_OPTION ), 'Saving a notification should bump the cache version.' );
		$this->assertSame( 3, $this->get_rows( $period )[ $product->get_id() ]['signups'], 'The report should be recomputed after the bump.' );
	}

	/**
	 * Get the report rows keyed by product ID.
	 *
	 * @param array $params Query parameters.
	 * @return array<int, array>
	 */
	private function get_rows( array $params ): array {
		$response = $this->request( self::ENDPOINT, $params );
		$this->assertSame( 200, $response->get_status(), 'The report request failed.' );

		return array_column( $response->get_data(), null, 'product_id' );
	}
}
