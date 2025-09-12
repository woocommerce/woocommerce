<?php
declare(strict_types=1);

require_once __DIR__ . '/test-abstract-controller-v4.php';

/**
 * Abstract Controller tests for V4 REST API.
 */
class WC_REST_Abstract_Controller_V4_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Test controller instance.
	 *
	 * @var Test_Abstract_Controller_V4
	 */
	private $controller;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->controller = new Test_Abstract_Controller_V4();
		$this->user       = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Test get_hook_prefix method.
	 */
	public function test_get_hook_prefix() {
		$this->controller->set_rest_base( 'orders' );
		$hook_prefix = $this->controller->get_hook_prefix();

		$this->assertEquals( 'woocommerce_rest_api_v4_orders_', $hook_prefix );
	}

	/**
	 * Test get_error_prefix method.
	 */
	public function test_get_error_prefix() {
		$this->controller->set_rest_base( 'products' );
		$error_prefix = $this->controller->get_error_prefix();

		$this->assertEquals( 'woocommerce_rest_api_v4_products_', $error_prefix );
	}

	/**
	 * Test check_permissions with supported object types.
	 */
	public function test_check_permissions_supported_object_types() {
		// Test with admin user.
		wp_set_current_user( $this->user );

		// Test order permissions.
		$this->assertTrue( $this->controller->check_permissions( 'order', 'read' ) );
		$this->assertTrue( $this->controller->check_permissions( 'order', 'edit' ) );
		$this->assertTrue( $this->controller->check_permissions( 'order', 'delete' ) );

		// Test product permissions.
		$this->assertTrue( $this->controller->check_permissions( 'product', 'read' ) );
		$this->assertTrue( $this->controller->check_permissions( 'product', 'edit' ) );

		// Test coupon permissions.
		$this->assertTrue( $this->controller->check_permissions( 'coupon', 'read' ) );
		$this->assertTrue( $this->controller->check_permissions( 'coupon', 'edit' ) );
	}

	/**
	 * Test check_permissions with unsupported object types.
	 */
	public function test_check_permissions_unsupported_object_types() {
		wp_set_current_user( $this->user );

		// Test unsupported object types return false.
		$this->assertFalse( $this->controller->check_permissions( 'unsupported_type', 'read' ) );
		$this->assertFalse( $this->controller->check_permissions( 'invalid_resource', 'edit' ) );
		$this->assertFalse( $this->controller->check_permissions( '', 'read' ) );
	}

	/**
	 * Test check_permissions with different user capabilities.
	 */
	public function test_check_permissions_user_capabilities() {
		// Test with shop manager (limited capabilities).
		$shop_manager = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $shop_manager );

		// Shop manager should have read access but limited edit access.
		$this->assertTrue( $this->controller->check_permissions( 'order', 'read' ) );
		$this->assertTrue( $this->controller->check_permissions( 'product', 'read' ) );

		// Test with customer (no capabilities).
		$customer = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer );

		$this->assertFalse( $this->controller->check_permissions( 'order', 'read' ) );
		$this->assertFalse( $this->controller->check_permissions( 'product', 'read' ) );
		$this->assertFalse( $this->controller->check_permissions( 'order', 'edit' ) );
	}

	/**
	 * Test check_permissions with object ID for object-level permissions.
	 */
	public function test_check_permissions_with_object_id() {
		wp_set_current_user( $this->user );

		// Create test objects.
		$order_id   = $this->factory->post->create( array( 'post_type' => 'shop_order' ) );
		$product_id = $this->factory->post->create( array( 'post_type' => 'product' ) );

		// Test with valid object IDs.
		$this->assertTrue( $this->controller->check_permissions( 'order', 'read', $order_id ) );
		$this->assertTrue( $this->controller->check_permissions( 'order', 'edit', $order_id ) );
		$this->assertTrue( $this->controller->check_permissions( 'product', 'read', $product_id ) );
		$this->assertTrue( $this->controller->check_permissions( 'product', 'edit', $product_id ) );

		// Test with invalid object IDs.
		$this->assertFalse( $this->controller->check_permissions( 'order', 'read', 99999 ) );
		$this->assertFalse( $this->controller->check_permissions( 'product', 'edit', 99999 ) );
	}

	/**
	 * Test check_permissions filter hook.
	 */
	public function test_check_permissions_filter_hook() {
		wp_set_current_user( $this->user );

		// Add filter to override permissions.
		add_filter( 'woocommerce_rest_api_v4_orders_check_permissions', array( $this, 'override_order_permissions' ), 10, 4 );

		// Test that filter is applied.
		$this->assertTrue( $this->controller->check_permissions( 'order', 'read' ) );

		// Remove filter.
		remove_filter( 'woocommerce_rest_api_v4_orders_check_permissions', array( $this, 'override_order_permissions' ) );
	}

	/**
	 * Filter callback to override order permissions.
	 *
	 * @param bool   $permission Current permission.
	 * @param string $context Operation context.
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type.
	 * @return bool Modified permission.
	 */
	public function override_order_permissions( $permission, $context, $object_id, $object_type ) {
		return true; // Always allow for testing.
	}

	/**
	 * Test get_route_error_response method.
	 */
	public function test_get_route_error_response() {
		$error = $this->controller->get_route_error_response( 'test_error', 'Test error message', 400, array( 'test_data' => 'value' ) );

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertEquals( 'test_error', $error->get_error_code() );
		$this->assertEquals( 'Test error message', $error->get_error_message() );
		$this->assertEquals( 400, $error->get_error_data()['status'] );
		$this->assertEquals( 'value', $error->get_error_data()['test_data'] );
	}

	/**
	 * Test get_route_error_response with default parameters.
	 */
	public function test_get_route_error_response_defaults() {
		$error = $this->controller->get_route_error_response( 'test_error', 'Test error message' );

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertEquals( 'test_error', $error->get_error_code() );
		$this->assertEquals( 'Test error message', $error->get_error_message() );
		$this->assertEquals( 400, $error->get_error_data()['status'] );
	}

	/**
	 * Test get_route_error_response with empty error code.
	 */
	public function test_get_route_error_response_empty_error_code() {
		$error = $this->controller->get_route_error_response( '', 'Test error message' );

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertEquals( 'invalid_request', $error->get_error_code() );
	}

	/**
	 * Test get_route_error_response with empty error message.
	 */
	public function test_get_route_error_response_empty_error_message() {
		$error = $this->controller->get_route_error_response( 'test_error', '' );

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertStringContainsString( 'An error occurred while processing your request', $error->get_error_message() );
	}

	/**
	 * Test get_route_error_response_from_object method.
	 */
	public function test_get_route_error_response_from_object() {
		$original_error = new WP_Error( 'original_error', 'Original error message' );
		$error          = $this->controller->get_route_error_response_from_object( $original_error, 422, array( 'test_data' => 'value' ) );

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertEquals( 'original_error', $error->get_error_code() );
		$this->assertEquals( 'Original error message', $error->get_error_message() );
		$this->assertEquals( 422, $error->get_error_data()['status'] );
		$this->assertEquals( 'value', $error->get_error_data()['test_data'] );
	}

	/**
	 * Test get_route_error_response_from_object with invalid object.
	 */
	public function test_get_route_error_response_from_object_invalid() {
		$this->expectException( TypeError::class );
		$this->controller->get_route_error_response_from_object( 'not_a_wp_error', 500 );
	}

	/**
	 * Test get_item_schema method.
	 */
	public function test_get_item_schema() {
		$schema = $this->controller->get_item_schema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'properties', $schema );

		// Check schema structure.
		$this->assertEquals( 'http://json-schema.org/draft-04/schema#', $schema['$schema'] );
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertEquals( 'test_resource', $schema['title'] );

		// Check properties.
		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'name', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'date_created', $schema['properties'] );
	}

	/**
	 * Test schema caching.
	 */
	public function test_get_item_schema_caching() {
		// First call.
		$schema1 = $this->controller->get_item_schema();

		// Second call should return cached result (same array reference).
		$schema2 = $this->controller->get_item_schema();

		$this->assertSame( $schema1, $schema2 );
		$this->assertNotEmpty( $schema1 ); // Should have schema data.
		$this->assertEquals( 'test_resource', $schema1['title'] );
	}
}
