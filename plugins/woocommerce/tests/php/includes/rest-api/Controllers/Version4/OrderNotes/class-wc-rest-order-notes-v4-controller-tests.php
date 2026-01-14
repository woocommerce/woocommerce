<?php
declare( strict_types=1 );

use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes\Controller as OrderNotesController;

/**
 * class Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes\Controller tests.
 * Order Notes Controller tests for V4 REST API.
 */
class WC_REST_Order_Notes_V4_Controller_Tests extends WC_REST_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Endpoint instance.
	 *
	 * @var OrderNotesController
	 */
	private $endpoint;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user;

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_rest_api_v4_feature();
	}

	/**
	 * Enable the REST API v4 feature.
	 */
	public static function enable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features[] = 'rest-api-v4';
				return $features;
			},
		);
	}

	/**
	 * Disable the REST API v4 feature.
	 */
	public static function disable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features = array_diff( $features, array( 'rest-api-v4' ) );
				return $features;
			}
		);
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		$this->enable_rest_api_v4_feature();
		parent::setUp();

		// Create schema instance with dependency injection.
		$order_note_schema = new \Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes\Schema\OrderNoteSchema();

		// Create utils instance.
		$collection_query = new \Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes\CollectionQuery();

		$this->endpoint = new OrderNotesController();
		$this->endpoint->init( $order_note_schema, $collection_query );

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/order-notes', $routes );
		$this->assertArrayHasKey( '/wc/v4/order-notes/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test getting all order notes.
	 */
	public function test_get_items() {
		// Create an order.
		$order = OrderHelper::create_order( $this->user );

		// Add some order notes.
		$order->add_order_note( 'Test note 1', false, false );
		$order->add_order_note( 'Test note 2', true, false );
		$order->add_order_note( 'Test note 3', false, false );

		$request = new WP_REST_Request( 'GET', '/wc/v4/order-notes' );
		$request->set_query_params( array( 'order_id' => $order->get_id() ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $notes );
		$this->assertGreaterThanOrEqual( 3, count( $notes ) );
	}

	/**
	 * Test getting order notes with order filter.
	 */
	public function test_get_items_with_order_filter() {
		// Create two orders.
		$order1 = OrderHelper::create_order( $this->user );
		$order2 = OrderHelper::create_order( $this->user );

		// Add notes to both orders.
		$order1->add_order_note( 'Order 1 note', false, false );
		$order2->add_order_note( 'Order 2 note', false, false );

		$request = new WP_REST_Request( 'GET', '/wc/v4/order-notes' );
		$request->set_query_params( array( 'order_id' => $order1->get_id() ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $notes );
		$this->assertGreaterThanOrEqual( 1, count( $notes ) );

		// All notes should belong to order 1.
		foreach ( $notes as $note ) {
			$this->assertEquals( $order1->get_id(), $note['order_id'] );
		}
	}

	/**
	 * Test getting order notes with type filter.
	 */
	public function test_get_items_with_type_filter() {
		$order = OrderHelper::create_order( $this->user );

		// Add different types of notes.
		$order->add_order_note( 'Internal note', false, false );
		$order->add_order_note( 'Customer note', true, false );

		// Test internal notes filter.
		$request = new WP_REST_Request( 'GET', '/wc/v4/order-notes' );
		$request->set_query_params(
			array(
				'order_id'  => $order->get_id(),
				'note_type' => 'private',
			)
		);
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $notes );

		// All notes should be internal.
		foreach ( $notes as $note ) {
			$this->assertFalse( $note['is_customer_note'] );
		}

		// Test customer notes filter.
		$request = new WP_REST_Request( 'GET', '/wc/v4/order-notes' );
		$request->set_query_params(
			array(
				'order_id'  => $order->get_id(),
				'note_type' => 'customer',
			)
		);
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $notes );

		// All notes should be customer notes.
		foreach ( $notes as $note ) {
			$this->assertTrue( $note['is_customer_note'] );
		}
	}

	/**
	 * Test creating an order note.
	 */
	public function test_create_item() {
		$order = OrderHelper::create_order( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v4/order-notes' );
		$request->set_body_params(
			array(
				'order_id' => $order->get_id(),
				'note'     => 'Test order note',
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'Test order note', $data['note'] );
		$this->assertFalse( $data['is_customer_note'] );
		$this->assertEquals( $order->get_id(), $data['order_id'] );
	}

	/**
	 * Test creating a customer order note.
	 */
	public function test_create_is_customer_note() {
		$order = OrderHelper::create_order( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v4/order-notes' );
		$request->set_body_params(
			array(
				'order_id'         => $order->get_id(),
				'note'             => 'Customer order note',
				'is_customer_note' => true,
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'Customer order note', $data['note'] );
		$this->assertTrue( $data['is_customer_note'] );
		$this->assertEquals( $order->get_id(), $data['order_id'] );
	}

	/**
	 * Test creating order note with invalid order ID.
	 */
	public function test_create_item_invalid_order() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/order-notes' );
		$request->set_body_params(
			array(
				'order_id' => 99999,
				'note'     => 'Test order note',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating order note without required fields.
	 */
	public function test_create_item_missing_fields() {
		$order = OrderHelper::create_order( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v4/order-notes' );
		$request->set_body_params(
			array(
				'order_id' => $order->get_id(),
				// Missing 'note' field.
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test getting a single order note.
	 */
	public function test_get_item() {
		$order   = OrderHelper::create_order( $this->user );
		$note_id = $order->add_order_note( 'Test single note', false, false );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/order-notes/' . $note_id ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $note_id, $data['id'] );
		$this->assertEquals( 'Test single note', $data['note'] );
		$this->assertFalse( $data['is_customer_note'] );
		$this->assertEquals( $order->get_id(), $data['order_id'] );
	}

	/**
	 * Test getting non-existent order note.
	 */
	public function test_get_item_not_found() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/order-notes/99999' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test deleting an order note.
	 */
	public function test_delete_item() {
		$order   = OrderHelper::create_order( $this->user );
		$note_id = $order->add_order_note( 'Note to delete', false, false );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/order-notes/' . $note_id );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify note is deleted.
		$get_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/order-notes/' . $note_id ) );
		$this->assertEquals( 404, $get_response->get_status() );
	}

	/**
	 * Test deleting non-existent order note.
	 */
	public function test_delete_item_not_found() {
		$request = new WP_REST_Request( 'DELETE', '/wc/v4/order-notes/99999' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test order notes schema.
	 */
	public function test_get_item_schema() {
		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v4/order-notes' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'schema', $data );
		$this->assertArrayHasKey( 'properties', $data['schema'] );

		$properties = $data['schema']['properties'];
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'order_id', $properties );
		$this->assertArrayHasKey( 'note', $properties );
		$this->assertArrayHasKey( 'is_customer_note', $properties );
		$this->assertArrayHasKey( 'author', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
	}

	/**
	 * Test order notes without permission.
	 */
	public function test_get_items_without_permission() {
		wp_set_current_user( 0 );
		$order = OrderHelper::create_order( $this->user );

		$request = new WP_REST_Request( 'GET', '/wc/v4/order-notes' );
		$request->set_query_params( array( 'order_id' => $order->get_id() ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test creating order note without permission.
	 */
	public function test_create_item_without_permission() {
		wp_set_current_user( 0 );
		$order = OrderHelper::create_order( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v4/order-notes' );
		$request->set_body_params(
			array(
				'order_id' => $order->get_id(),
				'note'     => 'Test order note',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting order note without permission.
	 */
	public function test_delete_item_without_permission() {
		wp_set_current_user( 0 );
		$order   = OrderHelper::create_order( $this->user );
		$note_id = $order->add_order_note( 'Note to delete', false, false );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/order-notes/' . $note_id );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}
}
