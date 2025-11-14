<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\ShippingZoneMethod;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingMethodSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingZoneMethodService;
use WC_Shipping_Zone;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Class ShippingMethodSchemaTest
 *
 * @package Automattic\WooCommerce\Tests\RestApi\Routes\V4\ShippingZoneMethod
 */
class WC_REST_Shipping_Zone_Method_V4_Schema_Tests extends WC_Unit_Test_Case {

	/**
	 * @var ShippingMethodSchema
	 */
	private ShippingMethodSchema $schema;

	/**
	 * @var ShippingZoneMethodService
	 */
	private ShippingZoneMethodService $shipping_service;

	/**
	 * Created shipping zones for cleanup.
	 *
	 * @var array
	 */
	private array $created_zones = array();

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->schema           = new ShippingMethodSchema();
		$this->shipping_service = new ShippingZoneMethodService();
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
	 * Test schema identifier.
	 */
	public function test_schema_identifier() {
		$this->assertEquals( 'shipping_method', ShippingMethodSchema::IDENTIFIER );
	}

	/**
	 * Test get item schema properties.
	 */
	public function test_get_item_schema_properties() {
		$properties = $this->schema->get_item_schema_properties();

		$this->assertArrayHasKey( 'instance_id', $properties );
		$this->assertArrayHasKey( 'zone_id', $properties );
		$this->assertArrayHasKey( 'enabled', $properties );
		$this->assertArrayHasKey( 'method_id', $properties );
		$this->assertArrayHasKey( 'settings', $properties );

		// Test instance_id properties.
		$this->assertEquals( 'integer', $properties['instance_id']['type'] );
		$this->assertTrue( $properties['instance_id']['readonly'] );
		$this->assertContains( 'view', $properties['instance_id']['context'] );
		$this->assertContains( 'edit', $properties['instance_id']['context'] );

		// Test zone_id properties.
		$this->assertEquals( 'integer', $properties['zone_id']['type'] );
		$this->assertTrue( $properties['zone_id']['required'] );
		$this->assertContains( 'view', $properties['zone_id']['context'] );
		$this->assertContains( 'edit', $properties['zone_id']['context'] );

		// Test enabled properties.
		$this->assertEquals( 'boolean', $properties['enabled']['type'] );
		$this->assertTrue( $properties['enabled']['required'] );
		$this->assertContains( 'view', $properties['enabled']['context'] );
		$this->assertContains( 'edit', $properties['enabled']['context'] );

		// Test order properties.
		$this->assertEquals( 'integer', $properties['order']['type'] );
		$this->assertContains( 'view', $properties['order']['context'] );
		$this->assertContains( 'edit', $properties['order']['context'] );

		// Test method_id properties.
		$this->assertEquals( 'string', $properties['method_id']['type'] );
		$this->assertTrue( $properties['method_id']['required'] );
		$this->assertContains( 'view', $properties['method_id']['context'] );
		$this->assertContains( 'edit', $properties['method_id']['context'] );

		// Test settings properties.
		$this->assertEquals( 'object', $properties['settings']['type'] );
		$this->assertTrue( $properties['settings']['required'] );
		$this->assertTrue( $properties['settings']['additionalProperties'] );
		$this->assertContains( 'view', $properties['settings']['context'] );
		$this->assertContains( 'edit', $properties['settings']['context'] );

		// Test settings.title property.
		$this->assertArrayHasKey( 'title', $properties['settings']['properties'] );
		$this->assertEquals( 'string', $properties['settings']['properties']['title']['type'] );
		$this->assertTrue( $properties['settings']['properties']['title']['required'] );
	}

	/**
	 * Test get item schema.
	 */
	public function test_get_item_schema() {
		$schema = $this->schema->get_item_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertEquals( ShippingMethodSchema::IDENTIFIER, $schema['title'] );
	}

	/**
	 * Test get item response.
	 */
	public function test_get_item_response() {
		// Create zone and add flat rate method.
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Get the method instance.
		$method = \WC_Shipping_Zones::get_shipping_method( $instance_id );
		$this->assertNotNull( $method );

		// Update method settings using ShippingService.
		$method = $this->shipping_service->update_shipping_zone_method(
			$method,
			$instance_id,
			array(
				'enabled'  => true,
				'settings' => array(
					'title' => 'Test Flat Rate Method',
					'cost'  => '10.00',
				),
			)
		);
		$this->assertInstanceOf( \WC_Shipping_Method::class, $method );

		$request  = new WP_REST_Request( 'GET' );
		$response = $this->schema->get_item_response( $method, $request );

		$this->assertArrayHasKey( 'instance_id', $response );
		$this->assertArrayHasKey( 'zone_id', $response );
		$this->assertArrayHasKey( 'enabled', $response );
		$this->assertArrayHasKey( 'method_id', $response );
		$this->assertArrayHasKey( 'settings', $response );

		$this->assertEquals( $instance_id, $response['instance_id'] );
		$this->assertEquals( $zone->get_id(), $response['zone_id'] );
		$this->assertTrue( $response['enabled'] );
		$this->assertEquals( 'flat_rate', $response['method_id'] );

		// Test settings structure.
		$this->assertArrayHasKey( 'title', $response['settings'] );
		$this->assertEquals( 'Test Flat Rate Method', $response['settings']['title'] );
		$this->assertEquals( '10.00', $response['settings']['cost'] );
	}

	/**
	 * Test get method settings with different method types.
	 */
	public function test_get_method_settings_different_types() {
		$zone = $this->create_shipping_zone();

		// Test flat rate method.
		$flat_rate_id = $zone->add_shipping_method( 'flat_rate' );
		$flat_rate    = \WC_Shipping_Zones::get_shipping_method( $flat_rate_id );

		$flat_rate = $this->shipping_service->update_shipping_zone_method(
			$flat_rate,
			$flat_rate_id,
			array(
				'settings' => array(
					'title'      => 'Flat Rate Test',
					'cost'       => '15.50',
					'tax_status' => 'taxable',
				),
			)
		);
		$this->assertInstanceOf( \WC_Shipping_Method::class, $flat_rate );

		$reflection = new \ReflectionClass( $this->schema );
		$method     = $reflection->getMethod( 'get_method_settings' );
		$method->setAccessible( true );

		$settings = $method->invoke( $this->schema, $flat_rate );

		$this->assertArrayHasKey( 'title', $settings );
		$this->assertArrayHasKey( 'cost', $settings );
		$this->assertArrayHasKey( 'tax_status', $settings );
		$this->assertEquals( 'Flat Rate Test', $settings['title'] );
		$this->assertEquals( '15.50', $settings['cost'] );
		$this->assertEquals( 'taxable', $settings['tax_status'] );
	}

	/**
	 * Test get method settings with minimal data.
	 */
	public function test_get_method_settings_minimal() {
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = \WC_Shipping_Zones::get_shipping_method( $instance_id );

		$reflection = new \ReflectionClass( $this->schema );
		$get_method = $reflection->getMethod( 'get_method_settings' );
		$get_method->setAccessible( true );

		$settings = $get_method->invoke( $this->schema, $method );

		// Should at least have title.
		$this->assertArrayHasKey( 'title', $settings );
		$this->assertIsString( $settings['title'] );
	}

	/**
	 * Test get item response with include fields.
	 */
	public function test_get_item_response_with_include_fields() {
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = \WC_Shipping_Zones::get_shipping_method( $instance_id );

		$request  = new WP_REST_Request( 'GET' );
		$response = $this->schema->get_item_response(
			$method,
			$request,
			array( 'instance_id', 'method_id' )
		);

		// Should still return all fields since include_fields isn't implemented.
		// This is for future extensibility.
		$this->assertArrayHasKey( 'instance_id', $response );
		$this->assertArrayHasKey( 'zone_id', $response );
		$this->assertArrayHasKey( 'enabled', $response );
		$this->assertArrayHasKey( 'method_id', $response );
		$this->assertArrayHasKey( 'settings', $response );
	}

	/**
	 * Test get item response with free shipping method.
	 */
	public function test_get_item_response_free_shipping() {
		$zone        = $this->create_shipping_zone();
		$instance_id = $zone->add_shipping_method( 'free_shipping' );

		$method = \WC_Shipping_Zones::get_shipping_method( $instance_id );
		$method = $this->shipping_service->update_shipping_zone_method(
			$method,
			$instance_id,
			array(
				'settings' => array(
					'title'      => 'Free Shipping Test',
					'requires'   => 'min_amount',
					'min_amount' => '50.00',
				),
			)
		);
		$this->assertInstanceOf( \WC_Shipping_Method::class, $method );

		$request  = new WP_REST_Request( 'GET' );
		$response = $this->schema->get_item_response( $method, $request );

		$this->assertEquals( $instance_id, $response['instance_id'] );
		$this->assertEquals( $zone->get_id(), $response['zone_id'] );
		$this->assertIsBool( $response['enabled'] ); // Just verify it's a boolean, not specific value.
		$this->assertEquals( 'free_shipping', $response['method_id'] );
		$this->assertEquals( 'Free Shipping Test', $response['settings']['title'] );
		$this->assertEquals( 'min_amount', $response['settings']['requires'] );
		$this->assertEquals( '50.00', $response['settings']['min_amount'] );
	}
}
