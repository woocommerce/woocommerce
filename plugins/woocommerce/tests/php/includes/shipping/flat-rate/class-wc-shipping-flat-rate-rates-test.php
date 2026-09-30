<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps -- backcompat nomenclature.

/**
 * Tests the rate WC_Shipping_Flat_Rate emits for a package.
 *
 * The sibling test file covers evaluate_cost() and sanitize_cost() in isolation, with their
 * arguments passed in by hand. These go through calculate_shipping() instead, so they pin what a
 * merchant's saved setting actually charges the shopper.
 *
 * Most expected values are taken from what the settings screen promises the merchant, in
 * includes/shipping/flat-rate/includes/settings-flat-rate.php, rather than from reading the
 * implementation.
 *
 * The sibling file holds the name this class would otherwise take, hence the suffix here.
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
		try {
			// Released on the way out as well as in, so the dead terms this file creates are not
			// still memoized for whatever test file runs next in the same process.
			WC()->shipping()->shipping_classes = array();
		} finally {
			parent::tearDown();
		}
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
	 * @param float  $weight         Product weight, or zero for none.
	 * @param bool   $virtual        Whether the product is virtual, so nothing is shipped for it.
	 * @return WC_Product_Simple
	 */
	private function shippable_product( string $shipping_class = '', float $weight = 0, bool $virtual = false ): WC_Product_Simple {
		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->set_virtual( $virtual );

		if ( $weight > 0 ) {
			$product->set_weight( (string) $weight );
		}

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
	 * get_package_item_qty() re-checks needs_shipping() rather than trusting the package, so an
	 * item that does not need shipping is not counted even when the package contains one.
	 *
	 * @testdox The [qty] placeholder skips an item that does not need shipping.
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
		// The dearer class is listed first, so simply taking the last class would not be mistaken
		// for taking the most expensive one.
		$package = $this->package_of(
			array(
				array(
					'product'    => $heavy,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
				array(
					'product'    => $light,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
			),
			20.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( $expected, $rate->get_cost(), 'Per class should charge both 4 and 8, per order only the 8.' );
	}

	/**
	 * The Cost field defaults to 0, so a shop that never touches it is offering free delivery
	 * rather than no delivery.
	 *
	 * @testdox A cost of zero offers the shopper a rate priced zero.
	 */
	public function test_a_cost_of_zero_offers_a_free_rate(): void {
		$this->method_with( array( 'cost' => '0' ) );
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

		$this->assertEquals( 0, $rate->get_cost(), 'A cost of zero should be a free rate, not no rate.' );
	}

	/**
	 * Each class cost field shows "N/A" as its placeholder, so a class the merchant left blank
	 * adds nothing rather than falling back to some other class's cost.
	 *
	 * @testdox A class left blank adds nothing, while the classes that were filled in still charge.
	 */
	public function test_a_blank_class_cost_adds_nothing(): void {
		$charged = $this->shippable_product( 'flat-rate-charged' );
		$blank   = $this->shippable_product( 'flat-rate-blank' );

		$this->method_with(
			array(
				'cost' => '1',
				'class_cost_' . $charged->get_shipping_class_id() => '6',
				'class_cost_' . $blank->get_shipping_class_id() => '',
			)
		);
		$package = $this->package_of(
			array(
				array(
					'product'    => $charged,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
				array(
					'product'    => $blank,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
			),
			20.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 7, $rate->get_cost(), 'The class carrying a cost should add it; the blank one should not turn into some other value.' );
	}

	/**
	 * The no-class cost field is blank by default, so an unclassified item costs nothing extra
	 * until the merchant says otherwise.
	 *
	 * @testdox With the no-class cost left blank an unclassified item adds nothing.
	 */
	public function test_a_blank_no_class_cost_adds_nothing(): void {
		// A class has to exist somewhere in the store before any class costs are considered.
		$this->shippable_product( 'flat-rate-somewhere' );

		$this->method_with(
			array(
				'cost'          => '1',
				'no_class_cost' => '',
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

		$this->assertEquals( 1, $rate->get_cost(), 'A blank no-class cost should not turn into some other value.' );
	}

	/**
	 * Each class cost field carries the same placeholder description as the method cost, so the
	 * placeholders have to mean "this class" there. A shopper buying three of one class and one
	 * of another must not be charged for four of each.
	 *
	 * @testdox A placeholder in a class cost counts only the items in that class.
	 *
	 * @testWith ["[qty]", 3]
	 *           ["[cost]", 30]
	 *           ["[weight]", 6]
	 *
	 * @param string $class_cost Cost formula saved against the class.
	 * @param float  $expected   Expected rate cost.
	 */
	public function test_a_class_cost_placeholder_counts_only_that_class( string $class_cost, float $expected ): void {
		$scoped = $this->shippable_product( 'flat-rate-scoped', 2 );
		$other  = $this->shippable_product( 'flat-rate-other', 4 );

		$this->method_with(
			array(
				'cost' => '0',
				'class_cost_' . $scoped->get_shipping_class_id() => $class_cost,
				'class_cost_' . $other->get_shipping_class_id() => '',
			)
		);
		$package = $this->package_of(
			array(
				array(
					'product'    => $scoped,
					'quantity'   => 3,
					'line_total' => 30.0,
				),
				array(
					'product'    => $other,
					'quantity'   => 1,
					'line_total' => 70.0,
				),
			),
			100.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals(
			$expected,
			$rate->get_cost(),
			$class_cost . ' in a class cost should see only that class, not the whole package.'
		);
	}

	/**
	 * The method cost field documents [weight] alongside the other placeholders.
	 *
	 * @testdox The [weight] placeholder is the total weight of the package.
	 */
	public function test_weight_placeholder_is_the_total_weight_of_the_package(): void {
		$this->method_with( array( 'cost' => '[weight]' ) );
		$package = $this->package_of(
			array(
				array(
					'product'    => $this->shippable_product( '', 2 ),
					'quantity'   => 3,
					'line_total' => 30.0,
				),
			),
			30.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 6, $rate->get_cost(), 'Three units weighing 2 each should give a weight of 6.' );
	}

	/**
	 * find_shipping_classes() makes the same re-check, so a class sitting on an item that is not
	 * shipped contributes nothing to the rate.
	 *
	 * @testdox A shipping class on an item that does not need shipping adds no cost.
	 */
	public function test_a_class_on_a_virtual_product_adds_nothing(): void {
		$virtual = $this->shippable_product( 'flat-rate-downloadable', 0, true );

		$this->method_with(
			array(
				'cost' => '1',
				'class_cost_' . $virtual->get_shipping_class_id() => '9',
			)
		);
		$package = $this->package_of(
			array(
				array(
					'product'    => $this->shippable_product(),
					'quantity'   => 1,
					'line_total' => 10.0,
				),
				array(
					'product'    => $virtual,
					'quantity'   => 1,
					'line_total' => 10.0,
				),
			),
			20.0
		);

		$rate = $this->rate_for( $package );

		$this->assertEquals( 1, $rate->get_cost(), 'A class on something that is not shipped should not be charged for.' );
	}
}
