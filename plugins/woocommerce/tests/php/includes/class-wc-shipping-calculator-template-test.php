<?php
declare( strict_types = 1 );

/**
 * Tests for the shipping calculator template.
 *
 * @package WooCommerce\Tests\Templates
 */

/**
 * Class WC_Shipping_Calculator_Template_Test.
 */
class WC_Shipping_Calculator_Template_Test extends WC_Unit_Test_Case {

	/**
	 * Customer shipping country before each test.
	 *
	 * @var string
	 */
	private $original_shipping_country = '';

	/**
	 * Set up the test fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_shipping_country = WC()->customer->get_shipping_country();
	}

	/**
	 * Restore the test fixture.
	 */
	public function tearDown(): void {
		WC()->customer->set_shipping_country( $this->original_shipping_country );

		parent::tearDown();
	}

	/**
	 * @testdox Country select is single-style only when one shipping country is available.
	 * @testWith [["GR"], true]
	 *           [["GR", "CY"], false]
	 * @param string[] $countries Shipping country codes.
	 * @param bool     $is_single Whether only one shipping country is available.
	 */
	public function test_country_select( array $countries, bool $is_single ): void {
		$this->set_shipping_countries( $countries );
		WC()->customer->set_shipping_country( 'US' );

		$output = wc_get_template_html( 'cart/shipping-calculator.php' );

		$select = new WP_HTML_Tag_Processor( $output );
		$this->assertTrue( $select->next_tag( array( 'tag_name' => 'select' ) ) );
		$this->assertSame( 'calc_shipping_country', $select->get_attribute( 'name' ) );
		$this->assertTrue( $select->has_class( 'country_to_state' ) );
		$this->assertSame( $is_single, $select->has_class( 'country_to_state--single' ) );
		$this->assertSame( ! $is_single, $select->has_class( 'country_select' ) );
		$this->assertNull( $select->get_attribute( 'disabled' ) );
		$this->assertStringContainsString( '<option value="GR">Greece</option>', $output );
		$this->assertSame( ! $is_single, str_contains( $output, 'Select a country / region' ) );
	}

	/**
	 * @testdox Hidden country input is only output for single-country stores when the field is filtered off.
	 * @testWith [["GR"], "GR"]
	 *           [["GR", "CY"], null]
	 * @param string[]    $countries Shipping country codes.
	 * @param string|null $expected Expected hidden country code, or null when no country input is expected.
	 */
	public function test_hidden_country_input( array $countries, ?string $expected ): void {
		$this->set_shipping_countries( $countries );
		WC()->customer->set_shipping_country( 'US' );
		add_action(
			'woocommerce_before_shipping_calculator',
			static function () {
				add_filter( 'woocommerce_shipping_calculator_enable_country', '__return_false' );
			}
		);

		$output = wc_get_template_html( 'cart/shipping-calculator.php' );

		$this->assertStringNotContainsString( 'id="calc_shipping_country_field"', $output );
		if ( null === $expected ) {
			$this->assertStringNotContainsString( 'name="calc_shipping_country"', $output );
			return;
		}

		$country = new WP_HTML_Tag_Processor( $output );
		$this->assertTrue( $country->next_tag( array( 'tag_name' => 'input' ) ) );
		$this->assertSame( 'hidden', $country->get_attribute( 'type' ) );
		$this->assertSame( 'calc_shipping_country', $country->get_attribute( 'name' ) );
		$this->assertSame( $expected, $country->get_attribute( 'value' ) );
		$this->assertSame( 'calc_shipping_country', $country->get_attribute( 'id' ) );
		$this->assertTrue( $country->has_class( 'country_to_state' ) );
	}

	/**
	 * Configure the countries available to the shipping calculator.
	 *
	 * @param string[] $countries Country codes.
	 */
	private function set_shipping_countries( array $countries ): void {
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', $countries );
		update_option( 'woocommerce_ship_to_countries', '' );
	}
}
