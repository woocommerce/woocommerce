<?php
declare( strict_types=1 );

/**
 * Tests for the WC_Countries class.
 */
class WC_Countries_Test extends \WC_Unit_Test_Case {
	/**
	 * Tests for `get_country_from_alpha_3_code`.
	 *
	 * @param mixed $country_code Country code to test.
	 * @param mixed $expected     Expected result.
	 * @return void
	 *
	 * @dataProvider provide_test_get_country_from_alpha_3_code
	 */
	public function test_get_country_from_alpha_3_code( $country_code, $expected ) {
		$this->assertEquals( $expected, wc()->countries->get_country_from_alpha_3_code( $country_code ) );
	}

	/**
	 * Provider for `test_get_country_from_alpha_3_code`.
	 *
	 * @return array
	 */
	public function provide_test_get_country_from_alpha_3_code() {
		return array(
			'empty'   => array(
				'country code'    => '',
				'expected result' => null,
			),
			'integer' => array(
				'country code'    => 123,
				'expected result' => null,
			),
			'invalid' => array(
				'country code'    => 'invalid',
				'expected result' => null,
			),
			'valid'   => array(
				'country code'    => 'USA',
				'expected result' => 'US',
			),
		);
	}

	/**
	 * Tests that `flush_country_name_cache` clears the geo_cache so that
	 * get_countries(), get_states(), and get_continents() reload their data.
	 *
	 * @return void
	 */
	public function test_flush_country_name_cache_clears_geo_cache() {
		$countries = new WC_Countries();

		// Warm the cache for all three data sets.
		$countries->get_countries();
		$countries->get_states();
		$countries->get_continents();

		// Inspect the private geo_cache via reflection.
		$ref   = new ReflectionClass( $countries );
		$prop  = $ref->getProperty( 'geo_cache' );
		$prop->setAccessible( true );

		$before = $prop->getValue( $countries );
		$this->assertArrayHasKey( 'countries', $before, 'geo_cache[countries] should be populated after get_countries().' );
		$this->assertArrayHasKey( 'states', $before, 'geo_cache[states] should be populated after get_states().' );
		$this->assertArrayHasKey( 'continents', $before, 'geo_cache[continents] should be populated after get_continents().' );

		$countries->flush_country_name_cache();

		$after = $prop->getValue( $countries );
		$this->assertArrayNotHasKey( 'countries', $after, 'geo_cache[countries] should be cleared by flush_country_name_cache().' );
		$this->assertArrayNotHasKey( 'states', $after, 'geo_cache[states] should be cleared by flush_country_name_cache().' );
		$this->assertArrayNotHasKey( 'continents', $after, 'geo_cache[continents] should be cleared by flush_country_name_cache().' );
	}

	/**
	 * Tests that `flush_country_name_cache` is hooked to the `change_locale` action
	 * so country names are refreshed when the locale switches.
	 *
	 * @return void
	 */
	public function test_flush_country_name_cache_hooked_to_change_locale() {
		$countries = new WC_Countries();

		$this->assertNotFalse(
			has_action( 'change_locale', array( $countries, 'flush_country_name_cache' ) ),
			'flush_country_name_cache() should be hooked to the change_locale action.'
		);
	}
}
