<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- backcompat nomenclature.

/**
 * Tests the rate WC_Shipping_Flat_Rate emits for a package.
 *
 * The sibling test file covers evaluate_cost() and sanitize_cost() in isolation, with their
 * arguments passed in by hand. These go through calculate_shipping() instead, so they pin what a
 * merchant's saved setting actually charges the shopper.
 *
 * Every expected value here is taken from what the settings screen promises the merchant, in
 * includes/shipping/flat-rate/includes/settings-flat-rate.php, rather than from reading the
 * implementation. Behaviour the screen does not describe is deliberately left untested.
 */
class WC_Shipping_Flat_Rate_Rates_Test extends WC_Unit_Test_Case {

	/**
	 * Shipping zone holding the method under test.
	 *
	 * @var WC_Shipping_Zone
	 */
	private $zone;

	/**
	 * Instance id of the flat rate method in that zone.
	 *
	 * @var int
	 */
	private $instance_id;

	/**
	 * The System Under Test.
	 *
	 * @var WC_Shipping_Flat_Rate
	 */
	private $sut;

	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// WC_Shipping caches the class list on the singleton and no base class resets it, so a
		// class created by an earlier test would otherwise still be the list this one sees.
		WC()->shipping()->shipping_classes = array();

		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Flat rate rates' );
		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		$this->instance_id = $this->zone->add_shipping_method( 'flat_rate' );
	}

	/**
	 * Tear down test case.
	 */
	public function tearDown(): void {
		// Released on the way out as well as in, so the dead terms this file creates are not
		// still memoized for whatever test file runs next in the same process.
		WC()->shipping()->shipping_classes = array();

		parent::tearDown();
	}

	/**
	 * Save instance settings and return the method that reads them.
	 *
	 * Call this after creating any shipping class the test needs. The class cost fields only
	 * exist while classes do, and get_option() silently reads the store-wide settings for keys
	 * that are not instance fields, so building the method first makes the reads go elsewhere.
	 *
	 * @param array $settings Instance settings to store.
	 */
	private function method_with( array $settings ): void {
		update_option(
			'woocommerce_flat_rate_' . $this->instance_id . '_settings',
			array_merge(
				array(
					'title'      => 'Flat rate',
					'tax_status' => 'taxable',
					'type'       => 'class',
				),
				$settings
			)
		);

		$this->sut = new WC_Shipping_Flat_Rate( $this->instance_id );
	}

	/**
	 * Build a package from products.
	 *
	 * @param array $items         Each entry is array( product, quantity, line_total ).
	 * @param float $contents_cost Package contents cost, which feeds the [cost] placeholder.
	 * @return array
	 */
	private function package_of( array $items, float $contents_cost ): array {
		$contents = array();

		foreach ( $items as $index => $item ) {
			$contents[ 'item_' . $index ] = array(
				'data'       => $item['product'],
				'quantity'   => $item['quantity'],
				'line_total' => $item['line_total'],
			);
		}

		return array(
			'contents'      => $contents,
			'contents_cost' => $contents_cost,
			'destination'   => array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '90210',
			),
		);
	}

	/**
	 * Run the configured method against a package and return the rate it emitted.
	 *
	 * @param array $package Package to rate.
	 * @return WC_Shipping_Rate
	 */
	private function rate_for( array $package ): WC_Shipping_Rate {
		$this->sut->calculate_shipping( $package );

		$this->assertCount( 1, $this->sut->rates, 'The method should offer the shopper a rate.' );

		return current( $this->sut->rates );
	}

	/**
	 * Create a product that ships.
	 *
	 * @param string $shipping_class Shipping class slug, or an empty string for none.
	 * @return WC_Product_Simple
	 */
	private function shippable_product( string $shipping_class = '' ): WC_Product_Simple {
		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );

		if ( '' !== $shipping_class ) {
			$term = wp_insert_term( $shipping_class, 'product_shipping_class' );
			$this->assertNotWPError( $term, 'The test fixture should be able to create a shipping class.' );
			$product->set_shipping_class_id( (int) $term['term_id'] );

			WC()->shipping()->shipping_classes = array();
		}

		$product->save();

		return $product;
	}

	/**
	 * The screen calls [qty] "number of items". A shopper is not shipped the items that do not
	 * ship, so a virtual product in the same cart must not raise the price of delivery.
	 *
	 * @testdox The [qty] placeholder counts the items being shipped, not the virtual ones.
	 */
	public function test_qty_placeholder_counts_only_shippable_items(): void {
		$virtual = new WC_Product_Simple();
		$virtual->set_regular_price( '10' );
		$virtual->set_virtual( true );
		$virtual->save();

		$this->method_with( array( 'cost' => '[qty]' ) );
		$package = $this->package_of(
			array(
				array(
					'product'    => $this->shippable_product(),
					'quantity'   => 2,
					'line_total' => 20.0,
				),
				array(
					'product'    => $virtual,
					'quantity'   => 5,
					'line_total' => 50.0,
				),
			),
			70.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 2, $rate->get_cost(), 'Only the two shippable units should be counted.' );
	}

	/**
	 * The screen calls [cost] "total cost of items".
	 *
	 * @testdox The [cost] placeholder is the total cost of the items in the package.
	 */
	public function test_cost_placeholder_is_the_total_cost_of_items(): void {
		$this->method_with( array( 'cost' => '[cost]' ) );
		$package = $this->package_of(
			array(
				array(
					'product'    => $this->shippable_product(),
					'quantity'   => 1,
					'line_total' => 42.5,
				),
			),
			42.5
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 42.5, $rate->get_cost(), '[cost] should resolve to the total cost of the items.' );
	}

	/**
	 * Shipping class costs are described as costs that "can optionally be added based on the
	 * product shipping class", so they are added to the method's own cost.
	 *
	 * @testdox A shipping class cost is added to the method cost for items in that class.
	 */
	public function test_class_cost_is_added_for_items_in_that_class(): void {
		$product = $this->shippable_product( 'flat-rate-heavy' );

		$this->method_with(
			array(
				'cost' => '1',
				'class_cost_' . $product->get_shipping_class_id() => '7',
			)
		);
		$package = $this->package_of(
			array(
				array(
					'product'    => $product,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
			),
			10.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 8, $rate->get_cost(), 'The class cost should be added to the method cost.' );
	}

	/**
	 * Class costs were keyed by slug before 2.5.0 and zone instances only arrived in 2.6.0, so
	 * that older data lives in the method's store-wide settings. A zone instance that has never
	 * saved its own value for the class must still charge it, or a store upgrading from that era
	 * silently stops charging.
	 *
	 * Two mechanisms deliver this today, either of which suffices: the class cost field seeds its
	 * default from the slug-keyed value, and the lookup in calculate_shipping() falls back to the
	 * slug key. This pins the outcome the merchant sees rather than either mechanism, so it fails
	 * only when both are gone. That is deliberate: which of the two survives is not a promise
	 * made to anyone.
	 *
	 * @testdox A class cost saved under the pre-2.5.0 slug key is still charged.
	 */
	public function test_class_cost_saved_under_the_legacy_slug_key_is_still_charged(): void {
		$product = $this->shippable_product( 'flat-rate-fragile' );

		update_option( 'woocommerce_flat_rate_settings', array( 'class_cost_flat-rate-fragile' => '4' ) );

		$this->method_with( array( 'cost' => '1' ) );
		$package = $this->package_of(
			array(
				array(
					'product'    => $product,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
			),
			10.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 5, $rate->get_cost(), 'The slug-keyed class cost should still be honoured.' );
	}

	/**
	 * The screen offers a "No shipping class cost" field, so items carrying no class are charged it.
	 *
	 * @testdox The no-class cost is charged for items that have no shipping class.
	 */
	public function test_no_class_cost_is_charged_for_unclassified_items(): void {
		// A class has to exist somewhere in the store before any class costs are considered.
		$this->shippable_product( 'flat-rate-bulky' );

		$this->method_with(
			array(
				'cost'          => '1',
				'no_class_cost' => '3',
			)
		);
		$package = $this->package_of(
			array(
				array(
					'product'    => $this->shippable_product(),
					'quantity'   => 1,
					'line_total' => 10.0,
				),
			),
			10.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 4, $rate->get_cost(), 'An item without a class should be charged the no-class cost.' );
	}

	/**
	 * The two calculation types are described as "Charge shipping for each shipping class
	 * individually" and "Charge shipping for the most expensive shipping class".
	 *
	 * @testdox Per class charges every class, per order charges only the most expensive one.
	 *
	 * @testWith ["class", 13]
	 *           ["order", 9]
	 *
	 * @param string $type     The calculation type setting.
	 * @param float  $expected Expected rate cost.
	 */
	public function test_calculation_type_decides_how_class_costs_combine( string $type, float $expected ): void {
		$light = $this->shippable_product( 'flat-rate-light' );
		$heavy = $this->shippable_product( 'flat-rate-oversized' );

		$this->method_with(
			array(
				'cost' => '1',
				'type' => $type,
				'class_cost_' . $light->get_shipping_class_id() => '4',
				'class_cost_' . $heavy->get_shipping_class_id() => '8',
			)
		);
		$package = $this->package_of(
			array(
				array(
					'product'    => $light,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
				array(
					'product'    => $heavy,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
			),
			20.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( $expected, $rate->get_cost(), 'Per class should charge both 4 and 8, per order only the 8.' );
	}
}
