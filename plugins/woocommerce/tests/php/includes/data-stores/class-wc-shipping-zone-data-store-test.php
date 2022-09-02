<?php

/**
 * Class WC_Shipping_Zone_Data_Store_CPT_Test.
 */
class WC_Shipping_Zone_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox read() sets properties for normal, non-zero shipping zones.
	 */
	public function test_read_for_normal_shipping_zones() {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'California' );
		$zone->set_zone_order( 3 );
		$zone->add_location( 'US:CA', 'state' );
		$zone->save();

		$datastore = new WC_Shipping_Zone_Data_Store();
		$datastore->read( $zone );
		$this->assertSame( 'California', $zone->get_zone_name() );
		$this->assertSame( 3, $zone->get_zone_order() );
		$this->assertGreaterThan( 0, did_action( 'woocommerce_shipping_zone_loaded' ) );
	}

	/**
	 * @testdox read() sets default properties for shipping zone with ID 0.
	 */
	public function test_read_for_shipping_zone_zero() {
		$zone = new WC_Shipping_Zone( 0 );

		$datastore = new WC_Shipping_Zone_Data_Store();
		$datastore->read( $zone );
		$this->assertSame( 0, $zone->get_zone_order() );
		$this->assertGreaterThan( 0, did_action( 'woocommerce_shipping_zone_loaded' ) );
	}

	/**
	 * @testdox read() throws an exception if the zone ID cannot be found.
	 */
	public function test_read_with_invalid_zone_id() {
		$this->expectException( \Exception::class );

		$zone = new WC_Shipping_Zone( -1 );

		$datastore = new WC_Shipping_Zone_Data_Store();
		$datastore->read( $zone );
	}
}
