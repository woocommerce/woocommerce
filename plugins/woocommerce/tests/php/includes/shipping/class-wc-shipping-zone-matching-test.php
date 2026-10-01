<?php
/**
 * Tests for which shipping zone an address lands in.
 *
 * @package WooCommerce\Tests\Shipping
 */

declare( strict_types = 1 );

/**
 * Which zone a package matches, through WC_Shipping_Zones::get_zone_matching_package().
 *
 * The region rules a merchant draws (country, state, continent, postcode, postcode ranges and
 * wildcards) decide this, and the expectations come from what the zone editor offers. The legacy
 * `WC_Tests_Shipping_Zones` pins country, state, continent and a prefix wildcard; this covers the
 * rules it leaves out, each with an address inside the rule and one outside it.
 */
class WC_Shipping_Zone_Matching_Test extends WC_Unit_Test_Case {

	/**
	 * Start from no custom zones, so each test's own zone is the only thing that can match and an
	 * unmatched address falls through to the Rest of the World zone rather than a leftover one.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			WC_Shipping_Zones::delete_zone( $zone['id'] );
		}
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
	 * address that reaches its country.
	 *
	 * @testdox A bare wildcard postcode matches any postcode in the zone's country.
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
		$broad  = $this->zone_with( 'All of the US', 'US', 'country', 1 );
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
}
