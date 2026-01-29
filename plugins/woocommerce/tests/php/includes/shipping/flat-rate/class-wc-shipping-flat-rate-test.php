<?php

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- backcompat nomenclature.

/**
 * Test for WC_Shipping_Flat_Rate class.
 */
class WC_Shipping_Flat_Rate_Test extends WC_Unit_Test_Case {

	/**
	 * @var WC_Shipping_Flat_Rate Shipping method instance.
	 */
	private $sut;

	/**
	 * @var Closure Function to call protected method evaluate_cost.
	 */
	private $call_evaluate_cost;

	/**
	 * Set up test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                = new WC_Shipping_Flat_Rate();
		$this->call_evaluate_cost = function ( $sum, $args ) {
			return $this->evaluate_cost( $sum, $args );
		};
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );
	}

	/**
	 * Tear down test case.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_price_decimal_sep', '.' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		parent::tearDown();
	}


	/**
	 * @testDox Shipping cost with decimal separator works as expected.
	 */
	public function test_evaluate_cost_sep_dec() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12345,67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with incorrect decimal separator works as expected.
	 */
	public function test_evaluate_cost_dec_separator_inverse() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12345.67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with a thousand and decimal separator works as expected.
	 */
	public function test_evaluate_cost_sep_thou_dec() {
		$this->markTestSkipped( 'This test currently fails because we dont support thousand separator in shipping price.' );
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12.345,67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with two decimal separator works as expected.
	 */
	public function test_evaluate_cost_sep_dec_dec() {
		$this->markTestSkipped( 'This test currently fails because we dont support thousand separator in shipping price.' );
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12,345,67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with two thousand separator works as expected.
	 */
	public function test_evaluate_cost_sep_thou_thou() {
		$this->markTestSkipped( 'This test currently fails because we dont support thousand separator in shipping price.' );
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12.345.67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 1234567, $val );
	}

	/**
	 * Percent fee calculation works as expected.
	 */
	public function test_evaluate_cost_percent_fee() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="10.1"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 10.1, $val );
	}

	/**
	 * Percent fee calculation works as expected with comma as decimal separator. Value after the comma is ignored.
	 */
	public function test_evaluate_cost_percent_fee_comma() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="10,1"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 10, $val );
	}

	/**
	 * @testDox Per-class calculation does not offer rate when cart contains a product with a shipping class that has no cost defined.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/27030
	 */
	public function test_per_class_calculation_requires_all_classes_to_have_costs() {
		// Create two shipping classes.
		$class_with_cost    = wp_insert_term( 'Class With Cost', 'product_shipping_class' );
		$class_without_cost = wp_insert_term( 'Class Without Cost', 'product_shipping_class' );

		// Create a shipping zone.
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Test Zone' );
		$zone->set_zone_order( 1 );
		$zone->save();

		// Add flat rate shipping method to the zone.
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Configure flat rate: set cost only for the first class, leave second class empty.
		$flat_rate = WC_Shipping_Zones::get_shipping_method( $instance_id );
		$flat_rate->update_option( 'title', 'Test Flat Rate' );
		$flat_rate->update_option( 'type', 'class' ); // Per class calculation.
		$flat_rate->update_option( 'class_cost_' . $class_with_cost['term_id'], '10' );
		// Note: class_cost for class_without_cost is intentionally not set (empty).

		// Create products with each shipping class.
		$product_with_cost = WC_Helper_Product::create_simple_product();
		$product_with_cost->set_shipping_class_id( $class_with_cost['term_id'] );
		$product_with_cost->save();

		$product_without_cost = WC_Helper_Product::create_simple_product();
		$product_without_cost->set_shipping_class_id( $class_without_cost['term_id'] );
		$product_without_cost->save();

		// Clear cart and add both products.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product_with_cost->get_id() );
		WC()->cart->add_to_cart( $product_without_cost->get_id() );

		// Set a shipping destination that matches the zone.
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_postcode( '90210' );

		// Calculate shipping.
		WC()->cart->calculate_totals();
		$packages       = WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() );
		$available_rate = false;

		foreach ( $packages as $package ) {
			if ( isset( $package['rates'][ 'flat_rate:' . $instance_id ] ) ) {
				$available_rate = true;
				break;
			}
		}

		// Assert that no flat rate is available (because one class has no cost defined).
		$this->assertFalse(
			$available_rate,
			'Flat rate should NOT be available when cart contains products with shipping classes that have no cost defined.'
		);

		// Cleanup.
		WC()->cart->empty_cart();
		$zone->delete();
		$product_with_cost->delete( true );
		$product_without_cost->delete( true );
		wp_delete_term( $class_with_cost['term_id'], 'product_shipping_class' );
		wp_delete_term( $class_without_cost['term_id'], 'product_shipping_class' );
	}

	/**
	 * @testDox Per-class calculation offers rate when all shipping classes in cart have costs defined.
	 */
	public function test_per_class_calculation_works_when_all_classes_have_costs() {
		// Create two shipping classes.
		$class_one = wp_insert_term( 'Class One', 'product_shipping_class' );
		$class_two = wp_insert_term( 'Class Two', 'product_shipping_class' );

		// Create a shipping zone.
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Test Zone' );
		$zone->set_zone_order( 1 );
		$zone->save();

		// Add flat rate shipping method to the zone.
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		// Configure flat rate: set cost for both classes.
		$flat_rate = WC_Shipping_Zones::get_shipping_method( $instance_id );
		$flat_rate->update_option( 'title', 'Test Flat Rate' );
		$flat_rate->update_option( 'type', 'class' ); // Per class calculation.
		$flat_rate->update_option( 'class_cost_' . $class_one['term_id'], '10' );
		$flat_rate->update_option( 'class_cost_' . $class_two['term_id'], '15' );

		// Create products with each shipping class.
		$product_one = WC_Helper_Product::create_simple_product();
		$product_one->set_shipping_class_id( $class_one['term_id'] );
		$product_one->save();

		$product_two = WC_Helper_Product::create_simple_product();
		$product_two->set_shipping_class_id( $class_two['term_id'] );
		$product_two->save();

		// Clear cart and add both products.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product_one->get_id() );
		WC()->cart->add_to_cart( $product_two->get_id() );

		// Set a shipping destination that matches the zone.
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_postcode( '90210' );

		// Calculate shipping.
		WC()->cart->calculate_totals();
		$packages       = WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() );
		$available_rate = null;

		foreach ( $packages as $package ) {
			if ( isset( $package['rates'][ 'flat_rate:' . $instance_id ] ) ) {
				$available_rate = $package['rates'][ 'flat_rate:' . $instance_id ];
				break;
			}
		}

		// Assert that flat rate is available and cost is the sum of both class costs.
		$this->assertNotNull(
			$available_rate,
			'Flat rate should be available when all shipping classes have costs defined.'
		);
		$this->assertEquals(
			25, // 10 + 15 = 25
			$available_rate->get_cost(),
			'Flat rate cost should be the sum of all shipping class costs.'
		);

		// Cleanup.
		WC()->cart->empty_cart();
		$zone->delete();
		$product_one->delete( true );
		$product_two->delete( true );
		wp_delete_term( $class_one['term_id'], 'product_shipping_class' );
		wp_delete_term( $class_two['term_id'], 'product_shipping_class' );
	}
}
