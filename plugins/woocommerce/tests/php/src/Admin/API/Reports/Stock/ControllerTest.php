<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Stock;

use Automattic\WooCommerce\Admin\API\Reports\Stock\Controller;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use Automattic\WooCommerce\Internal\StockNotifications\StockReportColumns;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Stock report API controller test.
 */
class ControllerTest extends WC_REST_Unit_Test_Case {
	use StockNotificationsFeatureTrait;

	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-analytics/reports/stock';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->stock_notifications_original_feature_option = get_option( StockNotifications::ENABLE_OPTION_NAME, false );
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'no' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['current_screen'], $GLOBALS['wp_rest_additional_fields']['report_stock'] );
		$this->restore_stock_notifications_feature_option();

		parent::tearDown();
	}

	/**
	 * The CSV export counts the rows once when it queues its batches (a REST request) and again
	 * while each batch runs (an admin-ajax request). A report that returns more products in an
	 * admin context leaves the export short of 100%, so its download email is never sent.
	 *
	 * @testdox Should list only published and private products, in an admin context and outside one.
	 */
	public function test_report_lists_only_published_and_private_products_in_any_context(): void {
		$statuses = array(
			ProductStatus::PUBLISH,
			ProductStatus::PRIVATE,
			ProductStatus::DRAFT,
			ProductStatus::PENDING,
			ProductStatus::FUTURE,
		);

		$ids = array();
		foreach ( $statuses as $status ) {
			$ids[ $status ] = $this->create_product( $status )->get_id();
		}

		$listed = array( $ids[ ProductStatus::PUBLISH ], $ids[ ProductStatus::PRIVATE ] );
		sort( $listed );

		$expected = array(
			'ids'   => $listed,
			'total' => count( $listed ),
		);

		$this->assertFalse( is_admin(), 'The first report must be requested outside an admin context.' );
		$this->assertSame( $expected, $this->get_report( $ids ), 'The stock report lists the wrong products outside an admin context.' );

		set_current_screen( 'edit-post' );

		$this->assertTrue( is_admin(), 'The second report must be requested in an admin context.' );
		$this->assertSame( $expected, $this->get_report( $ids ), 'The stock report lists the wrong products in an admin context.' );
	}

	/**
	 * @testdox Should leave out the stock notification columns and sorts when the feature is disabled.
	 */
	public function test_notification_columns_absent_when_feature_disabled(): void {
		$product = $this->create_product( ProductStatus::PUBLISH );
		$this->insert_notification( $product->get_id(), NotificationStatus::ACTIVE, 3 );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data() );
		$this->assertArrayNotHasKey( 'active_signups', $response->get_data()[0] );
		$this->assertArrayNotHasKey( 'days_waiting', $response->get_data()[0] );

		$schema = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', self::ENDPOINT ) )->get_data()['schema']['properties'];
		$this->assertArrayNotHasKey( 'active_signups', $schema );
		$this->assertArrayNotHasKey( 'days_waiting', $schema );

		foreach ( array( 'active_signups', 'days_waiting' ) as $orderby ) {
			$request = new WP_REST_Request( 'GET', self::ENDPOINT );
			$request->set_param( 'orderby', $orderby );
			$this->assertSame( 400, $this->server->dispatch( $request )->get_status(), "Sorting by $orderby should be rejected." );
		}
	}

	/**
	 * @testdox Should export the stock notification columns only when the feature is enabled.
	 */
	public function test_export_columns_follow_feature_flag(): void {
		$controller = new Controller();
		$item       = array(
			'name'           => 'Test product',
			'sku'            => 'test-sku',
			'stock_status'   => 'outofstock',
			'stock_quantity' => 0,
			'active_signups' => 2,
			'days_waiting'   => 5,
		);

		$this->assertArrayNotHasKey( 'active_signups', $controller->get_export_columns() );
		$this->assertArrayNotHasKey( 'days_waiting', $controller->get_export_columns() );
		$this->assertArrayNotHasKey( 'active_signups', $controller->prepare_item_for_export( $item ) );
		$this->assertArrayNotHasKey( 'days_waiting', $controller->prepare_item_for_export( $item ) );

		$this->enable_stock_notification_columns();

		$this->assertArrayHasKey( 'active_signups', $controller->get_export_columns() );
		$this->assertArrayHasKey( 'days_waiting', $controller->get_export_columns() );

		$export_item = $controller->prepare_item_for_export( $item );
		$this->assertSame( 2, $export_item['active_signups'] );
		$this->assertSame( 5, $export_item['days_waiting'] );

		$item['active_signups'] = 0;
		$item['days_waiting']   = null;
		$export_item            = $controller->prepare_item_for_export( $item );
		$this->assertSame( 0, $export_item['active_signups'] );
		$this->assertSame( '', $export_item['days_waiting'], 'A product without active sign-ups should export no days waiting.' );
	}

	/**
	 * @testdox Should report active sign-ups and days waiting per product and variation when the feature is enabled.
	 */
	public function test_notification_columns_report_active_signups(): void {
		$this->enable_stock_notification_columns();
		$ids = $this->create_notification_fixture();

		$schema = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', self::ENDPOINT ) )->get_data()['schema']['properties'];
		$this->assertArrayHasKey( 'active_signups', $schema );
		$this->assertArrayHasKey( 'days_waiting', $schema );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'include', implode( ',', $ids ) );
		$request->set_param( 'orderby', 'id' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$actual = array();
		foreach ( $response->get_data() as $item ) {
			$actual[ $item['id'] ] = array( $item['active_signups'], $item['days_waiting'] );
		}

		$this->assertSame(
			array(
				$ids['simple_a']  => array( 2, 5 ),
				$ids['simple_b']  => array( 1, 2 ),
				$ids['none']      => array( 0, null ),
				$ids['parent']    => array( 0, null ),
				$ids['variation'] => array( 3, 9 ),
			),
			array_replace( array_fill_keys( $ids, null ), $actual )
		);
	}

	/**
	 * @testdox Should sort by active sign-up count in both orders.
	 */
	public function test_orderby_active_signups(): void {
		$this->enable_stock_notification_columns();
		$ids = $this->create_notification_fixture();

		$this->assertSame(
			array( $ids['variation'], $ids['simple_a'], $ids['simple_b'] ),
			array_slice( $this->get_sorted_ids( $ids, 'active_signups', 'desc' ), 0, 3 )
		);
		$this->assertSame(
			array( $ids['simple_b'], $ids['simple_a'], $ids['variation'] ),
			array_slice( $this->get_sorted_ids( $ids, 'active_signups', 'asc' ), 2 )
		);
	}

	/**
	 * @testdox Should sort by days waiting in both orders, with products without sign-ups last.
	 */
	public function test_orderby_days_waiting(): void {
		$this->enable_stock_notification_columns();
		$ids     = $this->create_notification_fixture();
		$without = array( $ids['none'], $ids['parent'] );

		$desc = $this->get_sorted_ids( $ids, 'days_waiting', 'desc' );
		$this->assertSame( array( $ids['variation'], $ids['simple_a'], $ids['simple_b'] ), array_slice( $desc, 0, 3 ) );
		$this->assertEqualsCanonicalizing( $without, array_slice( $desc, 3 ) );

		$asc = $this->get_sorted_ids( $ids, 'days_waiting', 'asc' );
		$this->assertSame( array( $ids['simple_b'], $ids['simple_a'], $ids['variation'] ), array_slice( $asc, 0, 3 ) );
		$this->assertEqualsCanonicalizing( $without, array_slice( $asc, 3 ) );
	}

	/**
	 * @testdox Should attach the report columns when the feature's services are wired up.
	 */
	public function test_feature_wires_up_report_columns(): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'yes' );
		$this->init_stock_notifications_services();

		$schema = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', self::ENDPOINT ) )->get_data()['schema']['properties'];
		$this->assertArrayHasKey( 'active_signups', $schema );
		$this->assertArrayHasKey( 'days_waiting', $schema );
	}

	/**
	 * Enable the feature and attach the report columns, which the feature wires up once at `init`.
	 */
	private function enable_stock_notification_columns(): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'yes' );
		new StockReportColumns();
	}

	/**
	 * Create two simple products, one without sign-ups, and a variable product with one variation, plus their notifications.
	 *
	 * @return array<string, int> Product IDs keyed by role.
	 */
	private function create_notification_fixture(): array {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Test variable product' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_regular_price( 5 );
		$variation->save();

		$ids = array(
			'simple_a'  => $this->create_product( ProductStatus::PUBLISH )->get_id(),
			'simple_b'  => $this->create_product( ProductStatus::PUBLISH )->get_id(),
			'none'      => $this->create_product( ProductStatus::PUBLISH )->get_id(),
			'parent'    => $parent->get_id(),
			'variation' => $variation->get_id(),
		);

		$this->insert_notification( $ids['simple_a'], NotificationStatus::ACTIVE, 5 );
		$this->insert_notification( $ids['simple_a'], NotificationStatus::ACTIVE, 1 );
		$this->insert_notification( $ids['simple_a'], NotificationStatus::SENT, 30 );
		$this->insert_notification( $ids['simple_b'], NotificationStatus::ACTIVE, 2 );
		$this->insert_notification( $ids['simple_b'], NotificationStatus::PENDING, 40 );
		$this->insert_notification( $ids['none'], NotificationStatus::CANCELLED, 10 );
		$this->insert_notification( $ids['variation'], NotificationStatus::ACTIVE, 9 );
		$this->insert_notification( $ids['variation'], NotificationStatus::ACTIVE, 4 );
		$this->insert_notification( $ids['variation'], NotificationStatus::ACTIVE, 0 );

		return $ids;
	}

	/**
	 * Insert a stock notification row directly.
	 *
	 * @param int    $product_id Product or variation ID.
	 * @param string $status     Notification status.
	 * @param int    $days_ago   How many whole days ago it was created, plus a minute so the elapsed day count is stable.
	 */
	private function insert_notification( int $product_id, string $status, int $days_ago ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'wc_stock_notifications',
			array(
				'product_id'       => $product_id,
				'user_id'          => 0,
				'user_email'       => wp_generate_password( 8, false ) . '@example.com',
				'status'           => $status,
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS - MINUTE_IN_SECONDS ),
			)
		);
	}

	/**
	 * Request the stock report for the given products, sorted.
	 *
	 * @param int[]  $ids     Product IDs to report on.
	 * @param string $orderby Sort column.
	 * @param string $order   Sort direction.
	 * @return int[] Reported product IDs in order.
	 */
	private function get_sorted_ids( array $ids, string $orderby, string $order ): array {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'include', implode( ',', $ids ) );
		$request->set_param( 'orderby', $orderby );
		$request->set_param( 'order', $order );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		return wp_list_pluck( $response->get_data(), 'id' );
	}

	/**
	 * Create a product with the given status.
	 *
	 * @param string $status Product status.
	 * @return WC_Product_Simple
	 */
	private function create_product( string $status ): WC_Product_Simple {
		$product = new WC_Product_Simple();
		$product->set_name( "Test $status product" );
		$product->set_regular_price( 5 );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );

		if ( ProductStatus::FUTURE === $status ) {
			$product->set_date_created( time() + WEEK_IN_SECONDS );
		}

		$product->set_status( $status );
		$product->save();

		return $product;
	}

	/**
	 * Request the stock report for the given products.
	 *
	 * @param int[] $ids Product IDs to report on.
	 * @return array Reported product IDs, plus the total the CSV export batches on.
	 */
	private function get_report( array $ids ): array {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'include', implode( ',', $ids ) );
		$request->set_param( 'orderby', 'id' );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$headers = $response->get_headers();

		return array(
			'ids'   => wp_list_pluck( $response->get_data(), 'id' ),
			'total' => (int) $headers['X-WP-Total'],
		);
	}
}
