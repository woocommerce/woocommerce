<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\RestApi\Routes\V4\ShippingZones;

use Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones\Controller;
use Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones\ShippingZoneSchema;
use WC_REST_Unit_Test_Case;
use WC_Shipping_Zones;
use WP_REST_Request;

/**
 * Class ControllerTest
 *
 * @package Automattic\WooCommerce\Tests\RestApi\Routes\V4\ShippingZones
 */
class ControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * @var Controller
	 */
	private Controller $controller;

	/**
	 * @var ShippingZoneSchema
	 */
	private ShippingZoneSchema $schema;

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

		$this->schema     = new ShippingZoneSchema();
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
	 * Test creating a shipping zone with all fields.
	 */
	public function test_create_item_with_all_fields() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'California Zone',
				'order'     => 5,
				'locations' => array(
					array(
						'code' => 'US:CA',
						'type' => 'state',
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
		$this->assertEquals( 'California Zone', $data['name'] );
		$this->assertEquals( 5, $data['order'] );
		$this->assertIsArray( $data['locations'] );
		$this->assertCount( 1, $data['locations'] );
		$this->assertEquals( 'US:CA', $data['locations'][0]['code'] );
		$this->assertEquals( 'state', $data['locations'][0]['type'] );
		$this->assertEquals( 'California', $data['locations'][0]['name'] );
		$this->assertIsArray( $data['methods'] );
		$this->assertEmpty( $data['methods'] );

		// Verify Location header.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Location', $headers );
		$this->assertStringContainsString( '/wc/v4/shipping-zones/' . $data['id'], $headers['Location'] );

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test creating a shipping zone with minimal fields (empty locations).
	 */
	public function test_create_item_with_minimal_fields() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Test Zone',
				'locations' => array(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertIsInt( $data['id'] );
		$this->assertEquals( 'Test Zone', $data['name'] );
		$this->assertEquals( 0, $data['order'] ); // Should default to 0.
		$this->assertIsArray( $data['locations'] );
		$this->assertEmpty( $data['locations'] );

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test creating a shipping zone with multiple locations.
	 */
	public function test_create_item_with_multiple_locations() {
		wp_set_current_user( self::$admin_user_id );

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
						'code' => 'CA',
						'type' => 'country',
					),
					array(
						'code' => 'EU',
						'type' => 'continent',
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 'Multi-Location Zone', $data['name'] );
		$this->assertCount( 3, $data['locations'] );

		// Verify first location.
		$this->assertEquals( 'US', $data['locations'][0]['code'] );
		$this->assertEquals( 'country', $data['locations'][0]['type'] );
		$this->assertEquals( 'United States (US)', $data['locations'][0]['name'] );

		// Verify second location.
		$this->assertEquals( 'CA', $data['locations'][1]['code'] );
		$this->assertEquals( 'country', $data['locations'][1]['type'] );
		$this->assertEquals( 'Canada', $data['locations'][1]['name'] );

		// Verify third location.
		$this->assertEquals( 'EU', $data['locations'][2]['code'] );
		$this->assertEquals( 'continent', $data['locations'][2]['type'] );
		$this->assertEquals( 'Europe', $data['locations'][2]['name'] );

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test creating a zone with postcode location.
	 */
	public function test_create_item_with_postcode_location() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Postcode Zone',
				'locations' => array(
					array(
						'code' => '90210',
						'type' => 'postcode',
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertCount( 1, $data['locations'] );
		$this->assertEquals( '90210', $data['locations'][0]['code'] );
		$this->assertEquals( 'postcode', $data['locations'][0]['type'] );
		$this->assertEquals( '90210', $data['locations'][0]['name'] ); // Postcodes return the code as the name.

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test validation - missing name.
	 */
	public function test_create_item_missing_name() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'locations' => array(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test validation - missing locations.
	 */
	public function test_create_item_missing_locations() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name' => 'Invalid Zone',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test validation - invalid location type is skipped.
	 */
	public function test_create_item_invalid_location_type() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Invalid Location Type',
				'locations' => array(
					array(
						'code' => 'US',
						'type' => 'invalid_type',
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		// Invalid location type should be skipped, so locations should be empty.
		$this->assertEmpty( $data['locations'] );

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test validation - location without code is skipped.
	 */
	public function test_create_item_location_without_code() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Missing Code',
				'locations' => array(
					array(
						'type' => 'country',
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		// Location without code should be skipped.
		$this->assertEmpty( $data['locations'] );

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test permission check - no authentication.
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

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test permission check - user without manage_woocommerce capability.
	 */
	public function test_create_item_with_read_only_permission() {
		$subscriber_id = self::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		wp_set_current_user( $subscriber_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Read Only Zone',
				'locations' => array(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 403, $response->get_status() );

		// Cleanup.
		wp_delete_user( $subscriber_id );
	}

	/**
	 * Test creating zone when shipping is disabled.
	 */
	public function test_create_item_when_shipping_disabled() {
		wp_set_current_user( self::$admin_user_id );

		// Disable shipping.
		update_option( 'woocommerce_ship_to_countries', 'disabled' );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Disabled Shipping Zone',
				'locations' => array(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 503, $response->get_status() );
		$this->assertEquals( 'rest_shipping_disabled', $data['code'] );
		$this->assertEquals( 'Shipping is disabled.', $data['message'] );

		// Re-enable shipping.
		update_option( 'woocommerce_ship_to_countries', '' );
	}

	/**
	 * Test location type defaults to country when not specified.
	 */
	public function test_create_item_location_type_defaults() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Default Type Zone',
				'locations' => array(
					array(
						'code' => 'US',
						// No type specified.
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertCount( 1, $data['locations'] );
		$this->assertEquals( 'US', $data['locations'][0]['code'] );
		$this->assertEquals( 'country', $data['locations'][0]['type'] ); // Should default to 'country'.

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test that created zone can be retrieved.
	 */
	public function test_create_and_retrieve_item() {
		wp_set_current_user( self::$admin_user_id );

		// Create zone.
		$create_request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$create_request->set_body_params(
			array(
				'name'      => 'Retrieve Test Zone',
				'order'     => 3,
				'locations' => array(
					array(
						'code' => 'GB',
						'type' => 'country',
					),
				),
			)
		);

		$create_response = rest_get_server()->dispatch( $create_request );
		$create_data     = $create_response->get_data();

		$this->assertEquals( 201, $create_response->get_status() );
		$zone_id = $create_data['id'];

		// Retrieve the zone.
		$get_request  = new WP_REST_Request( 'GET', '/wc/v4/shipping-zones/' . $zone_id );
		$get_response = rest_get_server()->dispatch( $get_request );
		$get_data     = $get_response->get_data();

		$this->assertEquals( 200, $get_response->get_status() );
		$this->assertEquals( $zone_id, $get_data['id'] );
		$this->assertEquals( 'Retrieve Test Zone', $get_data['name'] );
		$this->assertEquals( 3, $get_data['order'] );
		$this->assertCount( 1, $get_data['locations'] );
		$this->assertEquals( 'GB', $get_data['locations'][0]['code'] );

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $zone_id );
		$this->created_zones[] = $zone;
	}

	/**
	 * Test sanitization of location codes.
	 */
	public function test_create_item_sanitizes_location_data() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/shipping-zones' );
		$request->set_body_params(
			array(
				'name'      => 'Sanitization Test',
				'locations' => array(
					array(
						'code' => 'US  ',
						'type' => '  country  ',
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		// Verify whitespace is trimmed.
		$this->assertEquals( 'US', $data['locations'][0]['code'] );
		$this->assertEquals( 'country', $data['locations'][0]['type'] );

		// Track for cleanup.
		$zone                  = WC_Shipping_Zones::get_zone( $data['id'] );
		$this->created_zones[] = $zone;
	}
}
