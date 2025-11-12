<?php
/**
 * ShippingZoneService tests.
 *
 * @package WooCommerce\Tests\RestApi\Controllers\Version4\ShippingZones
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones\ShippingZoneService;

/**
 * ShippingZoneService test class.
 */
class WC_REST_Shipping_Zones_V4_Service_Tests extends WC_Unit_Test_Case {

	/**
	 * ShippingZoneService instance.
	 *
	 * @var ShippingZoneService
	 */
	private $service;

	/**
	 * Created shipping zones for cleanup.
	 *
	 * @var array
	 */
	private $created_zones = array();

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->service = new ShippingZoneService();

		// Ensure shipping is enabled for tests.
		update_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		// Clean up created zones.
		foreach ( $this->created_zones as $zone ) {
			if ( $zone instanceof WC_Shipping_Zone && $zone->get_id() > 0 ) {
				$zone->delete();
			}
		}
		$this->created_zones = array();

		parent::tearDown();
	}

	/**
	 * Helper method to create a shipping zone.
	 *
	 * @param string $name Zone name.
	 * @param int    $order Zone order.
	 * @param array  $locations Zone locations.
	 * @return WC_Shipping_Zone
	 */
	private function create_zone( $name = 'Test Zone', $order = 0, $locations = array() ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );
		$zone->set_locations( $locations );
		$zone->save();

		$this->created_zones[] = $zone;

		return $zone;
	}

	// ========================================
	// Tests for get_sorted_shipping_zones()
	// ========================================

	/**
	 * Test get_sorted_shipping_zones includes all zones.
	 */
	public function test_get_sorted_shipping_zones_includes_all_zones() {
		$zone1 = $this->create_zone( 'Zone 1', 1 );
		$zone2 = $this->create_zone( 'Zone 2', 2 );

		$zones = $this->service->get_sorted_shipping_zones();

		// Should include our 2 zones + Rest of the World.
		$this->assertGreaterThanOrEqual( 3, count( $zones ) );

		// Extract zone IDs.
		$zone_ids = array();
		foreach ( $zones as $zone_data ) {
			$zone_ids[] = $zone_data['zone_id'];
		}

		$this->assertContains( $zone1->get_id(), $zone_ids );
		$this->assertContains( $zone2->get_id(), $zone_ids );

		// Check that Rest of the World (ID 0) is included.
		$this->assertContains( 0, $zone_ids );
	}

	/**
	 * Test get_sorted_shipping_zones sorts by order.
	 */
	public function test_get_sorted_shipping_zones_sorts_by_order() {
		$zone3 = $this->create_zone( 'Zone Order 3', 3 );
		$zone1 = $this->create_zone( 'Zone Order 1', 1 );
		$zone2 = $this->create_zone( 'Zone Order 2', 2 );

		$zones = $this->service->get_sorted_shipping_zones();

		// Find our test zones in the sorted result.
		$zone_orders = array();
		foreach ( $zones as $zone_data ) {
			if ( isset( $zone_data['zone_name'] ) && strpos( $zone_data['zone_name'], 'Zone Order' ) === 0 ) {
				$zone_orders[] = $zone_data['zone_order'];
			}
		}

		// Should be sorted ascending by order.
		$this->assertEquals( array( 1, 2, 3 ), $zone_orders );
	}

	/**
	 * Test get_sorted_shipping_zones includes Rest of World zone.
	 */
	public function test_get_sorted_shipping_zones_includes_rest_of_world() {
		$this->create_zone( 'Zone 1', 10 );
		$this->create_zone( 'Zone 2', 20 );

		$zones = $this->service->get_sorted_shipping_zones();

		// Extract zone IDs.
		$zone_ids = array();
		foreach ( $zones as $zone_data ) {
			$zone_ids[] = $zone_data['zone_id'];
		}

		// Rest of the World (ID 0) should be included in the list.
		$this->assertContains( 0, $zone_ids );

		// Verify zones are sorted by order.
		$zone_orders = array();
		foreach ( $zones as $zone_data ) {
			$zone_orders[] = $zone_data['zone_order'];
		}

		// Orders should be in ascending sequence.
		$sorted_orders = $zone_orders;
		sort( $sorted_orders );
		$this->assertEquals( $sorted_orders, $zone_orders );
	}

	// ========================================
	// Tests for create_shipping_zone()
	// ========================================

	/**
	 * Test create_shipping_zone with name only.
	 */
	public function test_create_shipping_zone_with_name() {
		$params = array( 'name' => 'New Test Zone' );

		$zone = $this->service->create_shipping_zone( $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $zone );
		$this->assertGreaterThan( 0, $zone->get_id() );
		$this->assertEquals( 'New Test Zone', $zone->get_zone_name() );

		$this->created_zones[] = $zone;
	}

	/**
	 * Test create_shipping_zone with name and locations.
	 */
	public function test_create_shipping_zone_with_locations() {
		$params = array(
			'name'      => 'US Zone',
			'locations' => array(
				array(
					'code' => 'US',
					'type' => 'country',
				),
			),
		);

		$zone = $this->service->create_shipping_zone( $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $zone );
		$this->assertEquals( 'US Zone', $zone->get_zone_name() );

		$locations = $zone->get_zone_locations();
		$this->assertCount( 1, $locations );
		$this->assertEquals( 'US', $locations[0]->code );
		$this->assertEquals( 'country', $locations[0]->type );

		$this->created_zones[] = $zone;
	}

	/**
	 * Test create_shipping_zone with name, order, and locations.
	 */
	public function test_create_shipping_zone_with_all_fields() {
		$params = array(
			'name'      => 'Complete Zone',
			'order'     => 5,
			'locations' => array(
				array(
					'code' => 'CA',
					'type' => 'country',
				),
			),
		);

		$zone = $this->service->create_shipping_zone( $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $zone );
		$this->assertEquals( 'Complete Zone', $zone->get_zone_name() );
		$this->assertEquals( 5, $zone->get_zone_order() );
		$this->assertCount( 1, $zone->get_zone_locations() );

		$this->created_zones[] = $zone;
	}

	/**
	 * Test create_shipping_zone with empty name fails.
	 */
	public function test_create_shipping_zone_empty_name() {
		$params = array( 'name' => '' );

		$result = $this->service->create_shipping_zone( $params );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_invalid_zone_name', $result->get_error_code() );
		$this->assertEquals( 'Zone name cannot be empty.', $result->get_error_message() );
	}

	/**
	 * Test create_shipping_zone with whitespace-only name fails.
	 */
	public function test_create_shipping_zone_whitespace_name() {
		$params = array( 'name' => '   ' );

		$result = $this->service->create_shipping_zone( $params );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_invalid_zone_name', $result->get_error_code() );
	}

	// ========================================
	// Tests for update_shipping_zone()
	// ========================================

	/**
	 * Test update_shipping_zone name.
	 */
	public function test_update_shipping_zone_name() {
		$zone   = $this->create_zone( 'Original Name' );
		$params = array( 'name' => 'Updated Name' );

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$this->assertEquals( 'Updated Name', $result->get_zone_name() );
	}

	/**
	 * Test update_shipping_zone order.
	 */
	public function test_update_shipping_zone_order() {
		$zone   = $this->create_zone( 'Test Zone', 0 );
		$params = array( 'order' => 10 );

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$this->assertEquals( 10, $result->get_zone_order() );
	}

	/**
	 * Test update_shipping_zone locations.
	 */
	public function test_update_shipping_zone_locations() {
		$zone   = $this->create_zone( 'Test Zone' );
		$params = array(
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
		);

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$locations = $result->get_zone_locations();
		$this->assertCount( 2, $locations );

		$codes = array_map(
			function ( $location ) {
				return $location->code;
			},
			$locations
		);
		$this->assertContains( 'US', $codes );
		$this->assertContains( 'CA', $codes );
	}

	/**
	 * Test update_shipping_zone clears locations with empty array.
	 */
	public function test_update_shipping_zone_clear_locations() {
		$zone = $this->create_zone(
			'Test Zone',
			0,
			array(
				array(
					'code' => 'US',
					'type' => 'country',
				),
			)
		);

		$this->assertCount( 1, $zone->get_zone_locations() );

		$params = array( 'locations' => array() );
		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$this->assertCount( 0, $result->get_zone_locations() );
	}

	/**
	 * Test update_shipping_zone with empty name fails.
	 */
	public function test_update_shipping_zone_empty_name() {
		$zone   = $this->create_zone( 'Test Zone' );
		$params = array( 'name' => '' );

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_invalid_zone_name', $result->get_error_code() );
	}

	/**
	 * Test update_shipping_zone normalizes country:state to state type.
	 */
	public function test_update_shipping_zone_normalize_country_state_type() {
		$zone   = $this->create_zone( 'Test Zone' );
		$params = array(
			'locations' => array(
				array(
					'code' => 'US:CA',
					'type' => 'country:state',
				),
			),
		);

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$locations = $result->get_zone_locations();
		$this->assertCount( 1, $locations );
		$this->assertEquals( 'state', $locations[0]->type );
	}

	/**
	 * Test update_shipping_zone skips invalid location types.
	 */
	public function test_update_shipping_zone_skips_invalid_location_types() {
		$zone   = $this->create_zone( 'Test Zone' );
		$params = array(
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
		);

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$locations = $result->get_zone_locations();
		$this->assertCount( 1, $locations );
		$this->assertEquals( 'CA', $locations[0]->code );
	}

	/**
	 * Test update_shipping_zone skips empty location codes.
	 */
	public function test_update_shipping_zone_skips_empty_location_codes() {
		$zone   = $this->create_zone( 'Test Zone' );
		$params = array(
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
		);

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$locations = $result->get_zone_locations();
		$this->assertCount( 1, $locations );
		$this->assertEquals( 'US', $locations[0]->code );
	}

	/**
	 * Test update_shipping_zone defaults location type to country.
	 */
	public function test_update_shipping_zone_defaults_location_type_to_country() {
		$zone   = $this->create_zone( 'Test Zone' );
		$params = array(
			'locations' => array(
				array( 'code' => 'GB' ),
			),
		);

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
		$locations = $result->get_zone_locations();
		$this->assertCount( 1, $locations );
		$this->assertEquals( 'country', $locations[0]->type );
	}

	// ========================================
	// Tests for Rest of the World restrictions
	// ========================================

	/**
	 * Test update_shipping_zone cannot change Rest of World name.
	 */
	public function test_update_rest_of_world_zone_name_fails() {
		$zone   = WC_Shipping_Zones::get_zone( 0 );
		$params = array( 'name' => 'New Name' );

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_cannot_edit_zone', $result->get_error_code() );
		$this->assertStringContainsString( 'Cannot change name of "Rest of the World" zone', $result->get_error_message() );
	}

	/**
	 * Test update_shipping_zone cannot change Rest of World order.
	 */
	public function test_update_rest_of_world_zone_order_fails() {
		$zone   = WC_Shipping_Zones::get_zone( 0 );
		$params = array( 'order' => 5 );

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_cannot_edit_zone', $result->get_error_code() );
		$this->assertStringContainsString( 'Cannot change order of "Rest of the World" zone', $result->get_error_message() );
	}

	/**
	 * Test update_shipping_zone cannot change Rest of World locations.
	 */
	public function test_update_rest_of_world_zone_locations_fails() {
		$zone   = WC_Shipping_Zones::get_zone( 0 );
		$params = array(
			'locations' => array(
				array(
					'code' => 'US',
					'type' => 'country',
				),
			),
		);

		$result = $this->service->update_shipping_zone( $zone, $params );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_cannot_edit_zone', $result->get_error_code() );
		$this->assertStringContainsString( 'Cannot change locations of "Rest of the World" zone', $result->get_error_message() );
	}

	/**
	 * Test update_shipping_zone allows null values for Rest of World.
	 */
	public function test_update_rest_of_world_zone_allows_null() {
		$zone   = WC_Shipping_Zones::get_zone( 0 );
		$params = array(
			'name'      => null,
			'order'     => null,
			'locations' => null,
		);

		$result = $this->service->update_shipping_zone( $zone, $params );

		// Should succeed because null values are ignored.
		$this->assertInstanceOf( WC_Shipping_Zone::class, $result );
	}
}
