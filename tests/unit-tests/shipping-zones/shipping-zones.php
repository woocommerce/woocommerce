<?php

/**
 * Class Shipping_Zones.
 * @package WooCommerce\Tests\Shipping_Zones
 */
class WC_Tests_Shipping_Zones extends WC_Unit_Test_Case {

	/**
	 * Test: WC_Shipping_Zones::get_zones
	 */
	public function test_get_zones() {
		// Setup
		WC_Helper_Shipping_Zones::create_mock_zones();

		// Test
		$zones = WC_Shipping_Zones::get_zones();

		// Assert
		$this->assertTrue( is_array( $zones ) );
		$this->assertTrue( 4 === sizeof( $zones ) );

		// Clean
		WC_Helper_Shipping_Zones::remove_mock_zones();
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone
	 */
	public function test_get_zone() {
		// Setup
		WC_Helper_Shipping_Zones::create_mock_zones();

		// Test
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert that the first zone is our local zone
		$this->assertInstanceOf( 'WC_Shipping_Zone', $zone );
		$this->assertEquals( $zone->get_zone_name(), 'Local' );

		// Clean
		WC_Helper_Shipping_Zones::remove_mock_zones();
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone_by
	 */
	public function test_get_zone_by() {
		// Setup
		WC_Helper_Shipping_Zones::create_mock_zones();

		// Test
		$zone = WC_Shipping_Zones::get_zone_by( 'zone_id', 2 );

		// Assert
		$this->assertInstanceOf( 'WC_Shipping_Zone', $zone );
		$this->assertEquals( $zone->get_zone_name(), 'Europe' );

		// Test instance_id
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );

		// Assert
		$this->assertInstanceOf( 'WC_Shipping_Zone', $zone );
		$this->assertEquals( $zone->get_zone_name(), 'Europe' );

		// Clean
		WC_Helper_Shipping_Zones::remove_mock_zones();
	}

	/**
	 * Test: WC_Shipping_Zones::get_shipping_method
	 */
	public function test_get_shipping_method() {
		// Setup
		WC_Helper_Shipping_Zones::create_mock_zones();

		// Test
		$zone            = WC_Shipping_Zones::get_zone_by( 'zone_id', 1 );
		$instance_id     = $zone->add_shipping_method( 'flat_rate' );
		$shipping_method = WC_Shipping_Zones::get_shipping_method( $instance_id );

		// Assert
		$this->assertInstanceOf( 'WC_Shipping_Flat_Rate', $shipping_method );

		// Clean
		WC_Helper_Shipping_Zones::remove_mock_zones();
	}

	/**
	 * Test: WC_Shipping_Zones::delete_zone
	 */
	public function test_delete_zone() {
		// Setup
		WC_Helper_Shipping_Zones::create_mock_zones();

		// Test
		WC_Shipping_Zones::delete_zone( 1 );
		$zones = WC_Shipping_Zones::get_zones();

		// Assert
		$this->assertTrue( 3 === sizeof( $zones ) );

		// Clean
		WC_Helper_Shipping_Zones::remove_mock_zones();
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone_matching_package
	 */
	public function test_get_zone_matching_package() {
		// Setup
		WC_Helper_Shipping_Zones::create_mock_zones();

		// Test
		$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
			'destination' => array(
				'country'  => 'GB',
				'state'    => 'Cambs',
				'postcode' => 'CB23 1GG',
			),
		) );
		$zone2 = WC_Shipping_Zones::get_zone_matching_package( array(
			'destination' => array(
				'country'  => 'GB',
				'state'    => 'Cambs',
				'postcode' => 'PE12 1BG',
			),
		) );
		$zone3 = WC_Shipping_Zones::get_zone_matching_package( array(
			'destination' => array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '90210',
			),
		) );
		$zone4 = WC_Shipping_Zones::get_zone_matching_package( array(
			'destination' => array(
				'country'  => 'US',
				'state'    => 'AL',
				'postcode' => '12345',
			),
		) );

		// Assert
		$this->assertEquals( 'Local', $zone1->get_zone_name() );
		$this->assertEquals( 'Europe', $zone2->get_zone_name() );
		$this->assertEquals( 'California', $zone3->get_zone_name() );
		$this->assertEquals( 'US', $zone4->get_zone_name() );

		// Clean
		WC_Helper_Shipping_Zones::remove_mock_zones();
	}
}
