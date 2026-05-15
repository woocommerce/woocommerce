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
	 * @testdox get_zone_id_from_package() returns a state-filtered zone for an optional-state country when the destination state is blank.
	 */
	public function test_get_zone_id_from_package_matches_state_zone_when_optional_state_is_blank() {
		// Auckland-specific state-filtered zone, lower zone_order so it wins ordering ties.
		$state_zone = new WC_Shipping_Zone();
		$state_zone->set_zone_name( 'NZ Auckland' );
		$state_zone->set_zone_order( 1 );
		$state_zone->add_location( 'NZ:NZ-AUK', 'state' );
		$state_zone->save();

		// Country-wide NZ fallback zone, higher zone_order.
		$country_zone = new WC_Shipping_Zone();
		$country_zone->set_zone_name( 'NZ Fallback' );
		$country_zone->set_zone_order( 2 );
		$country_zone->add_location( 'NZ', 'country' );
		$country_zone->save();

		$package = array(
			'destination' => array(
				// Optional in NZ — left blank, as Apple/Google Pay would.
				'country'  => 'NZ',
				'state'    => '',
				'postcode' => '1021',
			),
		);

		$datastore = new WC_Shipping_Zone_Data_Store();
		$matched   = (int) $datastore->get_zone_id_from_package( $package );

		$this->assertSame(
			$state_zone->get_id(),
			$matched,
			'Optional-state countries with a blank state should still match more specific state-filtered zones.'
		);

		$state_zone->delete();
		$country_zone->delete();
	}

	/**
	 * @testdox get_zone_id_from_package() still requires an exact state match for countries where the state field is required.
	 */
	public function test_get_zone_id_from_package_requires_state_for_required_state_country() {
		// California-specific zone — US states are required, so a blank state must NOT match.
		$state_zone = new WC_Shipping_Zone();
		$state_zone->set_zone_name( 'US California' );
		$state_zone->set_zone_order( 1 );
		$state_zone->add_location( 'US:CA', 'state' );
		$state_zone->save();

		// US country fallback.
		$country_zone = new WC_Shipping_Zone();
		$country_zone->set_zone_name( 'US Fallback' );
		$country_zone->set_zone_order( 2 );
		$country_zone->add_location( 'US', 'country' );
		$country_zone->save();

		$package = array(
			'destination' => array(
				'country'  => 'US',
				'state'    => '',
				'postcode' => '94016',
			),
		);

		$datastore = new WC_Shipping_Zone_Data_Store();
		$matched   = (int) $datastore->get_zone_id_from_package( $package );

		$this->assertSame(
			$country_zone->get_id(),
			$matched,
			'Required-state countries should not wildcard-match state-filtered zones when the state is blank.'
		);

		$state_zone->delete();
		$country_zone->delete();
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
