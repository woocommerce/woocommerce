<?php
/**
 * Tests for the WC_Shopping_Zones class.
 *
 * @package WooCommerce\Tests\Shipping
 */

/**
 * Class Shipping_Zones.
 * @package WooCommerce\Tests\Shipping
 */
class WC_Tests_Shipping_Zones extends WC_Unit_Test_Case {

	/**
	 * Set up tests.
	 */
	public function setUp() {
		parent::setUp();

		WC_Helper_Shipping_Zones::create_mock_zones();
	}

	/**
	 * Test: WC_Shipping_Zones::get_zones
	 */
	public function test_get_zones() {
		// Test.
		$zones = WC_Shipping_Zones::get_zones();

		// Assert.
		$this->assertTrue( is_array( $zones ) );
		$this->assertTrue( 4 === count( $zones ) );
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone
	 */
	public function test_get_zone() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert that the first zone is our local zone.
		$this->assertInstanceOf( 'WC_Shipping_Zone', $zone );
		$this->assertEquals( $zone->get_zone_name(), 'Local' );
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone_by
	 */
	public function test_get_zone_by() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone_by( 'zone_id', 2 );

		// Assert.
		$this->assertInstanceOf( 'WC_Shipping_Zone', $zone );
		$this->assertEquals( $zone->get_zone_name(), 'Europe' );

		// Test instance_id.
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );

		// Assert.
		$this->assertInstanceOf( 'WC_Shipping_Zone', $zone );
		$this->assertEquals( $zone->get_zone_name(), 'Europe' );
	}

	/**
	 * Test: WC_Shipping_Zones::get_shipping_method
	 */
	public function test_get_shipping_method() {
		// Test.
		$zone            = WC_Shipping_Zones::get_zone_by( 'zone_id', 1 );
		$instance_id     = $zone->add_shipping_method( 'flat_rate' );
		$shipping_method = WC_Shipping_Zones::get_shipping_method( $instance_id );

		// Assert.
		$this->assertInstanceOf( 'WC_Shipping_Flat_Rate', $shipping_method );
	}

	/**
	 * Test: WC_Shipping_Zones::delete_zone
	 */
	public function test_delete_zone() {
		// Test.
		WC_Shipping_Zones::delete_zone( 1 );
		$zones = WC_Shipping_Zones::get_zones();

		// Assert.
		$this->assertTrue( 3 === count( $zones ) );
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone_matching_package
	 */
	public function test_get_zone_matching_package() {
		// Test.
		$zone1 = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'GB',
					'state'    => 'Cambs',
					'postcode' => 'CB23 1GG',
				),
			)
		);
		$zone2 = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'GB',
					'state'    => 'Cambs',
					'postcode' => 'PE12 1BG',
				),
			)
		);
		$zone3 = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90210',
				),
			)
		);
		$zone4 = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'AL',
					'postcode' => '12345',
				),
			)
		);

		// Assert.
		$this->assertEquals( 'Local', $zone1->get_zone_name() );
		$this->assertEquals( 'Europe', $zone2->get_zone_name() );
		$this->assertEquals( 'California', $zone3->get_zone_name() );
		$this->assertEquals( 'US', $zone4->get_zone_name() );
	}
}
