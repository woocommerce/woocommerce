<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\StockNotifications;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\StockNotifications\Controller;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use Exception;
use WC_Data_Store;
use WC_Helper_Product;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Stock notifications report API controller test.
 */
class ControllerTest extends WC_REST_Unit_Test_Case {

	use ReportTestTrait;

	/**
	 * Routes depend on the feature option, so each test needs a fresh server.
	 *
	 * @var bool
	 */
	protected static $reuse_rest_server = false;

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
	 * @testdox Should register the report data stores only when the feature is enabled.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $enabled    Feature option value.
	 * @param bool   $registered Whether the data stores should load.
	 */
	public function test_data_stores_follow_the_feature_state( string $enabled, bool $registered ): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, $enabled );

		foreach ( array( 'report-stock-notifications', 'report-stock-notifications-stats' ) as $name ) {
			try {
				WC_Data_Store::load( $name );
				$loaded = true;
			} catch ( Exception $e ) {
				$loaded = false;
			}
			$this->assertSame( $registered, $loaded, "Unexpected data store state for {$name}." );
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
		$this->assertArrayNotHasKey( 5, $next_day, 'A sign-up at 23:30 local time does not belong to the next UTC day.' );
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
	}

	/**
	 * @testdox Should count sends on their own date, listing products with no sign-ups in the period.
	 */
	public function test_notified_uses_its_own_date(): void {
		$before = '2026-02-01 10:00:00';
		$during = '2026-03-03 10:00:00';
		$this->insert_row( 1, NotificationStatus::SENT, $before, 'a@example.com', $during );
		$this->insert_row( 2, NotificationStatus::SENT, $during, 'd@example.com', $before );
		$this->insert_row( 3, NotificationStatus::CANCELLED, $before, 'e@example.com' );

		$rows = $this->get_rows( self::PERIOD );

		$this->assertEqualsCanonicalizing( array( 1, 2 ), array_keys( $rows ), 'A product with no activity in the period is not listed.' );
		$this->assertSame( 0, $rows[1]['signups'], 'Rows created before the period are not sign-ups.' );
		$this->assertSame( 0, $rows[1]['customers'], 'A send alone does not make a customer in the period.' );
		$this->assertSame( 1, $rows[1]['notifications_sent'], 'A send in the period counts, whenever the sign-up was made.' );
		$this->assertSame( 1, $rows[2]['signups'] );
		$this->assertSame( 0, $rows[2]['notifications_sent'], 'A send before the period does not count.' );
	}

	/**
	 * @testdox Should count customers as distinct emails among the period's sign-ups.
	 */
	public function test_customers_count_distinct_emails_that_signed_up(): void {
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-02 10:00:00', 'a@example.com' );
		$this->insert_row( 1, NotificationStatus::CANCELLED, '2026-03-03 10:00:00', 'a@example.com' );
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-02 10:00:00', 'b@example.com' );
		$this->insert_row( 1, NotificationStatus::PENDING, '2026-03-02 10:00:00', 'c@example.com' );
		$this->insert_row( 1, NotificationStatus::SENT, '2026-02-01 10:00:00', 'd@example.com', '2026-03-03 10:00:00' );

		$rows = $this->get_rows( self::PERIOD );

		$this->assertSame( 3, $rows[1]['signups'] );
		$this->assertSame( 2, $rows[1]['customers'], 'Repeat, pending and earlier sign-ups do not add customers.' );
	}

	/**
	 * @testdox Should reject an unknown orderby and sort by sign-ups, descending, by default.
	 */
	public function test_default_order_and_invalid_orderby(): void {
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		$this->insert_row( 2, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		$this->insert_row( 2, NotificationStatus::SENT, '2026-03-03 10:00:00' );
		$this->insert_row( 3, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );
		$this->insert_row( 3, NotificationStatus::SENT, '2026-03-03 10:00:00' );
		$this->insert_row( 3, NotificationStatus::CANCELLED, '2026-03-04 10:00:00' );
		$this->insert_row( 3, NotificationStatus::ACTIVE, '2026-02-01 10:00:00' );

		$this->assertSame( array( 3, 2, 1 ), array_column( $this->request( self::ENDPOINT, self::PERIOD )->get_data(), 'product_id' ) );
		$this->assertSame( 400, $this->request( self::ENDPOINT, array_merge( self::PERIOD, array( 'orderby' => 'date' ) ) )->get_status() );
	}

	/**
	 * @testdox Should sort by $orderby, descending, breaking ties by product ID.
	 * @testWith ["product_id", [3, 2, 1]]
	 *           ["signups", [2, 3, 1]]
	 *           ["notifications_sent", [1, 3, 2]]
	 *           ["customers", [3, 1, 2]]
	 *
	 * @param string $orderby  Orderby value.
	 * @param array  $expected Product IDs in the expected order.
	 */
	public function test_accepts_orderby( string $orderby, array $expected ): void {
		$before = '2026-02-01 10:00:00';
		$during = '2026-03-03 10:00:00';
		// Sign-ups: 1, 3 and 2. Customers: 1, 1 (one email signing up three times) and 2. Notifications sent: 1, 0 and 1.
		$signups_by_product = array(
			1 => 1,
			2 => 3,
			3 => 2,
		);
		foreach ( $signups_by_product as $product_id => $signups ) {
			for ( $i = 0; $i < $signups; $i++ ) {
				$email = 2 === $product_id ? 'repeat@example.com' : "{$product_id}-{$i}@example.com";
				$this->insert_row( $product_id, NotificationStatus::ACTIVE, $during, $email );
			}
		}
		foreach ( array( 1, 3 ) as $product_id ) {
			$this->insert_row( $product_id, NotificationStatus::SENT, $before, "sent-{$product_id}@example.com", $during );
		}

		$response = $this->request( self::ENDPOINT, array_merge( self::PERIOD, array( 'orderby' => $orderby ) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $expected, array_column( $response->get_data(), 'product_id' ) );
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
	 * @testdox Should serve cached data until a notification write that changes report data bumps the cache version.
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

		$version      = ReportsCache::get_version();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'crud@example.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertNotSame( $version, ReportsCache::get_version(), 'Saving a notification should invalidate the reports cache.' );
		$this->assertSame( 3, $this->get_rows( $period )[ $product->get_id() ]['signups'], 'The report should be recomputed after the bump.' );

		$version = ReportsCache::get_version();
		$notification->set_date_last_attempt( time() );
		$notification->save();
		$this->assertSame( $version, ReportsCache::get_version(), 'A send-attempt bookkeeping save should keep the reports cache.' );

		$notification->set_status( NotificationStatus::CANCELLED );
		$notification->save();
		$this->assertNotSame( $version, ReportsCache::get_version(), 'A status change should invalidate the reports cache.' );

		$version = ReportsCache::get_version();
		$notification->delete( true );
		$this->assertNotSame( $version, ReportsCache::get_version(), 'Deleting a notification should invalidate the reports cache.' );
	}

	/**
	 * @testdox Should accept a server-side CSV export of the report.
	 */
	public function test_export_route_accepts_the_report(): void {
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );

		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/export' );
		$request->set_body_params( array( 'report_args' => self::PERIOD ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringContainsString( '/reports/stock-notifications/export/', $response->get_data()['_links']['status'][0]['href'] );
	}

	/**
	 * @testdox Should export the product name, sign-ups, notifications sent and customers columns.
	 */
	public function test_export_columns_and_rows(): void {
		$controller = new Controller();
		$item       = array(
			'product_id'         => 7,
			'signups'            => 3,
			'notifications_sent' => 2,
			'customers'          => 1,
			'extended_info'      => array( 'name' => 'Hoodie' ),
		);

		$this->assertSame(
			array( 'product_name', 'signups', 'notifications_sent', 'customers' ),
			array_keys( $controller->get_export_columns() )
		);
		$this->assertSame(
			array(
				'product_name'       => 'Hoodie',
				'signups'            => 3,
				'notifications_sent' => 2,
				'customers'          => 1,
			),
			$controller->prepare_item_for_export( $item )
		);
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
