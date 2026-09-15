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
	 * Options changed by the tests.
	 *
	 * @var array<string, mixed>
	 */
	private $original_options = array();

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

		foreach ( array( 'woocommerce_allowed_countries', 'woocommerce_specific_allowed_countries', 'woocommerce_ship_to_countries' ) as $option_name ) {
			$this->original_options[ $option_name ] = get_option( $option_name, null );
		}

		$this->original_shipping_country = WC()->customer->get_shipping_country();
	}

	/**
	 * Restore the test fixture.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_shipping_calculator_enable_country', '__return_false' );
		remove_action( 'woocommerce_before_shipping_calculator', array( $this, 'disable_country_field_before_calculator' ) );

		foreach ( $this->original_options as $option_name => $option_value ) {
			if ( null === $option_value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $option_value );
			}
		}

		WC()->customer->set_shipping_country( $this->original_shipping_country );

		parent::tearDown();
	}

	/**
	 * A single available country should remain visible and be submitted by the calculator's select.
	 */
	public function test_single_shipping_country_uses_single_country_select() {
		$this->set_shipping_countries( array( 'GR' ) );
		WC()->customer->set_shipping_country( 'GR' );

		$output = wc_get_template_html( 'cart/shipping-calculator.php' );

		$select = new WP_HTML_Tag_Processor( $output );
		$this->assertTrue( $select->next_tag( array( 'tag_name' => 'select' ) ) );
		$this->assertSame( 'calc_shipping_country', $select->get_attribute( 'name' ) );
		$this->assertTrue( $select->has_class( 'country_to_state--single' ) );
		$this->assertFalse( $select->has_class( 'country_select' ) );
		$this->assertNull( $select->get_attribute( 'disabled' ) );
		$this->assertStringContainsString( '<option value="GR" selected=\'selected\'>Greece</option>', $output );
		$this->assertStringNotContainsString( '<input type="hidden" name="calc_shipping_country"', $output );
		$this->assertStringNotContainsString( 'Select a country / region', $output );
	}

	/**
	 * Hiding the country filter should hide the field without removing the required submitted value.
	 */
	public function test_single_shipping_country_is_submitted_when_filter_hides_field() {
		$this->set_shipping_countries( array( 'GR' ) );
		add_filter( 'woocommerce_shipping_calculator_enable_country', '__return_false' );

		$output = wc_get_template_html( 'cart/shipping-calculator.php' );

		$this->assertStringNotContainsString( 'id="calc_shipping_country_field"', $output );
		$this->assertStringContainsString( '<input type="hidden" name="calc_shipping_country" value="GR" />', $output );
	}

	/**
	 * Extensions should still be able to register the country filter from the before-calculator hook.
	 */
	public function test_country_filter_runs_after_before_calculator_hook() {
		$this->set_shipping_countries( array( 'GR' ) );
		add_action( 'woocommerce_before_shipping_calculator', array( $this, 'disable_country_field_before_calculator' ) );

		$output = wc_get_template_html( 'cart/shipping-calculator.php' );

		$this->assertStringNotContainsString( 'id="calc_shipping_country_field"', $output );
		$this->assertStringContainsString( '<input type="hidden" name="calc_shipping_country" value="GR" />', $output );
	}

	/**
	 * Multiple available countries should keep the existing selectable control.
	 */
	public function test_multiple_shipping_countries_remain_selectable() {
		$this->set_shipping_countries( array( 'GR', 'CY' ) );

		$output = wc_get_template_html( 'cart/shipping-calculator.php' );

		$select = new WP_HTML_Tag_Processor( $output );
		$this->assertTrue( $select->next_tag( array( 'tag_name' => 'select' ) ) );
		$this->assertSame( 'calc_shipping_country', $select->get_attribute( 'name' ) );
		$this->assertTrue( $select->has_class( 'country_select' ) );
		$this->assertFalse( $select->has_class( 'country_to_state--single' ) );
		$this->assertNull( $select->get_attribute( 'disabled' ) );
		$this->assertStringContainsString( 'Select a country / region', $output );
		$this->assertStringNotContainsString( '<input type="hidden" name="calc_shipping_country"', $output );
	}

	/**
	 * Multiple countries should retain the existing country-filter behavior.
	 */
	public function test_multiple_shipping_countries_can_still_hide_country_field() {
		$this->set_shipping_countries( array( 'GR', 'CY' ) );
		add_filter( 'woocommerce_shipping_calculator_enable_country', '__return_false' );

		$output = wc_get_template_html( 'cart/shipping-calculator.php' );

		$this->assertStringNotContainsString( 'id="calc_shipping_country_field"', $output );
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

	/**
	 * Disable the country field from the before-calculator hook.
	 */
	public function disable_country_field_before_calculator(): void {
		add_filter( 'woocommerce_shipping_calculator_enable_country', '__return_false' );
	}
}
