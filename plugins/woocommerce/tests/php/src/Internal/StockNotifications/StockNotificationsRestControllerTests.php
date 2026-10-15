<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotificationsRestController;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the wc/v3/stock-notifications REST controller.
 */
class StockNotificationsRestControllerTests extends WC_REST_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	private const ROUTE = '/wc/v3/stock-notifications';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
		( new StockNotificationsRestController() )->register_routes();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * Create and save a notification.
	 *
	 * @param array $props Props to set, plus optional `posted_attributes` meta.
	 * @return Notification
	 */
	private function create_notification( array $props = array() ): Notification {
		$posted_attributes = $props['posted_attributes'] ?? null;
		unset( $props['posted_attributes'] );

		$props['product_id'] ??= \WC_Helper_Product::create_simple_product()->get_id();

		$notification = new Notification();
		$notification->set_props(
			array_merge(
				array(
					'user_email' => 'shopper@example.com',
					'status'     => NotificationStatus::ACTIVE,
				),
				$props
			)
		);
		if ( null !== $posted_attributes ) {
			$notification->add_meta_data( 'posted_attributes', $posted_attributes );
		}
		$notification->save();

		return $notification;
	}

	/**
	 * Dispatch a GET request to the collection route.
	 *
	 * @param array $params Query params.
	 * @return \WP_REST_Response
	 */
	private function get_collection( array $params = array() ) {
		$request = new WP_REST_Request( 'GET', self::ROUTE );
		$request->set_query_params( $params );
		return $this->server->dispatch( $request );
	}

	/**
	 * @testdox The single item route returns every documented field with the expected values.
	 */
	public function test_get_item(): void {
		$product      = \WC_Helper_Product::create_simple_product( true, array( 'sku' => 'BIS-SKU' ) );
		$notification = $this->create_notification(
			array(
				'product_id'          => $product->get_id(),
				'user_id'             => get_current_user_id(),
				'status'              => NotificationStatus::CANCELLED,
				'cancellation_source' => NotificationCancellationSource::ADMIN,
				'date_cancelled'      => '2026-02-03T04:05:06Z',
			)
		);

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE . '/' . $notification->get_id() ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $notification->get_id(), $data['id'] );
		$this->assertSame( 'cancelled', $data['status'] );
		$this->assertSame( $product->get_id(), $data['product_id'] );
		$this->assertSame( 0, $data['parent_id'] );
		$this->assertSame( 0, $data['variation_id'] );
		$this->assertSame( 'BIS-SKU', $data['sku'] );
		$this->assertSame( get_current_user_id(), $data['user_id'] );
		$this->assertSame( 'shopper@example.com', $data['user_email'] );
		$this->assertSame( 'admin', $data['cancellation_source'] );
		$this->assertSame( $product->get_permalink(), $data['product_permalink'] );
		$this->assertEquals( new \stdClass(), $data['posted_attributes'] );
		$this->assertSame( '2026-02-03T04:05:06', $data['date_cancelled_gmt'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $data['date_created'] );
		$this->assertNotNull( $data['date_created_gmt'] );
		foreach ( array( 'date_confirmed', 'date_notified', 'date_last_attempt' ) as $unset_date ) {
			$this->assertNull( $data[ $unset_date ] );
			$this->assertNull( $data[ $unset_date . '_gmt' ] );
		}

		$links = $response->get_links();
		$this->assertStringEndsWith( self::ROUTE . '/' . $notification->get_id(), $links['self'][0]['href'] );
		$this->assertStringEndsWith( self::ROUTE, $links['collection'][0]['href'] );
		$this->assertStringEndsWith( '/wc/v3/products/' . $product->get_id(), $links['up'][0]['href'] );
	}

	/**
	 * @testdox A variation sign-up resolves the parent product, the variation and the posted attributes.
	 */
	public function test_get_item_for_variation(): void {
		$variable     = \WC_Helper_Product::create_variation_product();
		$variation    = wc_get_product( $variable->get_children()[0] );
		$attributes   = array( 'attribute_pa_size' => 'small' );
		$notification = $this->create_notification(
			array(
				'product_id'        => $variation->get_id(),
				'posted_attributes' => $attributes,
			)
		);

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE . '/' . $notification->get_id() ) );
		$data     = $response->get_data();

		$this->assertSame( $variation->get_id(), $data['product_id'] );
		$this->assertSame( $variable->get_id(), $data['parent_id'] );
		$this->assertSame( $variation->get_id(), $data['variation_id'] );
		$this->assertSame( $attributes, (array) $data['posted_attributes'] );
		$this->assertStringEndsWith( sprintf( '/wc/v3/products/%d/variations/%d', $variable->get_id(), $variation->get_id() ), $response->get_links()['up'][0]['href'] );
	}

	/**
	 * @testdox A notification whose product was deleted returns null product fields instead of failing.
	 */
	public function test_get_item_with_deleted_product(): void {
		$product      = \WC_Helper_Product::create_simple_product();
		$product_id   = $product->get_id();
		$notification = $this->create_notification( array( 'product_id' => $product_id ) );
		$product->delete( true );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE . '/' . $notification->get_id() ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $product_id, $data['product_id'] );
		$this->assertNull( $data['parent_id'] );
		$this->assertNull( $data['variation_id'] );
		$this->assertNull( $data['sku'] );
		$this->assertNull( $data['product_permalink'] );
		$this->assertArrayNotHasKey( 'up', $response->get_links() );
	}

	/**
	 * @testdox An unknown id yields a 404 with the invalid id error code.
	 */
	public function test_get_item_unknown_id(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE . '/999999' ) );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_stock_notification_invalid_id', $response->get_data()['code'] );
		$this->assertSame( 404, $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE . '/0' ) )->get_status() );
	}

	/**
	 * @testdox The prepare filter can extend the payload.
	 */
	public function test_prepare_filter(): void {
		$notification = $this->create_notification();
		$filter       = function ( $response, $filtered_notification, $request ) use ( $notification ) {
			$this->assertSame( $notification->get_id(), $filtered_notification->get_id() );
			$this->assertInstanceOf( WP_REST_Request::class, $request );
			$data          = $response->get_data();
			$data['extra'] = 'yes';
			$response->set_data( $data );
			return $response;
		};
		add_filter( 'woocommerce_rest_prepare_stock_notification', $filter, 10, 3 );

		$data = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE . '/' . $notification->get_id() ) )->get_data();
		remove_filter( 'woocommerce_rest_prepare_stock_notification', $filter );

		$this->assertSame( 'yes', $data['extra'] );
	}

	/**
	 * @testdox Collection requests paginate with the standard total headers, newest first.
	 */
	public function test_get_items_pagination(): void {
		$ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$ids[] = $this->create_notification( array( 'date_created' => 1000 + $i ) )->get_id();
		}

		$response = $this->get_collection( array( 'per_page' => 2 ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( '_links', $response->get_data()[0] );
		$this->assertSame( '3', $response->get_headers()['X-WP-Total'] );
		$this->assertSame( '2', $response->get_headers()['X-WP-TotalPages'] );
		$this->assertSame( array( $ids[2], $ids[1] ), array_column( $response->get_data(), 'id' ) );
		$this->assertStringContainsString( 'rel="next"', $response->get_headers()['Link'] );

		$response = $this->get_collection(
			array(
				'per_page' => 2,
				'page'     => 2,
			)
		);
		$this->assertSame( array( $ids[0] ), array_column( $response->get_data(), 'id' ) );
		$this->assertStringContainsString( 'rel="prev"', $response->get_headers()['Link'] );

		$response = $this->get_collection(
			array(
				'offset'  => 1,
				'order'   => 'asc',
				'orderby' => 'id',
			)
		);
		$this->assertSame( array( $ids[1], $ids[2] ), array_column( $response->get_data(), 'id' ) );
		$this->assertSame( '3', $response->get_headers()['X-WP-Total'] );
	}

	/**
	 * @testdox Collection requests cap per_page at 100 and reject unknown statuses.
	 */
	public function test_get_items_invalid_params(): void {
		$this->assertSame( 'rest_invalid_param', $this->get_collection( array( 'per_page' => 101 ) )->get_data()['code'] );
		$this->assertSame( 'rest_invalid_param', $this->get_collection( array( 'status' => 'bogus' ) )->get_data()['code'] );
		$this->assertSame( 'rest_invalid_param', $this->get_collection( array( 'after' => 'not-a-date' ) )->get_data()['code'] );
		$this->assertSame( 'rest_invalid_param', $this->get_collection( array( 'email' => 'not-an-email' ) )->get_data()['code'] );
	}

	/**
	 * @testdox Each collection filter narrows the result set.
	 */
	public function test_get_items_filters(): void {
		$product_a = \WC_Helper_Product::create_simple_product();
		$product_b = \WC_Helper_Product::create_simple_product();
		$user_id   = $this->factory->user->create();

		$active    = $this->create_notification(
			array(
				'product_id'   => $product_a->get_id(),
				'user_id'      => $user_id,
				'date_created' => '2026-01-10T00:00:00Z',
			)
		);
		$sent      = $this->create_notification(
			array(
				'product_id'    => $product_b->get_id(),
				'status'        => NotificationStatus::SENT,
				'user_email'    => 'Other@Example.com',
				'date_created'  => '2026-01-20T00:00:00Z',
				'date_notified' => '2026-01-25T00:00:00Z',
			)
		);
		$cancelled = $this->create_notification(
			array(
				'product_id'   => $product_b->get_id(),
				'status'       => NotificationStatus::CANCELLED,
				'date_created' => '2026-01-30T00:00:00Z',
			)
		);

		$ids = fn( array $params ) => array_column( $this->get_collection( $params )->get_data(), 'id' );

		$this->assertSame( array( $cancelled->get_id(), $sent->get_id(), $active->get_id() ), $ids( array() ) );
		$this->assertSame( array( $sent->get_id() ), $ids( array( 'status' => 'sent' ) ) );
		$this->assertSame( array( $cancelled->get_id(), $sent->get_id() ), $ids( array( 'status' => 'sent,cancelled' ) ) );
		$this->assertSame( array( $active->get_id() ), $ids( array( 'product_id' => $product_a->get_id() ) ) );
		$this->assertSame( array( $cancelled->get_id(), $sent->get_id() ), $ids( array( 'product_id' => $product_b->get_id() ) ) );
		$this->assertSame( array( $active->get_id() ), $ids( array( 'user_id' => $user_id ) ) );
		$this->assertSame( array( $sent->get_id() ), $ids( array( 'email' => 'other@example.com' ) ) );
		$this->assertSame( array( $cancelled->get_id(), $sent->get_id() ), $ids( array( 'after' => '2026-01-15T00:00:00' ) ) );
		$this->assertSame( array( $active->get_id() ), $ids( array( 'before' => '2026-01-15T00:00:00' ) ) );
		$this->assertSame( array( $sent->get_id() ), $ids( array( 'notified_after' => '2026-01-24T00:00:00' ) ) );
		$this->assertSame( array(), $ids( array( 'notified_before' => '2026-01-24T00:00:00' ) ) );
	}

	/**
	 * @testdox The _fields parameter limits the returned fields.
	 */
	public function test_get_items_fields(): void {
		$this->create_notification();

		$data = $this->get_collection( array( '_fields' => 'id,status' ) )->get_data();

		$this->assertSame( array( 'id', 'status' ), array_keys( $data[0] ) );
	}

	/**
	 * @testdox Users without manage_woocommerce are rejected on both routes.
	 */
	public function test_permissions(): void {
		$notification = $this->create_notification();

		wp_set_current_user( 0 );
		$response = $this->get_collection();
		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'customer' ) ) );
		$this->assertSame( 403, $this->get_collection()->get_status() );
		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE . '/' . $notification->get_id() ) )->get_status() );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertSame( 200, $this->get_collection()->get_status() );
	}

	/**
	 * @testdox OPTIONS exposes a read-only schema with every documented field.
	 */
	public function test_schema(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', self::ROUTE ) );
		$schema   = $response->get_data()['schema'];

		$this->assertSame( 'stock_notification', $schema['title'] );
		$expected = array(
			'id',
			'status',
			'product_id',
			'parent_id',
			'variation_id',
			'sku',
			'posted_attributes',
			'user_id',
			'user_email',
			'cancellation_source',
			'product_permalink',
			'date_created',
			'date_created_gmt',
			'date_modified',
			'date_modified_gmt',
			'date_confirmed',
			'date_confirmed_gmt',
			'date_notified',
			'date_notified_gmt',
			'date_last_attempt',
			'date_last_attempt_gmt',
			'date_cancelled',
			'date_cancelled_gmt',
		);
		$this->assertSame( $expected, array_keys( $schema['properties'] ) );
		foreach ( $schema['properties'] as $property ) {
			$this->assertTrue( $property['readonly'] );
			$this->assertSame( array( 'view', 'edit' ), $property['context'] );
		}
		$this->assertSame( NotificationStatus::get_valid_statuses(), $schema['properties']['status']['enum'] );

		$args = $response->get_data()['endpoints'][0]['args'];
		$this->assertSame( 100, $args['per_page']['maximum'] );
		$this->assertSame( NotificationStatus::get_valid_statuses(), $args['status']['items']['enum'] );
	}
}
