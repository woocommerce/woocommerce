<?php
/**
 * Unit tests for the WP_Countries class.
 *
 * @package WooCommerce\Tests\Countries
 */

// phpcs:disable WordPress.Files.FileName

/**
 * WC_Countries tests.
 */
class WC_Tests_Countries extends WC_Unit_Test_Case {

	/**
	 * Test getters.
	 *
	 * @since 3.1
	 */
	public function test_getters() {
		$countries = new WC_Countries();
		$this->assertEquals( $countries->get_countries(), $countries->countries );
		$this->assertEquals( $countries->get_states(), $countries->states );
	}

	/**
	 * Test get_shipping_continents.
	 *
	 * @since 3.6.0
	 */
	public function test_get_shipping_continents() {
		$countries      = new WC_Countries();
		$all_continents = $countries->get_continents();

		update_option( 'woocommerce_ship_to_countries', 'all' );
		$this->assertSame( $all_continents, $countries->get_shipping_continents() );

		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'CA', 'JP' ) );
		$expected = array(
			'AS' => $all_continents['AS'],
			'NA' => $all_continents['NA'],
		);
		$this->assertSame( $expected, $countries->get_shipping_continents() );
	}

	/**
	 * Test get_allowed_countries.
	 *
	 * @since 3.1
	 */
	public function test_get_allowed_countries() {
		$countries = new WC_Countries();

		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'RO', 'SI' ) );
		$expected = array(
			'RO' => 'Romania',
			'SI' => 'Slovenia',
		);
		$this->assertEquals( $expected, $countries->get_allowed_countries() );

		update_option( 'woocommerce_allowed_countries', 'all' );
		$this->assertEquals( $countries->get_countries(), $countries->get_allowed_countries() );

		update_option( 'woocommerce_allowed_countries', 'all_except' );
		update_option( 'woocommerce_all_except_countries', array( 'RO', 'SI' ) );
		$allowed_countries = $countries->get_allowed_countries();
		$this->assertEquals( count( $countries->get_countries() ) - 2, count( $allowed_countries ) );
		$this->assertFalse( isset( $allowed_countries['RO'], $allowed_countries['SI'] ) );
	}

	/**
	 * Test get_shipping_countries.
	 *
	 * @since 3.1
	 */
	public function test_get_shipping_countries() {
		$countries = new WC_Countries();

		update_option( 'woocommerce_ship_to_countries', '' );
		$this->assertEquals( $countries->get_allowed_countries(), $countries->get_shipping_countries() );

		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'RO', 'SI' ) );
		$this->assertEquals( $countries->get_allowed_countries(), $countries->get_shipping_countries() );

		update_option( 'woocommerce_ship_to_countries', 'all' );
		$this->assertEquals( $countries->get_countries(), $countries->get_shipping_countries() );

		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'RO', 'SI' ) );
		$expected = array(
			'RO' => 'Romania',
			'SI' => 'Slovenia',
		);
		$this->assertEquals( $expected, $countries->get_shipping_countries() );
	}

	/**
	 * Test get_allowed_country_states.
	 *
	 * @since 3.1
	 */
	public function test_get_allowed_country_states() {
		$countries = new WC_Countries();

		update_option( 'woocommerce_allowed_countries', 'all' );
		$this->assertEquals( $countries->get_states(), $countries->get_allowed_country_states() );

		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'US' ) );

		$all_states = $countries->get_allowed_country_states();
		$us_states  = $all_states['US'];

		$this->assertEquals( 'Oregon', $us_states['OR'] );
		$this->assertGreaterThanOrEqual( 50, count( $us_states ) );
	}

	/**
	 * Test get_shipping_country_states.
	 *
	 * @since 3.1
	 */
	public function test_get_shipping_country_states() {
		$countries = new WC_Countries();

		update_option( 'woocommerce_ship_to_countries', '' );
		$this->assertEquals( $countries->get_allowed_country_states(), $countries->get_shipping_country_states() );

		update_option( 'woocommerce_ship_to_countries', 'all' );
		$this->assertEquals( $countries->get_states(), $countries->get_shipping_country_states() );

		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'US' ) );

		$all_states = $countries->get_shipping_country_states();
		$us_states  = $all_states['US'];

		$this->assertEquals( 'Oregon', $us_states['OR'] );
		$this->assertGreaterThanOrEqual( 50, count( $us_states ) );
	}

	/**
	 * Test shipping_to_prefix.
	 *
	 * @since 3.1
	 */
	public function test_shipping_to_prefix() {
		$countries = new WC_Countries();

		$this->assertEquals( 'to', $countries->shipping_to_prefix( 'RO' ) );
		$this->assertEquals( 'to the', $countries->shipping_to_prefix( 'US' ) );
	}

	/**
	 * Test estimated_for_prefix.
	 *
	 * @since 3.1
	 */
	public function test_estimated_for_prefix() {
		$countries = new WC_Countries();

		$this->assertEquals( 'the ', $countries->estimated_for_prefix( 'GB' ) );
		$this->assertEquals( '', $countries->estimated_for_prefix( 'RO' ) );
	}

	/**
	 * Test tax_or_vat.
	 *
	 * @since 3.1
	 */
	public function test_tax_or_vat() {
		$countries = new WC_Countries();

		update_option( 'woocommerce_default_country', 'CZ' );
		$this->assertEquals( 'VAT', $countries->tax_or_vat() );

		update_option( 'woocommerce_default_country', 'NO' );
		$this->assertEquals( 'VAT', $countries->tax_or_vat() );

		update_option( 'woocommerce_default_country', 'US:CA' );
		$this->assertEquals( 'Tax', $countries->tax_or_vat() );
	}

	/**
	 * Test get_country_locale.
	 *
	 * @since 3.1
	 */
	public function test_get_country_locale() {
		$countries = new WC_Countries();
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'RO', 'SI' ) );

		$locales = $countries->get_country_locale();
		$this->assertArrayHasKey( 'RO', $locales );
		$this->assertArrayHasKey( 'SI', $locales );
		$this->assertArrayNotHasKey( 'AU', $locales );
		$this->assertArrayHasKey( 'default', $locales );
	}

	/**
	 * Test get_european_union_countries.
	 *
	 * @return void
	 */
	public function test_get_european_union_countries() {
		// After Brexit there should be 27 countries in the EU.
		$countries = new WC_Countries();
		$this->assertCount( 27, $countries->get_european_union_countries() );
	}

	/**
	 * Test get_vat_countries.
	 *
	 * @return void
	 */
	public function test_get_vat_countries() {
		$countries = new WC_Countries();
		$this->assertCount( 80, $countries->get_vat_countries() );
	}
}
