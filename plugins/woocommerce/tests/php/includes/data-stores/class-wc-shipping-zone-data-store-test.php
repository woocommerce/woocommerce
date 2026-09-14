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

	/**
	 * @testdox Shipping zones do not load meta from wp_postmeta even when a post with matching ID exists.
	 */
	public function test_shipping_zone_does_not_load_post_meta() {
		// Create a shipping zone.
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Test Zone' );
		$zone->save();
		$zone_id = $zone->get_id();

		// Create a post with the same ID and add meta to it.
		global $wpdb;
		$wpdb->insert(
			$wpdb->posts,
			array(
				'ID'          => $zone_id,
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Test Post',
			)
		);
		add_post_meta( $zone_id, 'test_meta_key', 'test_meta_value' );

		// Load the shipping zone fresh and verify it has no meta data.
		$fresh_zone = new WC_Shipping_Zone( $zone_id );
		$meta_data  = $fresh_zone->get_meta_data();

		$this->assertEmpty( $meta_data, 'Shipping zone should not have loaded any meta data from wp_postmeta.' );

		// Clean up.
		wp_delete_post( $zone_id, true );
		$zone->delete();
	}

	/**
	 * @testdox read_meta() returns an empty array for shipping zones.
	 */
	public function test_read_meta_returns_empty_array() {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Test Zone' );
		$zone->save();

		$datastore = new WC_Shipping_Zone_Data_Store();
		$result    = $datastore->read_meta( $zone );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );

		$zone->delete();
	}

	/**
	 * @testdox add_meta() returns 0 as shipping zones do not support meta storage.
	 */
	public function test_add_meta_returns_zero() {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Test Zone' );
		$zone->save();

		$datastore = new WC_Shipping_Zone_Data_Store();
		$meta      = (object) array(
			'key'   => 'test_key',
			'value' => 'test_value',
		);
		$result    = $datastore->add_meta( $zone, $meta );

		$this->assertSame( 0, $result );

		$zone->delete();
	}
}
