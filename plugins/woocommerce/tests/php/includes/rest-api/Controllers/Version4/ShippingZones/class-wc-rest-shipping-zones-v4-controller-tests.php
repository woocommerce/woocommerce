<?php
/**
 * Shipping Zones V4 Controller tests.
 *
 * @package WooCommerce\Tests\API
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones\Controller as ShippingZonesController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones\ShippingZoneSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones\ShippingZoneService;

/**
 * Shipping Zones V4 Controller tests class.
 */
class WC_REST_Shipping_Zones_V4_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Test endpoint.
	 *
	 * @var ShippingZonesController
	 */
	protected $endpoint;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	protected $user;

	/**
	 * Created shipping zones for cleanup.
	 *
	 * @var array
	 */
	protected $zones = array();

	/**
	 * Enable the REST API v4 feature.
	 */
	public static function enable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features[] = 'rest-api-v4';
				return $features;
			}
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
		$this->endpoint = new ShippingZonesController();
		$this->endpoint->init( new ShippingZoneSchema(), new ShippingZoneService() );
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
		$this->zones = array();
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		// Clean up created zones.
		foreach ( $this->zones as $zone ) {
			if ( $zone instanceof WC_Shipping_Zone && $zone->get_id() > 0 ) {
				$zone->delete();
			}
		}
		$this->zones = array();

		parent::tearDown();
		$this->disable_rest_api_v4_feature();
	}

	/**
	 * Helper method to create a shipping zone.
	 *
	 * @param string $name Zone name.
	 * @param int    $order Zone order.
	 * @param array  $locations Zone locations.
	 * @return WC_Shipping_Zone
	 */
	protected function create_shipping_zone( $name, $order = 0, $locations = array() ) {
		$zone = new WC_Shipping_Zone( null );
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );
		$zone->set_locations( $locations );
		$zone->save();

		$this->zones[] = $zone;
		return $zone;
	}

	/**
	 * Helper method to add a shipping method to a zone.
	 *
	 * @param WC_Shipping_Zone $zone Zone to add method to.
	 * @param string           $method_id Method ID.
	 * @param array            $settings Method settings.
	 * @return int Instance ID.
	 */
	protected function add_shipping_method( $zone, $method_id, $settings = array() ) {
		$instance_id = $zone->add_shipping_method( $method_id );

		if ( ! empty( $settings ) ) {
			$methods = $zone->get_shipping_methods();
			if ( isset( $methods[ $instance_id ] ) ) {
				$method = $methods[ $instance_id ];

				// Update instance settings.
				foreach ( $settings as $key => $value ) {
					$method->instance_settings[ $key ] = $value;
				}

				// Save the settings.
				$option_key = $method->get_instance_option_key();
				update_option( $option_key, $method->instance_settings );

				// Refresh the method.
				$method->init_settings();
			}
		}

		return $instance_id;
	}

	/**
	 * @testdox Should register routes correctly.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/shipping-zones', $routes );
	}

	/**
	 * @testdox Should return all shipping zones.
	 */
	public function test_get_items() {
		// Create test zones.
		$zone1 = $this->create_shipping_zone(
			'Test Zone 1',
			1,
			array(
				array(
					'code' => 'US:CA',
					'type' => 'state',
				),
			)
		);

		$zone2 = $this->create_shipping_zone(
			'Test Zone 2',
			2,
			array(
				array(
					'code' => 'US',
					'type' => 'country',
				),
			)
		);

		// Add shipping methods.
		$this->add_shipping_method( $zone1, 'flat_rate', array( 'cost' => '10.00' ) );
		$this->add_shipping_method( $zone1, 'free_shipping', array( 'min_amount' => '50' ) );
		$this->add_shipping_method( $zone2, 'flat_rate', array( 'cost' => '5.00' ) );

		// Make request.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Assertions.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertGreaterThanOrEqual( 3, count( $data ) ); // Our 2 zones + Rest of World.

		// Find our test zones in the response.
		$zone1_data         = null;
		$zone2_data         = null;
		$rest_of_world_data = null;

		foreach ( $data as $zone_data ) {
			if ( 'Test Zone 1' === $zone_data['name'] ) {
				$zone1_data = $zone_data;
			} elseif ( 'Test Zone 2' === $zone_data['name'] ) {
				$zone2_data = $zone_data;
			} elseif ( 0 === $zone_data['id'] ) {
				$rest_of_world_data = $zone_data;
			}
		}

		// Test zone 1 structure.
		$this->assertNotNull( $zone1_data );
		$this->assertEquals( $zone1->get_id(), $zone1_data['id'] );
		$this->assertEquals( 'Test Zone 1', $zone1_data['name'] );
		$this->assertEquals( 1, $zone1_data['order'] );
		$this->assertIsArray( $zone1_data['locations'] );
		$this->assertCount( 1, $zone1_data['locations'] );
		$this->assertEquals( 'US:CA', $zone1_data['locations'][0]['code'] );
		$this->assertEquals( 'state', $zone1_data['locations'][0]['type'] );
		$this->assertEquals( 'California', $zone1_data['locations'][0]['name'] );
		$this->assertIsArray( $zone1_data['methods'] );
		$this->assertCount( 2, $zone1_data['methods'] );

		// Test zone 2 structure.
		$this->assertNotNull( $zone2_data );
		$this->assertEquals( $zone2->get_id(), $zone2_data['id'] );
		$this->assertEquals( 'Test Zone 2', $zone2_data['name'] );
		$this->assertEquals( 2, $zone2_data['order'] );
		$this->assertIsArray( $zone2_data['locations'] );
		$this->assertCount( 1, $zone2_data['locations'] );
		$this->assertEquals( 'US', $zone2_data['locations'][0]['code'] );
		$this->assertEquals( 'country', $zone2_data['locations'][0]['type'] );
		$this->assertEquals( 'United States (US)', $zone2_data['locations'][0]['name'] );
		$this->assertIsArray( $zone2_data['methods'] );
		$this->assertCount( 1, $zone2_data['methods'] );

		// Test "Rest of the World" zone.
		$this->assertNotNull( $rest_of_world_data );
		$this->assertEquals( 0, $rest_of_world_data['id'] );
		$this->assertIsArray( $rest_of_world_data['locations'] );
	}

	/**
	 * @testdox Should format shipping methods correctly.
	 */
	public function test_method_formatting() {
		$zone = $this->create_shipping_zone( 'Method Test Zone' );

		// Add different types of shipping methods.
		$this->add_shipping_method( $zone, 'flat_rate', array( 'cost' => '15.50' ) );
		$this->add_shipping_method( $zone, 'free_shipping', array( 'min_amount' => '100' ) );
		$this->add_shipping_method( $zone, 'local_pickup', array( 'cost' => '0' ) );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Find our test zone.
		$test_zone_data = null;
		foreach ( $data as $zone_data ) {
			if ( 'Method Test Zone' === $zone_data['name'] ) {
				$test_zone_data = $zone_data;
				break;
			}
		}

		$this->assertNotNull( $test_zone_data );
		$this->assertIsArray( $test_zone_data['methods'] );
		$this->assertCount( 3, $test_zone_data['methods'] );

		$methods = $test_zone_data['methods'];

		// Test method structure.
		foreach ( $methods as $method ) {
			$this->assertArrayHasKey( 'instance_id', $method );
			$this->assertArrayHasKey( 'title', $method );
			$this->assertArrayHasKey( 'enabled', $method );
			$this->assertArrayHasKey( 'method_id', $method );
			$this->assertArrayHasKey( 'settings', $method );
			$this->assertIsInt( $method['instance_id'] );
			$this->assertIsString( $method['title'] );
			$this->assertIsBool( $method['enabled'] );
			$this->assertIsString( $method['method_id'] );
			$this->assertIsArray( $method['settings'] );
		}
	}

	/**
	 * @testdox Should format locations correctly.
	 */
	public function test_location_formatting() {
		$zone = $this->create_shipping_zone(
			'Location Test Zone',
			0,
			array(
				array(
					'code' => 'US:CA',
					'type' => 'state',
				),
				array(
					'code' => 'US:NY',
					'type' => 'state',
				),
				array(
					'code' => 'CA',
					'type' => 'country',
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Find our test zone.
		$test_zone_data = null;
		foreach ( $data as $zone_data ) {
			if ( 'Location Test Zone' === $zone_data['name'] ) {
				$test_zone_data = $zone_data;
				break;
			}
		}

		$this->assertNotNull( $test_zone_data );
		$this->assertIsArray( $test_zone_data['locations'] );
		$this->assertCount( 3, $test_zone_data['locations'] );

		$locations = $test_zone_data['locations'];

		// Check locations are objects with proper structure.
		$location_names = array_column( $locations, 'name' );
		$this->assertContains( 'California', $location_names );
		$this->assertContains( 'New York', $location_names );
		$this->assertContains( 'Canada', $location_names );
	}

	/**
	 * @testdox Should handle empty locations.
	 */
	public function test_empty_locations() {
		$zone = $this->create_shipping_zone( 'Empty Zone' );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Find our test zone.
		$test_zone_data = null;
		foreach ( $data as $zone_data ) {
			if ( 'Empty Zone' === $zone_data['name'] ) {
				$test_zone_data = $zone_data;
				break;
			}
		}

		$this->assertNotNull( $test_zone_data );
		$this->assertIsArray( $test_zone_data['locations'] );
		$this->assertEmpty( $test_zone_data['locations'] );
	}

	/**
	 * @testdox Should return error without permissions.
	 */
	public function test_get_items_without_permission() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * @testdox Should check delete permission for DELETE requests.
	 */
	public function test_check_permissions_delete_context() {
		// Add filter to deny delete permissions but allow edit.
		$filter_callback = function ( $permission, $context, $object_id, $object_type ) {
			if ( 'settings' === $object_type && 'delete' === $context ) {
				return false;
			}
			return $permission;
		};
		add_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10, 4 );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zones/1' );
		$result  = $this->endpoint->check_permissions( $request );

		// Should be denied because delete permission is blocked.
		$this->assertInstanceOf( WP_Error::class, $result );

		remove_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10 );
	}

	/**
	 * @testdox Should check read permission for GET requests.
	 */
	public function test_check_permissions_read_context() {
		// Add filter to deny read permissions but allow edit.
		$filter_callback = function ( $permission, $context, $object_id, $object_type ) {
			if ( 'settings' === $object_type && 'read' === $context ) {
				return false;
			}
			return $permission;
		};
		add_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10, 4 );

		$request = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$result  = $this->endpoint->check_permissions( $request );

		// Should be denied because read permission is blocked.
		$this->assertInstanceOf( WP_Error::class, $result );

		remove_filter( 'woocommerce_rest_check_permissions', $filter_callback, 10 );
	}

	/**
	 * @testdox Should order zones correctly.
	 */
	public function test_zone_ordering() {
		$zone1 = $this->create_shipping_zone( 'Zone Order 3', 3 );
		$zone2 = $this->create_shipping_zone( 'Zone Order 1', 1 );
		$zone3 = $this->create_shipping_zone( 'Zone Order 2', 2 );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Find the order of our test zones.
		$zone_orders = array();
		foreach ( $data as $zone_data ) {
			if ( strpos( $zone_data['name'], 'Zone Order' ) === 0 ) {
				$zone_orders[] = $zone_data['order'];
			}
		}

		// Should be sorted by order.
		$this->assertEquals( array( 1, 2, 3 ), $zone_orders );
	}

	/**
	 * @testdox Should handle non-numeric cost values.
	 */
	public function test_non_numeric_cost_handling() {
		$zone = $this->create_shipping_zone( 'Expression Cost Zone' );

		// Add flat rate with expression-based cost.
		$instance_id = $this->add_shipping_method( $zone, 'flat_rate', array( 'cost' => '10 + [qty] * 2' ) );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Find our test zone.
		$test_zone_data = null;
		foreach ( $data as $zone_data ) {
			if ( 'Expression Cost Zone' === $zone_data['name'] ) {
				$test_zone_data = $zone_data;
				break;
			}
		}

		$this->assertNotNull( $test_zone_data );
		$this->assertIsArray( $test_zone_data['methods'] );
		$this->assertCount( 1, $test_zone_data['methods'] );

		$method = $test_zone_data['methods'][0];
		$this->assertEquals( 'Flat rate', $method['title'] );
		$this->assertEquals( 'flat_rate', $method['method_id'] );
		$this->assertArrayHasKey( 'settings', $method );
		// Should return expression as-is in raw settings.
		$this->assertEquals( '10 + [qty] * 2', $method['settings']['cost'] );
	}

	/**
	 * @testdox Should handle free shipping requirements.
	 */
	public function test_free_shipping_requirements() {
		$zone = $this->create_shipping_zone( 'Free Shipping Test Zone' );

		// Test different free shipping requirements.
		$test_cases = array(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '50',
			),
			array(
				'requires'   => 'coupon',
				'min_amount' => '',
			),
			array(
				'requires'   => 'either',
				'min_amount' => '100',
			),
			array(
				'requires'   => 'both',
				'min_amount' => '75',
			),
			array(
				'requires'   => '',
				'min_amount' => '',
			),
		);

		foreach ( $test_cases as $index => $test_case ) {
			$settings = array( 'requires' => $test_case['requires'] );
			if ( ! empty( $test_case['min_amount'] ) ) {
				$settings['min_amount'] = $test_case['min_amount'];
			}

			$this->add_shipping_method( $zone, 'free_shipping', $settings );
		}

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Find our test zone.
		$test_zone_data = null;
		foreach ( $data as $zone_data ) {
			if ( 'Free Shipping Test Zone' === $zone_data['name'] ) {
				$test_zone_data = $zone_data;
				break;
			}
		}

		$this->assertNotNull( $test_zone_data );
		$this->assertIsArray( $test_zone_data['methods'] );
		$this->assertCount( count( $test_cases ), $test_zone_data['methods'] );

		// Verify each method has the correct raw settings.
		foreach ( $test_cases as $index => $test_case ) {
			$method = $test_zone_data['methods'][ $index ];
			$this->assertEquals( 'Free shipping', $method['title'] );
			$this->assertEquals( 'free_shipping', $method['method_id'] );
			$this->assertArrayHasKey( 'settings', $method );

			$settings = $method['settings'];

			// Check requires setting.
			if ( ! empty( $test_case['requires'] ) ) {
				$this->assertEquals( $test_case['requires'], $settings['requires'] );
			}

			// Check min_amount setting.
			if ( ! empty( $test_case['min_amount'] ) ) {
				$this->assertEquals( $test_case['min_amount'], $settings['min_amount'] );
			}
		}
	}

	/**
	 * @testdox Should handle malformed state location codes.
	 *
	 * Note: This test simulates what would happen if malformed data exists.
	 */
	public function test_malformed_state_location_handling() {
		// Create a mock zone with locations to test the formatting logic.
		$zone = $this->create_shipping_zone( 'State Location Test Zone' );

		// Add a valid state location.
		$zone->add_location( 'US:CA', 'state' );
		$zone->add_location( 'US:NY', 'state' );

		// Test the location formatting directly since we can't easily inject
		// malformed data without triggering core WooCommerce handling.
		$schema = new \Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones\ShippingZoneSchema();

		// Use reflection to test the protected method.
		$reflection = new \ReflectionClass( $schema );
		$method     = $reflection->getMethod( 'get_location_name' );
		$method->setAccessible( true );

		// Test valid state location.
		$valid_location = (object) array(
			'code' => 'US:CA',
			'type' => 'state',
		);
		$result         = $method->invoke( $schema, $valid_location );
		$this->assertEquals( 'California', $result );

		// Test malformed state location (missing state part).
		$malformed_location = (object) array(
			'code' => 'US',
			'type' => 'state',
		);
		$result             = $method->invoke( $schema, $malformed_location );
		$this->assertEquals( 'US', $result ); // Should return raw code as fallback.

		// Test malformed state location (too many parts).
		$malformed_location2 = (object) array(
			'code' => 'US:CA:Extra',
			'type' => 'state',
		);
		$result              = $method->invoke( $schema, $malformed_location2 );
		$this->assertEquals( 'US:CA:Extra', $result ); // Should return raw code as fallback.
	}

	/**
	 * @testdox Should return error when shipping is disabled.
	 */
	public function test_shipping_disabled_response() {
		// Disable shipping temporarily.
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );

		// Check for 503 Service Unavailable status.
		$this->assertEquals( 503, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_disabled', $data['code'] );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'Shipping is disabled.', $data['message'] );

		// Re-enable shipping.
		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * @testdox Should return correct schema.
	 */
	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v4/shipping-zones' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'order', $properties );
		$this->assertArrayHasKey( 'locations', $properties );
		$this->assertArrayHasKey( 'methods', $properties );

		// Test locations schema.
		$this->assertEquals( 'array', $properties['locations']['type'] );
		$this->assertEquals( 'object', $properties['locations']['items']['type'] );
		$this->assertArrayHasKey( 'properties', $properties['locations']['items'] );
		$this->assertArrayHasKey( 'code', $properties['locations']['items']['properties'] );
		$this->assertArrayHasKey( 'type', $properties['locations']['items']['properties'] );
		$this->assertArrayHasKey( 'name', $properties['locations']['items']['properties'] );

		// Test methods schema.
		$this->assertEquals( 'array', $properties['methods']['type'] );
		$this->assertArrayHasKey( 'properties', $properties['methods']['items'] );

		$method_properties = $properties['methods']['items']['properties'];
		$this->assertArrayHasKey( 'instance_id', $method_properties );
		$this->assertArrayHasKey( 'title', $method_properties );
		$this->assertArrayHasKey( 'enabled', $method_properties );
		$this->assertArrayHasKey( 'method_id', $method_properties );
		$this->assertArrayHasKey( 'settings', $method_properties );
	}

	/**
	 * @testdox Should return single zone by ID.
	 */
	public function test_get_item() {
		$zone = $this->create_shipping_zone( 'Single Zone Test' );
		$zone->add_location( 'US', 'country' );
		$zone->add_location( 'GB', 'country' );
		$zone->save();  // Make sure to save the zone with locations.
		$this->add_shipping_method( $zone, 'flat_rate', array( 'cost' => '10.00' ) );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $zone->get_id(), $data['id'] );
		$this->assertEquals( 'Single Zone Test', $data['name'] );
		$this->assertArrayHasKey( 'locations', $data );
		$this->assertArrayHasKey( 'methods', $data );

		// Check that detailed location format is used (should return location objects).
		$this->assertIsArray( $data['locations'] );
		$this->assertCount( 2, $data['locations'] );

		// In detailed view, locations should have name property.
		foreach ( $data['locations'] as $location ) {
			$this->assertIsArray( $location );
			$this->assertArrayHasKey( 'name', $location );
			$this->assertArrayHasKey( 'code', $location );
			$this->assertArrayHasKey( 'type', $location );
		}

		// Check methods.
		$this->assertCount( 1, $data['methods'] );
		$method = $data['methods'][0];
		$this->assertEquals( 'Flat rate', $method['title'] );
		$this->assertEquals( 'flat_rate', $method['method_id'] );
		$this->assertArrayHasKey( 'settings', $method );
		$this->assertEquals( '10.00', $method['settings']['cost'] );
	}

	/**
	 * @testdox Should return error for invalid zone ID.
	 */
	public function test_get_item_invalid_id() {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones/99999' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_invalid_id', $data['code'] );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'Invalid resource ID.', $data['message'] );
	}

	/**
	 * @testdox Should return error when getting zone with shipping disabled.
	 */
	public function test_get_item_shipping_disabled() {
		$zone = $this->create_shipping_zone( 'Test Zone' );

		// Disable shipping temporarily.
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$response = $this->server->dispatch( $request );

		// Check for 503 Service Unavailable status.
		$this->assertEquals( 503, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_disabled', $data['code'] );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'Shipping is disabled.', $data['message'] );

		// Re-enable shipping.
		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * @testdox Should return error when getting zone without permission.
	 */
	public function test_get_item_without_permission() {
		$zone = $this->create_shipping_zone( 'Test Zone' );
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * @testdox Should format Rest of World zone locations correctly.
	 */
	public function test_get_item_rest_of_world_zone() {
		// "Rest of the World" zone has ID 0.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones/0' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $data['id'] );
		$this->assertIsArray( $data['locations'] );
		// "Rest of the World" zone returns empty locations array.
		$this->assertCount( 0, $data['locations'] );
	}

	/**
	 * @testdox Should create zone with minimal fields.
	 */
	public function test_create_item_minimal() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Test Minimal Zone',
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 'Test Minimal Zone', $data['name'] );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
		$this->assertEquals( 0, $data['order'] ); // Default order.
		$this->assertIsArray( $data['locations'] );
		$this->assertCount( 0, $data['locations'] );
		$this->assertIsArray( $data['methods'] );

		// Track for cleanup.
		$this->zones[] = WC_Shipping_Zones::get_zone( $data['id'] );
	}

	/**
	 * @testdox Should create zone with name and locations.
	 */
	public function test_create_item_with_locations() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'US & Canada Zone',
				'locations' => array(
					array(
						'code' => 'US',
						'type' => 'country',
					),
					array(
						'code' => 'CA',
						'type' => 'country',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 'US & Canada Zone', $data['name'] );
		$this->assertIsArray( $data['locations'] );
		$this->assertCount( 2, $data['locations'] );

		// Verify locations were saved.
		$zone            = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->zones[]   = $zone;
		$saved_locations = $zone->get_zone_locations();
		$this->assertCount( 2, $saved_locations );
	}

	/**
	 * @testdox Should create zone with all fields.
	 */
	public function test_create_item_with_all_fields() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Europe Zone',
				'order'     => 5,
				'locations' => array(
					array(
						'code' => 'EU',
						'type' => 'continent',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 'Europe Zone', $data['name'] );
		$this->assertEquals( 5, $data['order'] );
		$this->assertCount( 1, $data['locations'] );

		// Verify the continent location.
		$location = $data['locations'][0];
		$this->assertEquals( 'EU', $location['code'] );
		$this->assertEquals( 'continent', $location['type'] );

		$this->zones[] = WC_Shipping_Zones::get_zone( $data['id'] );
	}

	/**
	 * @testdox Should return error when creating zone without name.
	 */
	public function test_create_item_missing_name() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * @testdox Should create zone without required locations.
	 */
	public function test_create_item_missing_locations() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name' => 'Test Zone',
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * @testdox Should skip invalid location types.
	 */
	public function test_create_item_invalid_location_type() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Test Zone',
				'locations' => array(
					array(
						'code' => 'US',
						'type' => 'invalid_type',
					),
					array(
						'code' => 'CA',
						'type' => 'country',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		// Invalid location type should be skipped, only valid one should be saved.
		$zone            = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->zones[]   = $zone;
		$saved_locations = $zone->get_zone_locations();
		$this->assertCount( 1, $saved_locations );
		$this->assertEquals( 'CA', $saved_locations[0]->code );
		$this->assertEquals( 'country', $saved_locations[0]->type );
	}

	/**
	 * @testdox Should default location type to country.
	 */
	public function test_create_item_location_type_defaults_to_country() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Test Zone',
				'locations' => array(
					array(
						'code' => 'GB',
						// No type specified.
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		// Location type should default to 'country'.
		$zone            = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->zones[]   = $zone;
		$saved_locations = $zone->get_zone_locations();
		$this->assertCount( 1, $saved_locations );
		$this->assertEquals( 'GB', $saved_locations[0]->code );
		$this->assertEquals( 'country', $saved_locations[0]->type );
	}

	/**
	 * @testdox Should return correct response structure on create.
	 */
	public function test_create_item_response_structure() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Response Test Zone',
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Verify 201 Created status.
		$this->assertEquals( 201, $response->get_status() );

		// Verify response structure.
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'order', $data );
		$this->assertArrayHasKey( 'locations', $data );
		$this->assertArrayHasKey( 'methods', $data );

		// Verify types.
		$this->assertIsInt( $data['id'] );
		$this->assertIsString( $data['name'] );
		$this->assertIsInt( $data['order'] );
		$this->assertIsArray( $data['locations'] );
		$this->assertIsArray( $data['methods'] );

		$this->zones[] = WC_Shipping_Zones::get_zone( $data['id'] );
	}

	/**
	 * @testdox Should set Location header on create.
	 */
	public function test_create_item_location_header() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Header Test Zone',
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		// Verify Location header is set.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Location', $headers );

		$expected_location = rest_url( '/wc/v4/shipping-zones/' . $data['id'] );
		$this->assertEquals( $expected_location, $headers['Location'] );

		$this->zones[] = WC_Shipping_Zones::get_zone( $data['id'] );
	}

	/**
	 * @testdox Should return error when creating zone without permission.
	 */
	public function test_create_item_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Unauthorized Zone',
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * @testdox Should return error when creating zone with shipping disabled.
	 */
	public function test_create_item_shipping_disabled() {
		// Disable shipping temporarily.
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Disabled Shipping Zone',
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 503, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_disabled', $data['code'] );

		// Re-enable shipping.
		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * @testdox Should create zone with various location types.
	 */
	public function test_create_item_with_various_location_types() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Multi-Location Zone',
				'locations' => array(
					array(
						'code' => 'US',
						'type' => 'country',
					),
					array(
						'code' => 'US:CA',
						'type' => 'state',
					),
					array(
						'code' => '90210',
						'type' => 'postcode',
					),
					array(
						'code' => 'NA',
						'type' => 'continent',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		$zone            = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->zones[]   = $zone;
		$saved_locations = $zone->get_zone_locations();
		$this->assertCount( 4, $saved_locations );

		// Verify all location types were saved.
		$types = array_map(
			function ( $location ) {
				return $location->type;
			},
			$saved_locations
		);
		$this->assertContains( 'country', $types );
		$this->assertContains( 'state', $types );
		$this->assertContains( 'postcode', $types );
		$this->assertContains( 'continent', $types );
	}

	/**
	 * @testdox Should skip empty location codes.
	 */
	public function test_create_item_empty_location_code() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Test Zone',
				'locations' => array(
					array(
						'code' => '',
						'type' => 'country',
					),
					array(
						'code' => 'US',
						'type' => 'country',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		// Empty location code should be skipped.
		$zone            = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->zones[]   = $zone;
		$saved_locations = $zone->get_zone_locations();
		$this->assertCount( 1, $saved_locations );
		$this->assertEquals( 'US', $saved_locations[0]->code );
	}

	/**
	 * @testdox Should create zone with country:state location type.
	 */
	public function test_create_item_with_country_state_location_type() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Country:State Zone',
				'locations' => array(
					array(
						'code' => 'US:CA',
						'type' => 'country:state',
					),
					array(
						'code' => 'US:NY',
						'type' => 'country:state',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		// Verify the zone was created.
		$zone          = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->zones[] = $zone;

		// Verify locations were saved and normalized to 'state' type.
		$saved_locations = $zone->get_zone_locations();
		$this->assertCount( 2, $saved_locations );

		foreach ( $saved_locations as $location ) {
			// Type should be normalized to 'state' internally.
			$this->assertEquals( 'state', $location->type );
		}

		// Verify codes are correct.
		$codes = array_map(
			function ( $location ) {
				return $location->code;
			},
			$saved_locations
		);
		$this->assertContains( 'US:CA', $codes );
		$this->assertContains( 'US:NY', $codes );
	}

	/**
	 * @testdox Should return error for empty zone name.
	 */
	public function test_create_item_empty_name() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => '',
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_invalid_zone_name', $data['code'] );
		$this->assertEquals( 'Zone name cannot be empty.', $data['message'] );
	}

	/**
	 * @testdox Should return error for whitespace zone name.
	 */
	public function test_create_item_whitespace_name() {
		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => '   ',
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_invalid_zone_name', $data['code'] );
	}

	/**
	 * @testdox Should return error when updating Rest of World zone name.
	 */
	public function test_update_rest_of_world_zone_name() {
		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/0' );
		$request->set_body_params(
			array(
				'name' => 'New Name',
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_cannot_edit_zone', $data['code'] );
		$this->assertEquals( 'Cannot change name of "Rest of the World" zone.', $data['message'] );
	}

	/**
	 * @testdox Should return error when updating Rest of World zone locations.
	 */
	public function test_update_rest_of_world_zone_locations() {
		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/0' );
		$request->set_body_params(
			array(
				'locations' => array(
					array(
						'code' => 'US',
						'type' => 'country',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_cannot_edit_zone', $data['code'] );
		$this->assertEquals( 'Cannot change locations of "Rest of the World" zone.', $data['message'] );
	}

	/**
	 * @testdox Should return error when updating Rest of World zone order.
	 */
	public function test_update_rest_of_world_zone_order() {
		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/0' );
		$request->set_body_params(
			array(
				'order' => 5,
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_cannot_edit_zone', $data['code'] );
		$this->assertEquals( 'Cannot change order of "Rest of the World" zone.', $data['message'] );
	}

	/**
	 * @testdox Should return error when updating zone with empty name.
	 */
	public function test_update_item_empty_name() {
		$zone          = $this->create_shipping_zone( 'Test Zone' );
		$this->zones[] = $zone;

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'name' => '',
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_invalid_zone_name', $data['code'] );
		$this->assertEquals( 'Zone name cannot be empty.', $data['message'] );
	}

	/**
	 * @testdox Should update zone name.
	 */
	public function test_update_item_name() {
		$zone          = $this->create_shipping_zone( 'Original Name' );
		$this->zones[] = $zone;

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'name' => 'Updated Name',
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $zone->get_id(), $data['id'] );
		$this->assertEquals( 'Updated Name', $data['name'] );

		// Verify it was actually saved.
		$zone_reloaded = WC_Shipping_Zones::get_zone( $zone->get_id() );
		$this->assertEquals( 'Updated Name', $zone_reloaded->get_zone_name() );
	}

	/**
	 * @testdox Should update zone order.
	 */
	public function test_update_item_order() {
		$zone          = $this->create_shipping_zone( 'Test Zone', 0 );
		$this->zones[] = $zone;

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'order' => 10,
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['order'] );

		// Verify it was actually saved.
		$zone_reloaded = WC_Shipping_Zones::get_zone( $zone->get_id() );
		$this->assertEquals( 10, $zone_reloaded->get_zone_order() );
	}

	/**
	 * @testdox Should update zone locations.
	 */
	public function test_update_item_locations() {
		$zone          = $this->create_shipping_zone( 'Test Zone' );
		$this->zones[] = $zone;

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'locations' => array(
					array(
						'code' => 'US',
						'type' => 'country',
					),
					array(
						'code' => 'CA',
						'type' => 'country',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $data['locations'] );

		// Verify they were actually saved.
		$zone_reloaded   = WC_Shipping_Zones::get_zone( $zone->get_id() );
		$saved_locations = $zone_reloaded->get_zone_locations();
		$this->assertCount( 2, $saved_locations );
	}

	/**
	 * @testdox Should Update regular zone with all fields successfully.
	 */
	public function test_update_item_all_fields() {
		$zone          = $this->create_shipping_zone( 'Original Name', 0 );
		$this->zones[] = $zone;

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'name'      => 'Updated Name',
				'order'     => 7,
				'locations' => array(
					array(
						'code' => 'GB',
						'type' => 'country',
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Updated Name', $data['name'] );
		$this->assertEquals( 7, $data['order'] );
		$this->assertCount( 1, $data['locations'] );
		$this->assertEquals( 'GB', $data['locations'][0]['code'] );
	}

	/**
	 * @testdox Should Update zone with invalid id.
	 */
	public function test_update_item_invalid_id() {
		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/99999' );
		$request->set_body_params(
			array(
				'name' => 'Test',
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_invalid_zone_id', $data['code'] );
		$this->assertEquals( 'Invalid shipping zone ID.', $data['message'] );
	}

	/**
	 * @testdox Should Update zone without permission.
	 */
	public function test_update_item_without_permission() {
		$zone          = $this->create_shipping_zone( 'Test Zone' );
		$this->zones[] = $zone;

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'name' => 'Updated Name',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * @testdox Should Update zone clears locations with empty array.
	 */
	public function test_update_item_clear_locations() {
		$zone          = $this->create_shipping_zone(
			'Test Zone',
			0,
			array(
				array(
					'code' => 'US',
					'type' => 'country',
				),
			)
		);
		$this->zones[] = $zone;

		// Verify zone has locations initially.
		$this->assertCount( 1, $zone->get_zone_locations() );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'locations' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $data['locations'] );

		// Verify locations were actually cleared.
		$zone_reloaded = WC_Shipping_Zones::get_zone( $zone->get_id() );
		$this->assertCount( 0, $zone_reloaded->get_zone_locations() );
	}

	/**
	 * @testdox Should Delete endpoint route configuration.
	 */
	public function test_delete_route_configuration() {
		$routes = $this->server->get_routes();
		$route  = $routes['/wc/v4/shipping-zones/(?P<id>[\d]+)'];

		// Find the DELETE method in the route configuration.
		$delete_config = null;
		foreach ( $route as $config ) {
			if ( isset( $config['methods']['DELETE'] ) ) {
				$delete_config = $config;
				break;
			}
		}

		$this->assertNotNull( $delete_config, 'DELETE method not found in route configuration' );
		$this->assertEquals( 'DELETE', $delete_config['methods']['DELETE'] );
		$this->assertIsArray( $delete_config['callback'] );
		$this->assertInstanceOf( get_class( $this->endpoint ), $delete_config['callback'][0] );
		$this->assertEquals( 'delete_item', $delete_config['callback'][1] );
		$this->assertIsArray( $delete_config['permission_callback'] );
		$this->assertInstanceOf( get_class( $this->endpoint ), $delete_config['permission_callback'][0] );
		$this->assertEquals( 'check_permissions', $delete_config['permission_callback'][1] );
	}

	/**
	 * @testdox Should return error when deleting zone with invalid ID.
	 */
	public function test_delete_item_invalid_id() {
		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zones/99999' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_invalid_zone_id', $data['code'] );
		$this->assertEquals( 'Invalid shipping zone ID.', $data['message'] );
	}

	/**
	 * @testdox Should delete zone successfully.
	 */
	public function test_delete_item_success() {
		$zone = $this->create_shipping_zone( 'Zone to Delete', 1 );
		$zone->add_location( 'US', 'country' );
		$zone->save();

		$zone_id = $zone->get_id();

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zones/' . $zone_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// Verify response contains full zone object (not just success flag).
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'order', $data );
		$this->assertArrayHasKey( 'locations', $data );
		$this->assertArrayHasKey( 'methods', $data );
		$this->assertEquals( $zone_id, $data['id'] );
		$this->assertEquals( 'Zone to Delete', $data['name'] );
		$this->assertEquals( 1, $data['order'] );

		// Verify the zone was actually deleted.
		$zone_after = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );
		$this->assertFalse( $zone_after, 'Zone should be deleted' );

		// Remove from cleanup array since it's already deleted.
		$this->zones = array_filter(
			$this->zones,
			function ( $z ) use ( $zone_id ) {
				return $z->get_id() !== $zone_id;
			}
		);
	}

	/**
	 * @testdox Should Delete zone for already deleted zone.
	 */
	public function test_delete_item_already_deleted() {
		$zone    = $this->create_shipping_zone( 'Zone to Delete' );
		$zone_id = $zone->get_id();

		// Delete the zone first.
		$zone->delete();

		// Remove from cleanup array.
		$this->zones = array_filter(
			$this->zones,
			function ( $z ) use ( $zone_id ) {
				return $z->get_id() !== $zone_id;
			}
		);

		// Try to delete again.
		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zones/' . $zone_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_invalid_zone_id', $data['code'] );
	}

	/**
	 * @testdox Should return error when deleting zone without permission.
	 */
	public function test_delete_item_without_permission() {
		$zone = $this->create_shipping_zone( 'Test Zone' );

		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * @testdox Should Delete zone when shipping is disabled.
	 */
	public function test_delete_item_shipping_disabled() {
		$zone = $this->create_shipping_zone( 'Test Zone' );

		// Disable shipping temporarily.
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zones/' . $zone->get_id() );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 503, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_api_v4_shipping_zones_disabled', $data['code'] );

		// Re-enable shipping.
		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * @testdox Should Delete zone with methods attached.
	 */
	public function test_delete_item_with_methods() {
		$zone = $this->create_shipping_zone( 'Zone with Methods' );
		$this->add_shipping_method( $zone, 'flat_rate' );
		$this->add_shipping_method( $zone, 'free_shipping' );

		$zone_id  = $zone->get_id();
		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/shipping-zones/' . $zone_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'methods', $data );
		$this->assertCount( 2, $data['methods'], 'Response should include the methods that were deleted with the zone' );

		// Verify the zone was actually deleted.
		$zone_after = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );
		$this->assertFalse( $zone_after, 'Zone should be deleted' );

		// Remove from cleanup array.
		$this->zones = array_filter(
			$this->zones,
			function ( $z ) use ( $zone_id ) {
				return $z->get_id() !== $zone_id;
			}
		);
	}
}
