<?php
/**
 * Tests for the WC_Shopping_Zone class.
 *
 * @package WooCommerce\Tests\Shipping
 */

/**
 * Class Shipping_Zone.
 * @package WooCommerce\Tests\Shipping
 */
class WC_Tests_Shipping_Zone extends WC_Unit_Test_Case {

	/**
	 * Set up tests.
	 */
	public function setUp() {
		parent::setUp();

		WC_Helper_Shipping_Zones::create_mock_zones();
	}

	/**
	 * Test: WC_Shipping_Zone::get_data
	 */
	public function test_get_data() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$data = $zone->get_data();

		// Assert.
		$this->assertTrue( is_array( $data ) );
	}

	/**
	 * Test: WC_Shipping_Zones::get_id
	 */
	public function test_get_zone_id() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertEquals( $zone->get_id(), 1 );
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone_name
	 */
	public function test_get_zone_name() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertEquals( $zone->get_zone_name(), 'Local' );
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone_order
	 */
	public function test_get_zone_order() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertEquals( $zone->get_zone_order(), 1 );
	}

	/**
	 * Test: WC_Shipping_Zones::get_zone_locations
	 */
	public function test_get_zone_locations() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertTrue( is_array( $zone->get_zone_locations() ) );
		$this->assertTrue( 2 === count( $zone->get_zone_locations() ) );
	}

	/**
	 * Test: WC_Shipping_Zones::get_formatted_location
	 */
	public function test_get_formatted_location() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertEquals( $zone->get_formatted_location(), 'United Kingdom (UK), CB*' );

		// Test.
		$zone = WC_Shipping_Zones::get_zone( 2 );

		// Assert.
		$this->assertEquals( $zone->get_formatted_location(), 'Europe' );

		// Test.
		$zone = WC_Shipping_Zones::get_zone( 3 );

		// Assert.
		$this->assertEquals( $zone->get_formatted_location(), 'California' );

		// Test.
		$zone = WC_Shipping_Zones::get_zone( 4 );

		// Assert.
		$this->assertEquals( $zone->get_formatted_location(), 'United States (US)' );
	}

	/**
	 * Test: WC_Shipping_Zone::get_shipping_methods
	 */
	public function test_get_shipping_methods() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$zone->add_shipping_method( 'flat_rate' );
		$methods = $zone->get_shipping_methods();

		// Assert.
		$this->assertTrue( 1 === count( $methods ) );
		$this->assertInstanceOf( 'WC_Shipping_Method', current( $methods ) );
	}

	/**
	 * Test: WC_Shipping_Zone::set_zone_name
	 */
	public function test_set_zone_name() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$zone->set_zone_name( 'I am a fish' );

		// Assert.
		$this->assertEquals( $zone->get_zone_name(), 'I am a fish' );
	}

	/**
	 * Test: WC_Shipping_Zone::set_zone_order
	 */
	public function test_set_zone_order() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$zone->set_zone_order( 100 );

		// Assert.
		$this->assertEquals( $zone->get_zone_order(), 100 );
	}

	/**
	 * Test: WC_Shipping_Zone::is_valid_location_type
	 */
	public function test_is_valid_location_type() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertEquals( $zone->get_zone_order(), 1 );
	}

	/**
	 * Test: WC_Shipping_Zone::add_location
	 */
	public function test_add_location() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertTrue( $zone->is_valid_location_type( 'state' ) );
		$this->assertTrue( $zone->is_valid_location_type( 'country' ) );
		$this->assertTrue( $zone->is_valid_location_type( 'continent' ) );
		$this->assertTrue( $zone->is_valid_location_type( 'postcode' ) );
		$this->assertFalse( $zone->is_valid_location_type( 'poop' ) );
		add_filter( 'woocommerce_valid_location_types', array( $this, 'add_valid_zone_location' ) );
		$this->assertTrue( $zone->is_valid_location_type( 'poop' ) );
		remove_filter( 'woocommerce_valid_location_types', array( $this, 'add_valid_zone_location' ), 10 );
	}

	/**
	 * Add a custom zone location.
	 *
	 * @param array $locations Valid zone locations.
	 * @return array New list of valid zone locations.
	 */
	public function add_valid_zone_location( $locations ) {
		return array_merge( $locations, array( 'poop' ) );
	}

	/**
	 * Test: WC_Shipping_Zone::clear_locations
	 */
	public function test_clear_locations() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$zone->clear_locations();

		// Assert.
		$zone_locations = $zone->get_zone_locations();
		$this->assertTrue( empty( $zone_locations ) );
	}

	/**
	 * Test: WC_Shipping_Zone::set_locations
	 */
	public function test_set_locations() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$zone->clear_locations();
		$zone->set_locations(
			array(
				array(
					'code' => 'US',
					'type' => 'country',
				),
				array(
					'code' => '90210',
					'type' => 'postcode',
				),
			)
		);

		// Assert.
		$this->assertEquals(
			$zone->get_zone_locations(),
			array(
				0 => (object) array(
					'code' => 'US',
					'type' => 'country',
				),
				1 => (object) array(
					'code' => '90210',
					'type' => 'postcode',
				),
			)
		);
	}

	/**
	 * Test: WC_Shipping_Zone::save
	 */
	public function test_save() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$zone->set_zone_name( 'I am a fish' );
		$zone->save();
		$zone = WC_Shipping_Zones::get_zone( 1 );

		// Assert.
		$this->assertEquals( $zone->get_zone_name(), 'I am a fish' );
	}

	/**
	 * Test: WC_Shipping_Zone::add_shipping_method
	 */
	public function test_add_shipping_method() {
		// Test.
		$zone = WC_Shipping_Zones::get_zone( 1 );
		$zone->add_shipping_method( 'flat_rate' );
		$zone->add_shipping_method( 'free_shipping' );

		// Assert.
		$methods = $zone->get_shipping_methods();

		// Assert.
		$this->assertTrue( 2 === count( $methods ) );
		$this->assertInstanceOf( 'WC_Shipping_Method', current( $methods ) );
	}

	/**
	 * Test legacy zone functions.
	 *
	 * @since 3.0.0
	 *
	 * @expectedDeprecated WC_Shipping_Zone::read
	 * @expectedDeprecated WC_Shipping_Zone::create
	 * @expectedDeprecated WC_Shipping_Zone::update
	 */
	public function test_wc_shipping_zone_legacy() {
		// Create a single zone.
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Local' );
		$zone->set_zone_order( 1 );
		$zone->add_location( 'GB', 'country' );
		$zone->add_location( 'CB*', 'postcode' );
		// Test invalid zone location.
		$zone->add_location( '1234', 'custom_type' );
		$zone->save();
		$zone_id = $zone->get_id();

		// Count zone locations, there should only be 2 as one is invalid.
		$this->assertSame( 2, count( $zone->get_zone_locations() ) );

		$zone_read = new WC_Shipping_Zone();
		$zone_read->read( $zone_id );
		$this->assertEquals( $zone_id, $zone_read->get_id() );

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Test' );
		$zone->set_zone_order( 2 );
		$zone->create();

		$this->assertEquals( 'Test', $zone->get_zone_name() );
		$this->assertNotEmpty( $zone->get_id() );

		$zone->set_zone_name( 'Test 2' );
		$zone->update();

		$this->assertEquals( 'Test 2', $zone->get_zone_name() );
	}

}
