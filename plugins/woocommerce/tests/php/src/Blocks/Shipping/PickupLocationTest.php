<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Shipping\PickupLocation;
use WC_Unit_Test_Case;

/**
 * Tests the options PickupLocation offers the shopper.
 *
 * Every expected value here is taken from what the Local pickup settings screen promises the
 * merchant: that enabling it makes pickup "appear as an option on the block based checkout", that
 * the title is "the shipping method title shown to customers", that the cost is an "Optional cost
 * to charge for local pickup" and that "by default, the local pickup shipping method is free",
 * and that each location carries a required Location name and its Pickup details.
 *
 * Two expectations here are not merchant promises. The rate id format is one, pinned only
 * relatively because it is what a shopper's stored choice points at. The `_pickup_location_address`
 * meta is the other: it is hidden internal data the order uses to work out its tax location.
 *
 * One expectation is recorded rather than endorsed. `has_valid_pickup_location()` short-circuits to
 * valid as soon as city, postcode and state are filled in, without asking the country what it needs,
 * so a location saved with no street line counts as having a usable address even in a country that
 * requires one. Whether that is right is a question for the team rather than for a test: a stall
 * inside a mall may genuinely have no street line, and the screen does not say. It is reported, and
 * `test_records_that_a_missing_street_line_still_counts_as_usable()` pins what happens today so a
 * change to it is a deliberate edit rather than a surprise.
 */
class PickupLocationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PickupLocation
	 */
	private $sut;

	/**
	 * Configure local pickup the way the settings screen would, then build the method.
	 *
	 * @param array $locations Pickup locations.
	 * @param array $settings  Method settings, merged over the defaults.
	 */
	private function pickup_configured_with( array $locations, array $settings = array() ): void {
		update_option(
			'woocommerce_pickup_location_settings',
			array_merge(
				array(
					'enabled'    => 'yes',
					'title'      => 'Pickup',
					'tax_status' => 'taxable',
					'cost'       => '',
				),
				$settings
			)
		);
		update_option( 'pickup_location_pickup_locations', $locations );

		$this->sut = new PickupLocation();
	}

	/**
	 * Build a location as the settings screen stores one.
	 *
	 * @param string $name    Location name.
	 * @param bool   $enabled Whether the location is switched on.
	 * @param array  $address Address, defaulting to a complete one.
	 * @param string $details Pickup details.
	 * @return array
	 */
	private function location( string $name, bool $enabled = true, array $address = array(), string $details = '' ): array {
		return array(
			'name'    => $name,
			'address' => $address ? $address : array(
				'address_1' => '1 Market St',
				'city'      => 'San Francisco',
				'state'     => 'CA',
				'postcode'  => '94105',
				'country'   => 'US',
			),
			'details' => $details,
			'enabled' => $enabled,
		);
	}

	/**
	 * Ask the method what it offers for an ordinary package.
	 *
	 * @return array Rates keyed by rate id.
	 */
	private function offered_rates(): array {
		// Unsaved on purpose: add_rate() only reads the name and the quantity off the package.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Fixture product' );

		$this->sut->calculate_shipping(
			array(
				'contents'      => array(
					'item' => array(
						'data'     => $product,
						'quantity' => 1,
					),
				),
				'contents_cost' => 10.0,
				'destination'   => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '94105',
				),
			)
		);

		return $this->sut->rates;
	}

	/**
	 * Ask the method for the one option a single-location store should offer.
	 *
	 * @return \WC_Shipping_Rate
	 */
	private function single_offered_rate(): \WC_Shipping_Rate {
		$rates = $this->offered_rates();
		$this->assertCount( 1, $rates, 'One open branch should give the shopper one option.' );

		return current( $rates );
	}

	/**
	 * @testdox Each enabled location is offered as its own option, named so the shopper can tell the branches apart.
	 */
	public function test_every_enabled_location_is_offered(): void {
		$this->pickup_configured_with(
			array(
				$this->location( 'Downtown' ),
				$this->location( 'Airport' ),
			)
		);

		$rates = $this->offered_rates();

		$this->assertCount( 2, $rates, 'Two open branches should give the shopper two options.' );

		$names = array_map(
			static function ( $rate ) {
				return $rate->get_meta_data()['pickup_location'];
			},
			array_values( $rates )
		);

		$this->assertSame( array( 'Downtown', 'Airport' ), $names, 'Each option should carry its own location name.' );

		$labels = array_map(
			static function ( $rate ) {
				return $rate->get_label();
			},
			array_values( $rates )
		);

		$this->assertStringContainsString( 'Downtown', $labels[0], 'The first option should name its own branch to the shopper.' );
		$this->assertStringContainsString( 'Airport', $labels[1], 'And the second should name its own.' );
		$this->assertNotSame( $labels[0], $labels[1], 'Two options a shopper cannot tell apart are no better than one.' );
	}

	/**
	 * @testdox A location that is switched off is not offered.
	 */
	public function test_a_disabled_location_is_not_offered(): void {
		$this->pickup_configured_with(
			array(
				$this->location( 'Downtown' ),
				$this->location( 'Closed for refurbishment', false ),
			)
		);

		$rates = $this->offered_rates();

		$this->assertCount( 1, $rates, 'A location switched off should not be offered.' );
		$this->assertSame( 'Downtown', current( $rates )->get_meta_data()['pickup_location'], 'The open branch is the one that survives.' );
	}

	/**
	 * The screen lets the method be enabled before any location exists, so this is a state a
	 * merchant passes through rather than an error.
	 *
	 * @testdox With no location open there is nothing to offer, even with pickup enabled.
	 */
	public function test_no_open_location_means_no_option(): void {
		$this->pickup_configured_with( array( $this->location( 'Closed', false ) ) );

		$this->assertEmpty( $this->offered_rates(), 'Pickup enabled with every branch closed should offer nothing.' );
	}

	/**
	 * "By default, the local pickup shipping method is free", and the cost, when set, is charged
	 * for local pickup rather than per branch.
	 *
	 * @testdox Pickup is free by default, and a configured cost is charged at every branch.
	 */
	public function test_cost_is_free_by_default_and_otherwise_shared(): void {
		$this->pickup_configured_with(
			array(
				$this->location( 'Downtown' ),
				$this->location( 'Airport' ),
			)
		);

		$rates = $this->offered_rates();
		$this->assertCount( 2, $rates, 'Both branches should be offered before their cost is examined.' );

		foreach ( $rates as $rate ) {
			$this->assertSame( '0', $rate->get_cost(), 'With no cost set, pickup should be free.' );
		}

		$this->pickup_configured_with(
			array(
				$this->location( 'Downtown' ),
				$this->location( 'Airport' ),
			),
			array( 'cost' => '5' )
		);

		$rates = $this->offered_rates();
		$this->assertCount( 2, $rates, 'Both branches should still be offered once a cost is set.' );

		foreach ( $rates as $rate ) {
			$this->assertSame( '5', $rate->get_cost(), 'A configured cost should be charged whichever branch is chosen.' );
		}
	}

	/**
	 * @testdox The title shown to customers appears on every option.
	 */
	public function test_the_customer_facing_title_appears_on_the_option(): void {
		$this->pickup_configured_with(
			array( $this->location( 'Downtown' ) ),
			array( 'title' => 'Collect in store' )
		);

		$label = $this->single_offered_rate()->get_label();

		$this->assertStringContainsString( 'Collect in store', $label, 'The option should carry the title the merchant set for customers.' );
		$this->assertStringContainsString( 'Downtown', $label, 'And the branch name, or a shopper cannot tell two branches apart.' );
	}

	/**
	 * @testdox The pickup details the merchant wrote travel with the option.
	 */
	public function test_pickup_details_travel_with_the_option(): void {
		$this->pickup_configured_with(
			array( $this->location( 'Downtown', true, array(), 'Ring the bell at the side door.' ) )
		);

		$this->assertSame(
			'Ring the bell at the side door.',
			$this->single_offered_rate()->get_meta_data()['pickup_details'],
			'Details the merchant wrote for the shopper should reach the shopper.'
		);
	}

	/**
	 * A branch is a place the shopper can walk into, so it stays on offer whatever state its
	 * address is in. What the address decides is whether the shopper is told where to go, and
	 * whether the order has somewhere to work its tax out from.
	 *
	 * An address counts as usable when it holds what that country actually asks for. The editor
	 * collects Address, City, Country / State and Postcode / ZIP, and only the Location name is
	 * required of the merchant, so an address is not judged against a fixed list of fields.
	 *
	 * @testdox A location is always offered, and carries an address only when its country has what it needs.
	 *
	 * @dataProvider provider_address_shapes
	 *
	 * @param array  $address    The address the merchant saved.
	 * @param bool   $is_usable  Whether the shopper should be given the address.
	 * @param string $why        What makes this address the shape it is.
	 */
	public function test_an_address_is_judged_against_what_its_country_asks_for( array $address, bool $is_usable, string $why ): void {
		$this->pickup_configured_with( array( $this->location( 'Branch', true, $address ) ) );

		$rates = $this->offered_rates();
		$this->assertCount( 1, $rates, 'The branch should be offered whatever its address looks like: ' . $why );

		$meta = current( $rates )->get_meta_data();
		$this->assertSame( 'Branch', $meta['pickup_location'], 'It should be named either way.' );

		if ( $is_usable ) {
			$this->assertStringContainsString( $address['city'], $meta['pickup_address'], 'The shopper should be told where to collect from: ' . $why );
			$this->assertStringContainsString( $address['address_1'], $meta['pickup_address'], 'Including the street line the merchant saved.' );
			$this->assertSame( $address, $meta['_pickup_location_address'], 'The order should keep the address it will work tax out from.' );
		} else {
			$this->assertSame( '', $meta['pickup_address'], 'A half-built address should be left empty rather than shown: ' . $why );
			$this->assertSame( array(), $meta['_pickup_location_address'], 'And nothing should be kept for the order to tax against.' );
		}
	}

	/**
	 * Address shapes, and whether they give the shopper somewhere to go.
	 *
	 * @return array
	 */
	public function provider_address_shapes(): array {
		$us = array(
			'address_1' => '1 Market St',
			'city'      => 'San Francisco',
			'state'     => 'CA',
			'postcode'  => '94105',
			'country'   => 'US',
		);

		return array(
			'a complete address'               => array( $us, true, 'everything filled in' ),
			'no country at all'                => array( array_merge( $us, array( 'country' => '' ) ), false, 'a country is the one thing always needed' ),
			'a country that wants a postcode'  => array( array_merge( $us, array( 'postcode' => '' ) ), false, 'the US asks for a postcode and none was given' ),
			'a country that wants no postcode' => array(
				array(
					'address_1' => '1 Sheikh Zayed Rd',
					'city'      => 'Dubai',
					'state'     => '',
					'postcode'  => '',
					'country'   => 'AE',
				),
				true,
				'the UAE asks for neither a postcode nor a state',
			),
		);
	}

	/**
	 * Rate ids are the array positions of the saved locations, so what an id means depends on the
	 * array holding still. Switching a branch off leaves it in place and the others keep their ids.
	 *
	 * @testdox Switching one location off does not change which option the others are.
	 */
	public function test_switching_one_location_off_does_not_renumber_the_others(): void {
		$this->pickup_configured_with(
			array(
				$this->location( 'Downtown' ),
				$this->location( 'Airport' ),
				$this->location( 'Harbour' ),
			)
		);

		$before = array();
		$rates  = $this->offered_rates();
		$this->assertCount( 3, $rates, 'All three branches should be offered to begin with.' );

		foreach ( $rates as $id => $rate ) {
			$before[ $rate->get_meta_data()['pickup_location'] ] = $id;
		}

		$this->pickup_configured_with(
			array(
				$this->location( 'Downtown' ),
				$this->location( 'Airport', false ),
				$this->location( 'Harbour' ),
			)
		);

		$after = array();
		$rates = $this->offered_rates();
		$this->assertCount( 2, $rates, 'Closing one branch should leave two.' );

		foreach ( $rates as $id => $rate ) {
			$after[ $rate->get_meta_data()['pickup_location'] ] = $id;
		}

		$this->assertSame( $before['Downtown'], $after['Downtown'], 'Downtown should still be the same option.' );
		$this->assertSame( $before['Harbour'], $after['Harbour'], 'Harbour should still be the same option, not take over the closed branch.' );
		$this->assertNotSame( $after['Downtown'], $after['Harbour'], 'And the two should remain separate options.' );
	}

	/**
	 * The screen says local pickup "will appear as an option on the block based checkout" when it
	 * is enabled, which is the whole point of the switch.
	 *
	 * @testdox The method is offered only while local pickup is switched on.
	 *
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $enabled  The stored setting.
	 * @param bool   $expected Whether the method should offer itself at all.
	 */
	public function test_the_method_is_offered_only_while_it_is_switched_on( string $enabled, bool $expected ): void {
		$this->pickup_configured_with( array( $this->location( 'Downtown' ) ), array( 'enabled' => $enabled ) );

		$this->assertSame(
			$expected,
			$this->sut->is_available( array( 'destination' => array( 'country' => 'US' ) ) ),
			'Local pickup switched ' . $enabled . '.'
		);
	}

	/**
	 * The method answers for itself rather than for any one branch, so nothing about the branches
	 * can hold it back. A store that has not added one yet, and one whose only branch is closed,
	 * are both still stores where local pickup is switched on. The sibling test covers the switch
	 * being off, which needs no locations to say anything.
	 *
	 * @testdox Being offered follows the switch, and no branch can hold it back.
	 */
	public function test_the_switch_decides_on_its_own_whatever_locations_exist(): void {
		$package = array( 'destination' => array( 'country' => 'US' ) );

		$this->pickup_configured_with( array(), array( 'enabled' => 'yes' ) );
		$this->assertTrue( $this->sut->is_available( $package ), 'With nowhere to collect from yet, the method is still the one that was switched on.' );

		$this->pickup_configured_with( array( $this->location( 'Downtown', false ) ), array( 'enabled' => 'yes' ) );
		$this->assertTrue( $this->sut->is_available( $package ), 'A branch being closed is not the method being switched off.' );
	}

	/**
	 * The settings screen keeps limited HTML in the pickup details, so a merchant can save markup
	 * there. What the shopper is handed on the rate is plain text either way: `add_meta_data()`
	 * cleans every value it stores. The wording survives, the markup does not.
	 *
	 * @testdox Markup a merchant saved in the pickup details does not travel on the rate.
	 */
	public function test_markup_in_the_pickup_details_does_not_reach_the_rate(): void {
		$this->pickup_configured_with(
			array( $this->location( 'Downtown', true, array(), 'Ring the <strong>side</strong> door.' ) )
		);

		$meta = $this->single_offered_rate()->get_meta_data();

		$this->assertArrayHasKey( 'pickup_details', $meta, 'The details should reach the shopper.' );
		$this->assertSame( 'Ring the side door.', $meta['pickup_details'], 'With the wording intact and the markup gone.' );
	}

	/**
	 * Records the behaviour this file's docblock reports to the team, rather than endorsing it.
	 * `has_valid_pickup_location()` stops as soon as city, postcode and state are filled and never
	 * asks the country whether it also wants a street line, so a US location saved without one is
	 * treated as having a usable address.
	 *
	 * @testdox Today, a location with no street line still counts as having a usable address.
	 */
	public function test_records_that_a_missing_street_line_still_counts_as_usable(): void {
		$this->pickup_configured_with(
			array(
				$this->location(
					'Mall stand',
					true,
					array(
						'address_1' => '',
						'city'      => 'San Francisco',
						'state'     => 'CA',
						'postcode'  => '94105',
						'country'   => 'US',
					)
				),
			)
		);

		$meta = $this->single_offered_rate()->get_meta_data();

		$this->assertStringContainsString( 'San Francisco', $meta['pickup_address'], 'Recorded behaviour: the address is treated as usable even though the US asks for a street line.' );
	}
}
