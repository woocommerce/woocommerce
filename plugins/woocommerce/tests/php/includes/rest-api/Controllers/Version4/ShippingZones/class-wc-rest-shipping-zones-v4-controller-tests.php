<?php
/**
 * Shipping Zones V4 Controller tests.
 *
 * @package WooCommerce\Tests\API
 */

use Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones\Controller as ShippingZonesController;

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
		$this->user     = $this->factory->user->create(
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
		$methods     = $zone->get_shipping_methods();

		if ( isset( $methods[ $instance_id ] ) && ! empty( $settings ) ) {
			$methods[ $instance_id ]->set_settings( $settings );
		}

		return $instance_id;
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/shipping-zones', $routes );
	}

	/**
	 * Test getting all shipping zones.
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
		$this->assertContains( 'California', $zone1_data['locations'] );
		$this->assertIsArray( $zone1_data['methods'] );
		$this->assertCount( 2, $zone1_data['methods'] );

		// Test zone 2 structure.
		$this->assertNotNull( $zone2_data );
		$this->assertEquals( $zone2->get_id(), $zone2_data['id'] );
		$this->assertEquals( 'Test Zone 2', $zone2_data['name'] );
		$this->assertEquals( 2, $zone2_data['order'] );
		$this->assertIsArray( $zone2_data['locations'] );
		$this->assertContains( 'United States', $zone2_data['locations'] );
		$this->assertIsArray( $zone2_data['methods'] );
		$this->assertCount( 1, $zone2_data['methods'] );

		// Test "Rest of the World" zone.
		$this->assertNotNull( $rest_of_world_data );
		$this->assertEquals( 0, $rest_of_world_data['id'] );
		$this->assertIsArray( $rest_of_world_data['locations'] );
	}

	/**
	 * Test shipping method formatting.
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
			$this->assertArrayHasKey( 'rate_description', $method );
			$this->assertIsInt( $method['instance_id'] );
			$this->assertIsString( $method['title'] );
			$this->assertIsBool( $method['enabled'] );
			$this->assertIsString( $method['rate_description'] );
		}
	}

	/**
	 * Test location formatting.
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
		$this->assertContains( 'California', $locations );
		$this->assertContains( 'New York', $locations );
		$this->assertContains( 'Canada', $locations );
	}

	/**
	 * Test empty locations.
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
	 * Test permissions.
	 */
	public function test_get_items_without_permission() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test zone ordering.
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
	 * Test schema.
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
		$this->assertEquals( 'string', $properties['locations']['items']['type'] );

		// Test methods schema.
		$this->assertEquals( 'array', $properties['methods']['type'] );
		$this->assertArrayHasKey( 'properties', $properties['methods']['items'] );

		$method_properties = $properties['methods']['items']['properties'];
		$this->assertArrayHasKey( 'instance_id', $method_properties );
		$this->assertArrayHasKey( 'title', $method_properties );
		$this->assertArrayHasKey( 'enabled', $method_properties );
		$this->assertArrayHasKey( 'rate_description', $method_properties );
	}
}

