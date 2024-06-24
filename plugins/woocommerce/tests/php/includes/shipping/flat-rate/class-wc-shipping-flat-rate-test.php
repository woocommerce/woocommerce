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
}
