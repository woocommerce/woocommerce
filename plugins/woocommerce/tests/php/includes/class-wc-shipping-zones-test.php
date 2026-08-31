<?php
declare( strict_types = 1 );

/**
 * Class WC_Shipping_Zones_Test file.
 *
 * @package WooCommerce\Tests\Shipping
 */

/**
 * Tests for the WC_Shipping_Zones class.
 */
class WC_Shipping_Zones_Test extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		WC_Helper_Shipping_Zones::remove_mock_zones();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		remove_filter( 'woocommerce_valid_location_types', array( $this, 'add_custom_location_type' ) );
		WC_Helper_Shipping_Zones::remove_mock_zones();
	}

	/**
	 * @testdox Should warn when a postcode zone is below a broader state zone.
	 */
	public function test_warns_when_postcode_zone_is_below_broader_state_zone(): void {
		$state_zone = $this->create_zone(
			'Washington State',
			0,
			array(
				array( 'US:WA', 'state' ),
			)
		);
		$zip_zone   = $this->create_zone(
			'Washington ZIP',
			1,
			array(
				array( 'US:WA', 'state' ),
				array( '98012', 'postcode' ),
			)
		);

		$zones = WC_Shipping_Zones::get_zones_with_order_conflict_warnings( 'json' );

		$this->assertArrayHasKey( $zip_zone->get_id(), $zones );
		$this->assertArrayHasKey( 'zone_order_conflict_warning', $zones[ $zip_zone->get_id() ] );
		$this->assertStringContainsString( $state_zone->get_zone_name(), $zones[ $zip_zone->get_id() ]['zone_order_conflict_warning'] );
	}

	/**
	 * @testdox Should not warn when a postcode zone is above a broader state zone.
	 */
	public function test_does_not_warn_when_postcode_zone_is_above_broader_state_zone(): void {
		$zip_zone = $this->create_zone(
			'Washington ZIP',
			0,
			array(
				array( 'US:WA', 'state' ),
				array( '98012', 'postcode' ),
			)
		);
		$this->create_zone(
			'Washington State',
			1,
			array(
				array( 'US:WA', 'state' ),
			)
		);

		$zones = WC_Shipping_Zones::get_zones_with_order_conflict_warnings( 'json' );

		$this->assertArrayHasKey( $zip_zone->get_id(), $zones );
		$this->assertArrayNotHasKey( 'zone_order_conflict_warning', $zones[ $zip_zone->get_id() ] );
	}

	/**
	 * @testdox Should not warn when a higher-priority zone has only custom locations.
	 */
	public function test_does_not_warn_when_higher_priority_zone_has_only_custom_locations(): void {
		add_filter( 'woocommerce_valid_location_types', array( $this, 'add_custom_location_type' ) );

		$this->create_zone(
			'Custom Region',
			0,
			array(
				array( 'custom-region', 'custom_location' ),
			)
		);
		$state_zone = $this->create_zone(
			'Washington State',
			1,
			array(
				array( 'US:WA', 'state' ),
			)
		);

		$zones = WC_Shipping_Zones::get_zones_with_order_conflict_warnings( 'json' );

		$this->assertArrayHasKey( $state_zone->get_id(), $zones );
		$this->assertArrayNotHasKey( 'zone_order_conflict_warning', $zones[ $state_zone->get_id() ] );
	}

	/**
	 * Add a custom location type for tests.
	 *
	 * @param array $location_types Location types.
	 * @return array Location types.
	 */
	public function add_custom_location_type( array $location_types ): array {
		$location_types[] = 'custom_location';

		return $location_types;
	}

	/**
	 * Create a shipping zone for tests.
	 *
	 * @param string $name Zone name.
	 * @param int    $order Zone order.
	 * @param array  $locations Zone locations.
	 * @return WC_Shipping_Zone
	 */
	private function create_zone( string $name, int $order, array $locations ): WC_Shipping_Zone {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );

		foreach ( $locations as $location ) {
			$zone->add_location( $location[0], $location[1] );
		}

		$zone->save();

		return $zone;
	}
}
