<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\ShippingZoneMethod;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\Controller;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingMethodSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingZoneMethodService;
use WC_REST_Unit_Test_Case;
use WC_Shipping_Zone;
use WP_Error;
use WP_Http;
use WP_REST_Request;

/**
 * Class ControllerTest
 *
 * @package Automattic\WooCommerce\Tests\RestApi\Routes\V4\ShippingZoneMethod
 */
class WC_REST_Shipping_Zone_Method_V4_Controller_Tests extends WC_REST_Unit_Test_Case {

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
		$this->controller->init( $this->schema, new ShippingZoneMethodService() );
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
	 * @testdox Should register routes correctly.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wc/v4/shipping-zone-method', $routes );
		$this->assertArrayHasKey( '/wc/v4/shipping-zone-method/(?P<id>[\\d]+)', $routes );
	}

	/**
	 * @testdox Should configure POST endpoint route correctly.
	 */
	public function test_post_route_configuration() {
		$routes = rest_get_server()->get_routes();
		$route  = $routes['/wc/v4/shipping-zone-method'];

		$this->assertEquals( 'POST', $route[0]['methods']['POST'] );
		$this->assertEquals( array( $this->controller, 'create_item' ), $route[0]['callback'] );
		$this->assertEquals( array( $this->controller, 'check_permissions' ), $route[0]['permission_callback'] );
	}

	/**
	 * @testdox Should configure GET endpoint route correctly.
	 */
	public function test_get_route_configuration() {
		$routes = rest_get_server()->get_routes();
		$route  = $routes['/wc/v4/shipping-zone-method/(?P<id>[\\d]+)'];

		$this->assertEquals( 'GET', $route[0]['methods']['GET'] );
		$this->assertEquals( array( $this->controller, 'get_item' ), $route[0]['callback'] );
		$this->assertEquals( array( $this->controller, 'check_permissions' ), $route[0]['permission_callback'] );
	}

	/**
	 * @testdox Should configure PUT endpoint route correctly.
	 */
	public function test_put_route_configuration() {
		$routes = rest_get_server()->get_routes();
		$route  = $routes['/wc/v4/shipping-zone-method/(?P<id>[\\d]+)'];

		$this->assertEquals( 'PUT', $route[1]['methods']['PUT'] );
		$this->assertEquals( 'PATCH', $route[1]['methods']['PATCH'] );
		$this->assertEquals( array( $this->controller, 'update_item' ), $route[1]['callback'] );
		$this->assertEquals( array( $this->controller, 'check_permissions' ), $route[1]['permission_callback'] );
	}

	/**
	 * @testdox Should return error when shipping is disabled.
	 */
	public function test_check_permissions_shipping_disabled() {
		// Disable shipping.
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$result  = $this->controller->check_permissions( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_shipping_disabled', $result->get_error_code() );
		$this->assertEquals( WP_Http::SERVICE_UNAVAILABLE, $result->get_error_data()['status'] );

		// Re-enable shipping.
		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * @testdox Should return error without proper capabilities.
	 */
	public function test_check_permissions_insufficient_permissions() {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$result  = $this->controller->check_permissions( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'sorry, you cannot', strtolower( $result->get_error_message() ) );

		wp_set_current_user( 0 );
		self::delete_user( $user_id );
	}

	/**
	 * @testdox Should allow access with proper capabilities.
	 */
	public function test_check_permissions_with_permissions() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$result  = $this->controller->check_permissions( $request );

		$this->assertTrue( $result );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should apply woocommerce_rest_check_permissions filter.
	 */
	public function test_check_permissions_applies_filter() {
		wp_set_current_user( self::$admin_user_id );

		// Add filter to deny permissions.
		$filter_callback = function ( $permission, $context, $object_id, $object_type ) {
			if ( 'settings' === $object_type && 'edit' === $context ) {
				return false;
			}
			return $permission;
		};
		add_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10, 4 );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$result  = $this->controller->check_permissions( $request );

		// Should be denied by filter.
		$this->assertInstanceOf( WP_Error::class, $result );

		remove_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10 );
		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should check delete permission for DELETE requests.
	 */
	public function test_check_permissions_delete_context() {
		wp_set_current_user( self::$admin_user_id );

		// Add filter to deny delete permissions but allow edit.
		$filter_callback = function ( $permission, $context, $object_id, $object_type ) {
			if ( 'settings' === $object_type && 'delete' === $context ) {
				return false;
			}
			return $permission;
		};
		add_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10, 4 );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zone-method/1' );
		$result  = $this->controller->check_permissions( $request );

		// Should be denied because delete permission is blocked.
		$this->assertInstanceOf( WP_Error::class, $result );

		remove_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10 );
		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should check read permission for GET requests.
	 */
	public function test_check_permissions_read_context() {
		wp_set_current_user( self::$admin_user_id );

		// Add filter to deny read permissions but allow edit.
		$filter_callback = function ( $permission, $context, $object_id, $object_type ) {
			if ( 'settings' === $object_type && 'read' === $context ) {
				return false;
			}
			return $permission;
		};
		add_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10, 4 );

		$request = new WP_REST_Request( 'GET', '/wc/v4/shipping-zone-method/1' );
		$result  = $this->controller->check_permissions( $request );

		// Should be denied because read permission is blocked.
		$this->assertInstanceOf( WP_Error::class, $result );

		remove_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10 );
		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return error when creating item with missing zone_id parameter.
	 */
	public function test_create_item_missing_zone_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		// Deliberately omit zone_id (absint sanitizer will convert to 0).
		$request->set_param( 'method_id', 'flat_rate' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_zone_id', $response->get_error_code() );
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return error when creating item with missing method_id parameter.
	 */
	public function test_create_item_missing_method_id() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		// Deliberately omit method_id (will default to empty string).
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_method_type', $response->get_error_code() );
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should succeed when creating item with missing enabled parameter using default value.
	 */
	public function test_create_item_missing_enabled() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
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
	 * @testdox Should succeed when creating item with missing settings parameter using defaults.
	 */
	public function test_create_item_missing_settings() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
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
	 * @testdox Should return error when creating item with invalid zone ID.
	 */
	public function test_create_item_invalid_zone_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$request->set_param( 'zone_id', 99999 );
		$request->set_param( 'method_id', 'flat_rate' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_zone_id', $response->get_error_code() );
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return error when creating item with invalid method type.
	 */
	public function test_create_item_invalid_method_type() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		$request->set_param( 'method_id', 'invalid_method' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_method_type', $response->get_error_code() );
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should create item successfully.
	 */
	public function test_create_item_success() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone();

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
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
	 * @testdox Should rollback item creation on validation failure.
	 */
	public function test_create_item_rollback_on_validation_failure() {
		wp_set_current_user( self::$admin_user_id );

		// Register a custom shipping method that will fail validation.
		// Do this BEFORE creating the zone to ensure it's registered when validate_method_type is called.
		$custom_method_class = new class() extends \WC_Shipping_Method {
			/**
			 * Constructor.
			 *
			 * @param int $instance_id Instance ID.
			 */
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'test_failing_method';
				$this->method_title       = 'Test Failing Method';
				$this->method_description = 'A test method that fails validation';
				$this->instance_id        = absint( $instance_id );
				$this->supports           = array( 'shipping-zones', 'instance-settings' );
				parent::__construct( $instance_id );
			}

			/**
			 * Get instance form fields with validation that always fails.
			 *
			 * @return array
			 */
			public function get_instance_form_fields() {
				return array(
					'title' => array(
						'title'             => 'Title',
						'type'              => 'text',
						'default'           => '',
						// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
						'sanitize_callback' => function () {
							// Always throw exception to simulate validation failure.
							throw new \Exception( 'Simulated validation error' );
						},
					),
				);
			}
		};

		// Register the custom method with high priority to ensure it's loaded.
		add_filter(
			'woocommerce_shipping_methods',
			function ( $methods ) use ( $custom_method_class ) {
				$methods['test_failing_method'] = $custom_method_class;
				return $methods;
			},
			1
		);

		// Force WC_Shipping to reload methods by creating a new instance.
		WC()->shipping()->load_shipping_methods();

		$zone = $this->create_shipping_zone();

		// Count shipping methods before the test.
		$methods_before = $zone->get_shipping_methods( false );
		$count_before   = count( $methods_before );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zone-method' );
		$request->set_param( 'zone_id', $zone->get_id() );
		$request->set_param( 'method_id', 'test_failing_method' );
		$request->set_param( 'enabled', true );
		$request->set_param( 'settings', array( 'title' => 'Test Method' ) );

		$response = $this->controller->create_item( $request );

		// Should return an error.
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_setting', $response->get_error_code() );

		// Verify the method instance was deleted (rollback).
		$methods_after = $zone->get_shipping_methods( false );
		$this->assertCount( $count_before, $methods_after, 'Method instance should be deleted on validation failure' );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return error when updating item with invalid ID.
	 */
	public function test_update_item_invalid_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zone-method/99999' );
		$request->set_param( 'id', 99999 );
		$request->set_param( 'enabled', false );

		$response = $this->controller->update_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_id', $response->get_error_code() );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should ignore zone_id when updating since it is readonly.
	 */
	public function test_update_item_ignores_readonly_zone_id() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone and method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Create another zone.
		$other_zone = $this->create_shipping_zone( 'Other Zone' );

		$request = new WP_REST_Request( 'PUT', "/wc/v4/shipping-zone-method/{$instance_id}" );
		$request->set_param( 'id', $instance_id );
		$request->set_param( 'zone_id', $other_zone->get_id() ); // This should be ignored since zone_id is readonly.
		$request->set_param( 'enabled', false );

		$response = $this->controller->update_item( $request );

		// Should succeed and zone_id should be ignored.
		$this->assertNotInstanceOf( WP_Error::class, $response );
		$data = $response->get_data();

		// Method should still belong to original zone, not other_zone.
		$this->assertEquals( $zone->get_id(), $data['zone_id'] );
		$this->assertNotEquals( $other_zone->get_id(), $data['zone_id'] );
		$this->assertFalse( $data['enabled'] ); // Enabled update should have worked.

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should update item successfully.
	 */
	public function test_update_item_success() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone and method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$request = new WP_REST_Request( 'PUT', "/wc/v4/shipping-zone-method/{$instance_id}" );
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
	 * @testdox Should validate zone_id when updating item.
	 */
	public function test_update_item_zone_id_only() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone and method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$request = new WP_REST_Request( 'PUT', "/wc/v4/shipping-zone-method/{$instance_id}" );
		$request->set_param( 'id', $instance_id );
		$request->set_param( 'zone_id', $zone->get_id() );

		$response = $this->controller->update_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return item with valid ID.
	 */
	public function test_get_item_success() {
		wp_set_current_user( self::$admin_user_id );

		$zone        = $this->create_shipping_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$request = new WP_REST_Request( 'GET', '/wc/v4/shipping-zone-method/' . $instance_id );
		$request->set_param( 'id', $instance_id );
		$response = $this->controller->get_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'instance_id', $data );
		$this->assertArrayHasKey( 'method_id', $data );
		$this->assertArrayHasKey( 'enabled', $data );
		$this->assertArrayHasKey( 'settings', $data );
		$this->assertSame( $instance_id, $data['instance_id'] );
		$this->assertSame( 'flat_rate', $data['method_id'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return 404 for invalid ID.
	 */
	public function test_get_item_invalid_id() {
		wp_set_current_user( self::$admin_user_id );

		$invalid_id = 99999;
		$request    = new WP_REST_Request( 'GET', '/wc/v4/shipping-zone-method/' . $invalid_id );
		$request->set_param( 'id', $invalid_id );
		$response = $this->controller->get_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_id', $response->get_error_code() );
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return 404 for deleted method.
	 */
	public function test_get_item_deleted_method() {
		wp_set_current_user( self::$admin_user_id );

		$zone        = $this->create_shipping_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Delete the method.
		$zone->delete_shipping_method( $instance_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/shipping-zone-method/' . $instance_id );
		$request->set_param( 'id', $instance_id );
		$response = $this->controller->get_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_id', $response->get_error_code() );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return item for different shipping method types.
	 */
	public function test_get_item_different_method_types() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone( 'Test Zone' );

		// Test with different method types.
		$method_types = array( 'flat_rate', 'free_shipping', 'local_pickup' );

		foreach ( $method_types as $method_type ) {
			$instance_id = $zone->add_shipping_method( $method_type );

			$request = new WP_REST_Request( 'GET', '/wc/v4/shipping-zone-method/' . $instance_id );
			$request->set_param( 'id', $instance_id );
			$response = $this->controller->get_item( $request );

			$this->assertNotInstanceOf( WP_Error::class, $response, "Failed for method type: {$method_type}" );
			$this->assertEquals( 200, $response->get_status() );

			$data = $response->get_data();
			$this->assertArrayHasKey( 'method_id', $data );
			$this->assertSame( $method_type, $data['method_id'], "Method type mismatch for: {$method_type}" );
		}

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return method from correct zone.
	 */
	public function test_get_item_correct_zone() {
		wp_set_current_user( self::$admin_user_id );

		// Create multiple zones with methods.
		$zone1        = $this->create_shipping_zone( 'Zone 1' );
		$instance1_id = $zone1->add_shipping_method( 'flat_rate' );

		$zone2        = $this->create_shipping_zone( 'Zone 2' );
		$instance2_id = $zone2->add_shipping_method( 'free_shipping' );

		// Test first method.
		$request1 = new WP_REST_Request( 'GET', '/wc/v4/shipping-zone-method/' . $instance1_id );
		$request1->set_param( 'id', $instance1_id );
		$response1 = $this->controller->get_item( $request1 );

		$this->assertNotInstanceOf( WP_Error::class, $response1 );
		$data1 = $response1->get_data();
		$this->assertSame( 'flat_rate', $data1['method_id'] );
		$this->assertSame( $zone1->get_id(), $data1['zone_id'] );

		// Test second method.
		$request2 = new WP_REST_Request( 'GET', '/wc/v4/shipping-zone-method/' . $instance2_id );
		$request2->set_param( 'id', $instance2_id );
		$response2 = $this->controller->get_item( $request2 );

		$this->assertNotInstanceOf( WP_Error::class, $response2 );
		$data2 = $response2->get_data();
		$this->assertSame( 'free_shipping', $data2['method_id'] );
		$this->assertSame( $zone2->get_id(), $data2['zone_id'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return schema correctly.
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
	 * @testdox Should return correct error prefix.
	 */
	public function test_get_error_prefix() {
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'get_error_prefix' );
		$method->setAccessible( true );

		$prefix = $method->invoke( $this->controller );

		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zone_method_', $prefix );
	}

	/**
	 * @testdox Should configure DELETE endpoint route correctly.
	 */
	public function test_delete_route_configuration() {
		$routes = rest_get_server()->get_routes();
		$route  = $routes['/wc/v4/shipping-zone-method/(?P<id>[\\d]+)'];

		$this->assertEquals( 'DELETE', $route[2]['methods']['DELETE'] );
		$this->assertEquals( array( $this->controller, 'delete_item' ), $route[2]['callback'] );
		$this->assertEquals( array( $this->controller, 'check_permissions' ), $route[2]['permission_callback'] );
	}

	/**
	 * @testdox Should return error when deleting item with invalid ID.
	 */
	public function test_delete_item_invalid_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zone-method/99999' );
		$request->set_param( 'id', 99999 );

		$response = $this->controller->delete_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_id', $response->get_error_code() );
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_error_data()['status'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should delete item successfully.
	 */
	public function test_delete_item_success() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone and method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Verify the method exists.
		$methods_before = $zone->get_shipping_methods( false );
		$this->assertNotEmpty( $methods_before );

		$request = new WP_REST_Request( 'DELETE', "/wc/v4/shipping-zone-method/{$instance_id}" );
		$request->set_param( 'id', $instance_id );

		$response = $this->controller->delete_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		// Verify response contains full method object (not just success flag).
		$this->assertArrayHasKey( 'instance_id', $data );
		$this->assertArrayHasKey( 'zone_id', $data );
		$this->assertArrayHasKey( 'method_id', $data );
		$this->assertArrayHasKey( 'enabled', $data );
		$this->assertArrayHasKey( 'settings', $data );
		$this->assertEquals( $instance_id, $data['instance_id'] );
		$this->assertEquals( 'flat_rate', $data['method_id'] );
		$this->assertEquals( $zone->get_id(), $data['zone_id'] );

		// Verify the method was actually deleted.
		$methods_after = $zone->get_shipping_methods( false );
		$this->assertCount( count( $methods_before ) - 1, $methods_after );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should return error when deleting already deleted method.
	 */
	public function test_delete_item_already_deleted() {
		wp_set_current_user( self::$admin_user_id );

		$zone        = $this->create_shipping_zone( 'Test Zone' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Delete the method first.
		$zone->delete_shipping_method( $instance_id );

		// Try to delete again.
		$request = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zone-method/' . $instance_id );
		$request->set_param( 'id', $instance_id );
		$response = $this->controller->delete_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'invalid_id', $response->get_error_code() );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should delete item for different shipping method types.
	 */
	public function test_delete_item_different_method_types() {
		wp_set_current_user( self::$admin_user_id );

		$zone = $this->create_shipping_zone( 'Test Zone' );

		// Test with different method types.
		$method_types = array( 'flat_rate', 'free_shipping', 'local_pickup' );

		foreach ( $method_types as $method_type ) {
			$instance_id = $zone->add_shipping_method( $method_type );

			$request = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zone-method/' . $instance_id );
			$request->set_param( 'id', $instance_id );
			$response = $this->controller->delete_item( $request );

			$this->assertNotInstanceOf( WP_Error::class, $response, "Failed to delete method type: {$method_type}" );
			$this->assertEquals( 200, $response->get_status() );

			$data = $response->get_data();
			$this->assertArrayHasKey( 'method_id', $data, "Response missing method_id for method type: {$method_type}" );
			$this->assertEquals( $method_type, $data['method_id'], "Method type mismatch for: {$method_type}" );
		}

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should fire woocommerce_rest_delete_shipping_zone_method action hook.
	 */
	public function test_delete_item_fires_action_hook() {
		wp_set_current_user( self::$admin_user_id );

		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$hook_fired  = false;
		$hook_method = null;
		$hook_zone   = null;

		// Add hook listener.
		add_action(
			'woocommerce_rest_delete_shipping_zone_method',
			function ( $method, $zone ) use ( &$hook_fired, &$hook_method, &$hook_zone ) {
				$hook_fired  = true;
				$hook_method = $method;
				$hook_zone   = $zone;
			},
			10,
			2
		);

		$request = new WP_REST_Request( 'DELETE', "/wc/v4/shipping-zone-method/{$instance_id}" );
		$request->set_param( 'id', $instance_id );

		$response = $this->controller->delete_item( $request );

		$this->assertTrue( $hook_fired, 'woocommerce_rest_delete_shipping_zone_method action hook was not fired' );
		$this->assertNotNull( $hook_method, 'Hook did not receive method parameter' );
		$this->assertNotNull( $hook_zone, 'Hook did not receive zone parameter' );
		$this->assertEquals( $instance_id, $hook_method->instance_id, 'Hook received wrong method' );
		$this->assertEquals( $zone->get_id(), $hook_zone->get_id(), 'Hook received wrong zone' );

		wp_set_current_user( 0 );
	}
}
