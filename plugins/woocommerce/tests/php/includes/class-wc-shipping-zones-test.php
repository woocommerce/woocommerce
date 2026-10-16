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
	 * The zone editor offers a `low...high` postcode range. The destination inside it matches and
	 * one past its top does not.
	 *
	 * @testdox A numeric postcode range matches the postcodes inside it and no others.
	 */
	public function test_a_numeric_postcode_range_matches_inside_and_not_outside(): void {
		$this->zone_with( 'Beverly Hills', '90210...90220', 'postcode' );

		$this->assertSame(
			'Beverly Hills',
			$this->matched_zone_name(
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90215',
				)
			),
			'A postcode inside the range should land in the zone.'
		);
		$this->assertNotSame(
			'Beverly Hills',
			$this->matched_zone_name(
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90230',
				)
			),
			'A postcode past the top of the range should not.'
		);
	}

	/**
	 * A range whose bounds are not plain numbers is compared by turning each into a number, so a
	 * range written with letters still contains the codes between its ends.
	 *
	 * @testdox A postcode range written with letters still matches the codes inside it.
	 */
	public function test_a_postcode_range_with_letters_matches_inside(): void {
		$this->zone_with( 'Aberdeen', 'AB10...AB15', 'postcode' );

		$this->assertSame(
			'Aberdeen',
			$this->matched_zone_name(
				array(
					'country'  => 'GB',
					'state'    => '',
					'postcode' => 'AB12',
				)
			),
			'A code inside the lettered range should land in the zone.'
		);
		$this->assertNotSame(
			'Aberdeen',
			$this->matched_zone_name(
				array(
					'country'  => 'GB',
					'state'    => '',
					'postcode' => 'AB20',
				)
			),
			'A code past the top should not.'
		);
	}

	/**
	 * A bare `*` is the wildcard the editor offers for "any postcode", so the zone matches every
	 * address that reaches its country, and only its country.
	 *
	 * @testdox A bare wildcard postcode matches any postcode in the zone's country, but not outside it.
	 */
	public function test_a_bare_wildcard_postcode_matches_any_postcode(): void {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Anywhere in GB' );
		$zone->set_zone_order( 1 );
		$zone->add_location( 'GB', 'country' );
		$zone->add_location( '*', 'postcode' );
		$zone->save();

		$this->assertSame(
			'Anywhere in GB',
			$this->matched_zone_name(
				array(
					'country'  => 'GB',
					'state'    => '',
					'postcode' => 'SW1A 1AA',
				)
			),
			'The bare wildcard should accept any postcode.'
		);
		$this->assertNotSame(
			'Anywhere in GB',
			$this->matched_zone_name(
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => 'SW1A 1AA',
				)
			),
			'The same postcode outside GB should not match, since the country rule still restricts the zone.'
		);
	}

	/**
	 * A shopper types a postcode with a space or a hyphen; it is normalised before matching, so it
	 * still lands in the zone whose rule is written without them.
	 *
	 * @testdox A postcode typed with a space or hyphen is matched with those removed.
	 */
	public function test_a_postcode_is_matched_with_spaces_and_hyphens_removed(): void {
		$this->zone_with( 'Cambridge', 'CB231GG', 'postcode' );

		$this->assertSame(
			'Cambridge',
			$this->matched_zone_name(
				array(
					'country'  => 'GB',
					'state'    => '',
					'postcode' => 'CB23 1GG',
				)
			),
			'A space in the typed postcode should not keep it out of the zone.'
		);
		$this->assertSame(
			'Cambridge',
			$this->matched_zone_name(
				array(
					'country'  => 'GB',
					'state'    => '',
					'postcode' => 'cb23-1gg',
				)
			),
			'Nor should a hyphen or lower case.'
		);
	}

	/**
	 * A zone with no region rules is what the editor calls "Everywhere", and it matches every
	 * address rather than none.
	 *
	 * @testdox A zone with no regions matches every address.
	 */
	public function test_a_zone_with_no_regions_matches_every_address(): void {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Everywhere' );
		$zone->set_zone_order( 1 );
		$zone->save();

		$this->assertSame(
			'Everywhere',
			$this->matched_zone_name(
				array(
					'country'  => 'JP',
					'state'    => '',
					'postcode' => '100-0001',
				)
			),
			'A region-less zone should match an address nothing else covers.'
		);
	}

	/**
	 * When nothing a merchant drew matches, the address falls through to the Rest of the World zone
	 * (id 0) rather than to no zone at all.
	 *
	 * @testdox An address no zone covers falls through to the Rest of the World zone.
	 */
	public function test_an_unmatched_address_falls_through_to_the_rest_of_the_world_zone(): void {
		$this->zone_with( 'Only Germany', 'DE', 'country' );

		$matched = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90210',
				),
			)
		);

		$this->assertSame( 0, $matched->get_id(), 'An uncovered address should resolve to the Rest of the World zone.' );
	}

	/**
	 * Two zones at the same order both match the address. The matcher breaks the tie on the lower
	 * zone id, and the shadow warning that tells the merchant one zone hides the other ranks them
	 * the same way, so the warning names the zone that really loses.
	 *
	 * @testdox When two zones share an order the lower id wins, and the shadow warning agrees.
	 */
	public function test_when_two_zones_share_an_order_the_lower_id_wins(): void {
		$first  = $this->zone_with( 'First US', 'US', 'country', 1 );
		$second = $this->zone_with( 'Second US', 'US', 'country', 1 );

		$this->assertLessThan( $second->get_id(), $first->get_id(), 'The first zone saved should hold the lower id.' );

		$matched = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90210',
				),
			)
		);

		$this->assertSame( $first->get_id(), $matched->get_id(), 'With orders tied, the lower zone id should win.' );

		// The admin warning ranks the pair the same way, so it flags the zone that really loses.
		$warned = array();
		foreach ( WC_Shipping_Zones::get_zones_with_order_conflict_warnings() as $zone ) {
			if ( ! empty( $zone['zone_order_conflict_warning'] ) ) {
				$warned[] = $zone['zone_name'];
			}
		}
		$this->assertContains( 'Second US', $warned, 'The higher-id zone should be warned as shadowed.' );
		$this->assertNotContains( 'First US', $warned, 'The winning zone should carry no warning.' );
	}

	/**
	 * A zone defined only by a postcode, with no country, matches that postcode wherever the
	 * shopper says they are, which is a sharper edge of the editor than a merchant may expect.
	 *
	 * @testdox A postcode-only zone is reachable from a country the merchant did not intend.
	 */
	public function test_a_postcode_only_zone_is_reachable_from_another_country(): void {
		$this->zone_with( 'Postcode only', '12345', 'postcode' );

		$this->assertSame(
			'Postcode only',
			$this->matched_zone_name(
				array(
					'country'  => 'FR',
					'state'    => '',
					'postcode' => '12345',
				)
			),
			'A postcode-only zone matches the postcode in any country.'
		);
	}

	/**
	 * Matching is decided by zone order, not by how specific a zone is: a broad zone placed above a
	 * narrow one that also matches shadows it, and moving the broad zone below the narrow one hands
	 * the address back to the narrow zone.
	 *
	 * @testdox The zone listed first wins, so a broad zone above a narrow one shadows it.
	 */
	public function test_a_broad_zone_above_a_narrow_one_shadows_it(): void {
		$broad = $this->zone_with( 'All of the US', 'US', 'country', 1 );
		$this->zone_with( 'Beverly Hills only', '90210', 'postcode', 2 );

		$destination = array(
			'country'  => 'US',
			'state'    => 'CA',
			'postcode' => '90210',
		);

		$this->assertSame(
			'All of the US',
			$this->matched_zone_name( $destination ),
			'An address both zones match should land in the one listed first, broad though it is.'
		);

		// Move the broad zone below the narrow one; the same address now lands in the narrow zone.
		$broad->set_zone_order( 3 );
		$broad->save();

		$this->assertSame(
			'Beverly Hills only',
			$this->matched_zone_name( $destination ),
			'With the narrow zone now listed first, it wins, so order decides rather than specificity.'
		);
	}

	/**
	 * The admin warns that a zone "will not be matched because another covers the same region earlier
	 * in the list." That warning is true of the matcher, not only of the helper that prints it: a
	 * broad zone above a narrower one it fully covers makes the narrower zone unreachable, so no
	 * address the narrower zone describes ever lands in it.
	 *
	 * @testdox A zone the warning flags as shadowed is never returned by the matcher.
	 */
	public function test_a_shadowed_zone_is_never_matched(): void {
		$this->zone_with( 'All of the US', 'US', 'country', 1 );
		$this->zone_with( 'California only', 'US:CA', 'state', 2 );

		$warned = array();
		foreach ( WC_Shipping_Zones::get_zones_with_order_conflict_warnings() as $zone ) {
			if ( ! empty( $zone['zone_order_conflict_warning'] ) ) {
				$warned[] = $zone['zone_name'];
			}
		}
		$this->assertContains( 'California only', $warned, 'The narrower zone should be flagged as shadowed.' );

		// The matcher agrees: a Californian address lands in the broad zone, never the flagged one.
		$matched = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90210',
				),
			)
		);
		$this->assertSame( 'All of the US', $matched->get_zone_name(), 'The broad zone that shadows it wins instead, never the flagged one.' );
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

	/**
	 * Build a zone with one location rule.
	 *
	 * @param string $name  Zone name, used to assert which one matched.
	 * @param string $code  Location code, e.g. 'US' or '90210...90220'.
	 * @param string $type  Location type: 'country', 'state', 'continent' or 'postcode'.
	 * @param int    $order Zone order.
	 * @return WC_Shipping_Zone
	 */
	private function zone_with( string $name, string $code, string $type, int $order = 1 ): WC_Shipping_Zone {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );
		$zone->add_location( $code, $type );
		$zone->save();
		return $zone;
	}

	/**
	 * The name of the zone an address lands in.
	 *
	 * @param array $destination Destination with 'country', 'state', 'postcode' keys.
	 * @return string
	 */
	private function matched_zone_name( array $destination ): string {
		return WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => $destination ) )->get_zone_name();
	}
}
