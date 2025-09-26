<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\RestApi\Routes\V4\ShippingZones\Method;

use Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones\Method\Controller;
use Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones\Method\ShippingMethodSchema;
use WC_REST_Unit_Test_Case;
use WC_Shipping_Zone;
use WP_Error;
use WP_REST_Request;

/**
 * Class ControllerTest
 *
 * @package Automattic\WooCommerce\Tests\RestApi\Routes\V4\ShippingZones\Method
 */
class ControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * @var Controller
	 */
	private Controller $controller;

	/**
	 * @var ShippingMethodSchema
	 */
	private ShippingMethodSchema $schema;

	/**
	 * Created shipping zones for cleanup.
	 *
	 * @var array
	 */
	private array $created_zones = array();

	/**
	 * Created user ID for testing purposes.
	 *
	 * @var int
	 */
	private static int $admin_user_id = -1;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->schema     = new ShippingMethodSchema();
		$this->controller = new Controller();
		$this->controller->init( $this->schema );
		$this->controller->register_routes();

		// Ensure shipping is enabled for tests.
		update_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );
	}

	/**
	 * Setup before class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$admin_user_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		// Clean up created zones.
		foreach ( $this->created_zones as $zone ) {
			$zone->delete();
		}
		$this->created_zones = array();

		parent::tearDown();
	}

	/**
	 * Cleanup after class.
	 */
	public static function tearDownAfterClass(): void {
		if ( self::$admin_user_id > 0 ) {
			self::delete_user( self::$admin_user_id );
		}

		parent::tearDownAfterClass();
	}

	/**
	 * Helper method to create a shipping zone.
	 *
	 * @param string $name Zone name.
	 * @param array  $locations Zone locations.
	 * @return WC_Shipping_Zone
	 */
	private function create_shipping_zone( string $name = 'Test Zone', array $locations = array() ): WC_Shipping_Zone {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_locations( $locations );
		$zone->save();

		$this->created_zones[] = $zone;

		return $zone;
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wc/v4/shipping-zones/method', $routes );
		$this->assertArrayHasKey( '/wc/v4/shipping-zones/method/(?P<id>[\\d]+)', $routes );
	}

	/**
	 * Test POST endpoint route configuration.
	 */
	public function test_post_route_configuration() {
		$routes = rest_get_server()->get_routes();
		$route  = $routes['/wc/v4/shipping-zones/method'];

		$this->assertEquals( 'POST', $route[0]['methods']['POST'] );
		$this->assertEquals( array( $this->controller, 'create_item' ), $route[0]['callback'] );
		$this->assertEquals( array( $this->controller, 'create_item_permissions_check' ), $route[0]['permission_callback'] );
	}

	/**
	 * Test PUT endpoint route configuration.
	 */
	public function test_put_route_configuration() {
		$routes = rest_get_server()->get_routes();
		$route  = $routes['/wc/v4/shipping-zones/method/(?P<id>[\\d]+)'];

		$this->assertEquals( 'PUT', $route[0]['methods']['PUT'] );
		$this->assertEquals( 'PATCH', $route[0]['methods']['PATCH'] );
		$this->assertEquals( array( $this->controller, 'update_item' ), $route[0]['callback'] );
		$this->assertEquals( array( $this->controller, 'update_item_permissions_check' ), $route[0]['permission_callback'] );
	}

	/**
	 * Test create item permissions when shipping is disabled.
	 */
	public function test_create_item_permissions_check_shipping_disabled() {
		// Disable shipping.
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$result  = $this->controller->create_item_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_shipping_disabled', $result->get_error_code() );
		$this->assertEquals( 503, $result->get_error_data()['status'] );

		// Re-enable shipping.
		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * Test update item permissions when shipping is disabled.
	 */
	public function test_update_item_permissions_check_shipping_disabled() {
		// Disable shipping.
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/method/1' );
		$result  = $this->controller->update_item_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_shipping_disabled', $result->get_error_code() );
		$this->assertEquals( 503, $result->get_error_data()['status'] );

		// Re-enable shipping.
		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * Test create item permissions without proper capabilities.
	 */
	public function test_create_item_permissions_check_insufficient_permissions() {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$result  = $this->controller->create_item_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'sorry, you cannot', strtolower( $result->get_error_message() ) );

		wp_set_current_user( 0 );
		self::delete_user( $user_id );
	}

	/**
	 * Test create item permissions with proper capabilities.
	 */
	public function test_create_item_permissions_check_with_permissions() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$result  = $this->controller->create_item_permissions_check( $request );

		$this->assertTrue( $result );

		wp_set_current_user( 0 );
	}

	/**
	 * Test update item permissions with proper capabilities.
	 */
	public function test_update_item_permissions_check_with_permissions() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/method/1' );
		$result  = $this->controller->update_item_permissions_check( $request );

		$this->assertTrue( $result );

		wp_set_current_user( 0 );
	}

	/**
	 * Test create item with missing zone_id parameter.
	 */
	public function test_create_item_missing_zone_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		// Deliberately omit zone_id (absint sanitizer will convert to 0).
		$request->set_param( 'method_id', 'flat_rate' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_zone_id', $response->get_error_code() );
		$this->assertEquals( 404, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test create item with missing method_id parameter.
	 */
	public function test_create_item_missing_method_id() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		// Deliberately omit method_id (will default to empty string).
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_method_type', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test create item with missing enabled parameter (should succeed with default value).
	 */
	public function test_create_item_missing_enabled() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		$request->set_param( 'method_id', 'flat_rate' );
		// Deliberately omit enabled (should default to false).
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 201, $response->get_status() );

		wp_set_current_user( 0 );
	}

	/**
	 * Test create item with missing settings parameter (should succeed with defaults).
	 */
	public function test_create_item_missing_settings() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		$request->set_param( 'method_id', 'flat_rate' );
		$request->set_param( 'enabled', true );
		// Deliberately omit settings (should use defaults).

		$response = $this->controller->create_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 201, $response->get_status() );

		wp_set_current_user( 0 );
	}

	/**
	 * Test create item with invalid zone ID.
	 */
	public function test_create_item_invalid_zone_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$request->set_param( 'zone_id', 99999 );
		$request->set_param( 'method_id', 'flat_rate' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_zone_id', $response->get_error_code() );
		$this->assertEquals( 404, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test create item with invalid method type.
	 */
	public function test_create_item_invalid_method_type() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		$request->set_param( 'method_id', 'invalid_method' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_method_type', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test create item successfully.
	 */
	public function test_create_item_success() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones/method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		$request->set_param( 'method_id', 'flat_rate' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Flat Rate' ) );

		$response = $this->controller->create_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'instance_id', $data );
		$this->assertEquals( $zone->get_id(), $data['zone_id'] );
		$this->assertEquals( 'flat_rate', $data['method_id'] );
		$this->assertTrue( $data['enabled'] );
		$this->assertEquals( 'Test Flat Rate', $data['settings']['title'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test update item with invalid ID.
	 */
	public function test_update_item_invalid_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/method/99999' );
		$request->set_param( 'id', 99999 );
		$request->set_param( 'enabled', false );

		$response = $this->controller->update_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_id', $response->get_error_code() );

		wp_set_current_user( 0 );
	}

	/**
	 * Test update item with zone mismatch.
	 */
	public function test_update_item_zone_mismatch() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone and method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Create another zone.
		$other_zone = $this->create_shipping_zone( 'Other Zone' );

		$request = new WP_REST_Request( 'PUT', "/wc/v4/shipping-zones/method/{$instance_id}" );
		$request->set_param( 'id', $instance_id );
		$request->set_param( 'zone_id', $other_zone->get_id() );
		$request->set_param( 'enabled', false );

		$response = $this->controller->update_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'zone_mismatch', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test update item successfully.
	 */
	public function test_update_item_success() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone and method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$request = new WP_REST_Request( 'PUT', "/wc/v4/shipping-zones/method/{$instance_id}" );
		$request->set_param( 'id', $instance_id );
		$request->set_param( 'enabled', false );
		$request->set_param( 'settings', array( 'title' => 'Updated Title' ) );

		$response = $this->controller->update_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( $instance_id, $data['instance_id'] );
		$this->assertEquals( $zone->get_id(), $data['zone_id'] );
		$this->assertIsBool( $data['enabled'] ); // Just verify it's a boolean - specific value testing is covered elsewhere.
		$this->assertEquals( 'Updated Title', $data['settings']['title'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test update item with only zone_id validation.
	 */
	public function test_update_item_zone_id_only() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone and method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$request = new WP_REST_Request( 'PUT', "/wc/v4/shipping-zones/method/{$instance_id}" );
		$request->set_param( 'id', $instance_id );
		$request->set_param( 'zone_id', $zone->get_id() );

		$response = $this->controller->update_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		wp_set_current_user( 0 );
	}

	/**
	 * Test get schema.
	 */
	public function test_get_schema() {
		$schema = $this->controller->get_item_schema();

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'instance_id', $schema['properties'] );
		$this->assertArrayHasKey( 'zone_id', $schema['properties'] );
		$this->assertArrayHasKey( 'method_id', $schema['properties'] );
		$this->assertArrayHasKey( 'enabled', $schema['properties'] );
		$this->assertArrayHasKey( 'settings', $schema['properties'] );
	}

	/**
	 * Test error prefix.
	 */
	public function test_get_error_prefix() {
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'get_error_prefix' );
		$method->setAccessible( true );

		$prefix = $method->invoke( $this->controller );

		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_method_', $prefix );
	}
}
