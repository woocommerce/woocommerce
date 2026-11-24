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
	 * Build routes per test: the orderby enum depends on whether the feature's columns are attached.
	 *
	 * @var bool
	 */
	protected static $reuse_rest_server = false;

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
		unset( $GLOBALS['current_screen'] );
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
		$this->insert_notification( $product->get_id(), NotificationStatus::ACTIVE );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data() );
		$this->assertArrayNotHasKey( 'active_signups', $response->get_data()[0] );

		$schema = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', self::ENDPOINT ) )->get_data()['schema']['properties'];
		$this->assertArrayNotHasKey( 'active_signups', $schema );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'orderby', 'active_signups' );
		$this->assertSame( 400, $this->server->dispatch( $request )->get_status(), 'Sorting by active_signups should be rejected.' );
	}

	/**
	 * The CSV export calls `get_items()` outside a REST request, so the counts must load there too.
	 *
	 * @testdox Should export active sign-up counts only when the feature is enabled.
	 */
	public function test_export_includes_active_signups_when_feature_enabled(): void {
		$controller = new Controller();
		$this->assertArrayNotHasKey( 'active_signups', $controller->get_export_columns() );

		$this->enable_stock_notification_columns();
		$ids = $this->create_notification_fixture();
		$this->assertArrayHasKey( 'active_signups', $controller->get_export_columns() );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_default_params( wp_list_pluck( array_filter( $controller->get_collection_params(), fn( $param ) => isset( $param['default'] ) ), 'default' ) );
		$request->set_param( 'include', array_values( $ids ) );

		$actual = array();
		foreach ( $controller->get_items( $request )->get_data() as $item ) {
			$actual[ $item['id'] ] = $controller->prepare_item_for_export( $item )['active_signups'];
		}

		$this->assertSame( 2, $actual[ $ids['simple_a'] ] );
		$this->assertSame( 0, $actual[ $ids['none'] ] );
		$this->assertSame( 3, $actual[ $ids['variation'] ] );
	}

	/**
	 * @testdox Should report active sign-ups per product and variation when the feature is enabled.
	 */
	public function test_notification_columns_report_active_signups(): void {
		$this->enable_stock_notification_columns();
		$ids = $this->create_notification_fixture();

		$schema = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', self::ENDPOINT ) )->get_data()['schema']['properties'];
		$this->assertArrayHasKey( 'active_signups', $schema );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'include', implode( ',', $ids ) );
		$request->set_param( 'orderby', 'id' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$actual = array();
		foreach ( $response->get_data() as $item ) {
			$actual[ $item['id'] ] = $item['active_signups'];
		}

		$this->assertSame(
			array(
				$ids['simple_a']  => 2,
				$ids['simple_b']  => 1,
				$ids['none']      => 0,
				$ids['parent']    => 0,
				$ids['variation'] => 3,
			),
			array_replace( array_fill_keys( $ids, null ), $actual )
		);
	}

	/**
	 * @testdox Should report the stock notification columns for a page holding a single product.
	 */
	public function test_notification_columns_for_single_product_page(): void {
		$this->enable_stock_notification_columns();
		$product_id = $this->create_product( ProductStatus::PUBLISH )->get_id();
		$this->insert_notification( $product_id, NotificationStatus::ACTIVE );
		$this->insert_notification( $product_id, NotificationStatus::ACTIVE );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'include', (string) $product_id );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );
		$this->assertSame( 2, $response->get_data()[0]['active_signups'] );
	}

	/**
	 * @testdox Should not sort other product queries by the stock notification columns.
	 */
	public function test_orderby_clauses_only_apply_to_the_report(): void {
		global $wpdb;

		$this->enable_stock_notification_columns();

		$query = new \WP_Query(
			array(
				'post_type' => 'product',
				'orderby'   => 'active_signups',
				'order'     => 'DESC',
			)
		);

		$this->assertStringNotContainsString( $wpdb->prefix . 'wc_stock_notifications', $query->request );
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
	 * @testdox Should not serve a cached order when sign-ups change between report requests.
	 */
	public function test_orderby_active_signups_is_not_cached(): void {
		$this->enable_stock_notification_columns();
		$ids = $this->create_notification_fixture();

		$this->assertSame( $ids['variation'], $this->get_sorted_ids( $ids, 'active_signups', 'desc' )[0] );

		$this->insert_notification( $ids['simple_b'], NotificationStatus::ACTIVE );
		$this->insert_notification( $ids['simple_b'], NotificationStatus::ACTIVE );
		$this->insert_notification( $ids['simple_b'], NotificationStatus::ACTIVE );

		$this->assertSame( $ids['simple_b'], $this->get_sorted_ids( $ids, 'active_signups', 'desc' )[0] );
	}

	/**
	 * @testdox Should attach the report columns when the feature's services are wired up.
	 */
	public function test_feature_wires_up_report_columns(): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'yes' );
		$this->init_stock_notifications_services();

		$schema = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', self::ENDPOINT ) )->get_data()['schema']['properties'];
		$this->assertArrayHasKey( 'active_signups', $schema );
	}

	/**
	 * Enable the feature and attach the report columns, which the feature wires up once at `init`.
	 */
	private function enable_stock_notification_columns(): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'yes' );
		new StockReportColumns();
	}

	/**
	 * Create three simple products, one without sign-ups, and a variable product with one variation, plus their notifications.
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

		$this->insert_notification( $ids['simple_a'], NotificationStatus::ACTIVE );
		$this->insert_notification( $ids['simple_a'], NotificationStatus::ACTIVE );
		$this->insert_notification( $ids['simple_a'], NotificationStatus::SENT );
		$this->insert_notification( $ids['simple_b'], NotificationStatus::ACTIVE );
		$this->insert_notification( $ids['simple_b'], NotificationStatus::PENDING );
		$this->insert_notification( $ids['none'], NotificationStatus::CANCELLED );
		$this->insert_notification( $ids['variation'], NotificationStatus::ACTIVE );
		$this->insert_notification( $ids['variation'], NotificationStatus::ACTIVE );
		$this->insert_notification( $ids['variation'], NotificationStatus::ACTIVE );

		return $ids;
	}

	/**
	 * Insert a stock notification row directly.
	 *
	 * @param int    $product_id Product or variation ID.
	 * @param string $status     Notification status.
	 */
	private function insert_notification( int $product_id, string $status ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'wc_stock_notifications',
			array(
				'product_id'       => $product_id,
				'user_id'          => 0,
				'user_email'       => wp_generate_password( 8, false ) . '@example.com',
				'status'           => $status,
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
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
