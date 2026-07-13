<?php
declare( strict_types=1 );

/**
 * Tests for the WC_Countries class.
 */
class WC_Countries_Test extends \WC_Unit_Test_Case {
	/**
	 * Locale exposed through the WordPress locale filter.
	 *
	 * @var string
	 */
	private $active_locale = 'en_US';

	/**
	 * Geographical filter calls keyed by data type and locale.
	 *
	 * @var array<string, array<string, int>>
	 */
	private $geographical_filter_calls = array();

	/**
	 * Number of times the stateful locale filter was called.
	 *
	 * @var int
	 */
	private $locale_filter_calls = 0;

	/**
	 * Number of times the country source filter was called.
	 *
	 * @var int
	 */
	private $country_filter_calls = 0;

	/**
	 * Whether the states global existed before the test.
	 *
	 * @var bool
	 */
	private $states_global_existed = false;

	/**
	 * Value of the states global before the test.
	 *
	 * @var mixed
	 */
	private $states_global_value;

	/**
	 * Set up test state.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->states_global_existed = array_key_exists( 'states', $GLOBALS );
		$this->states_global_value   = $this->states_global_existed ? $GLOBALS['states'] : null;
	}

	/**
	 * Filter the active locale.
	 *
	 * @internal
	 *
	 * @param string $locale Current locale.
	 * @return string
	 */
	public function filter_active_locale( $locale ) {
		return $this->active_locale;
	}

	/**
	 * Return an empty locale once, then the WordPress fallback locale.
	 *
	 * @internal
	 *
	 * @param string $locale Current locale.
	 * @return string
	 */
	public function filter_stateful_locale( $locale ) {
		++$this->locale_filter_calls;
		return 1 === $this->locale_filter_calls ? '' : 'en_US';
	}

	/**
	 * Record a country source-filter call.
	 *
	 * @internal
	 *
	 * @param array $countries Country names.
	 * @return array
	 */
	public function record_country_filter_call( $countries ) {
		++$this->country_filter_calls;
		return $countries;
	}

	/**
	 * Stamp and record country names for the active locale.
	 *
	 * @internal
	 *
	 * @param array $countries Country names.
	 * @return array
	 */
	public function filter_country_names( $countries ) {
		$this->record_geographical_filter_call( 'countries' );
		$countries['US'] = $this->active_locale;
		return $countries;
	}

	/**
	 * Stamp and record state names for the active locale.
	 *
	 * @internal
	 *
	 * @param array $states State names.
	 * @return array
	 */
	public function filter_state_names( $states ) {
		$this->record_geographical_filter_call( 'states' );
		$states['US']['CA'] = $this->active_locale;
		return $states;
	}

	/**
	 * Stamp and record continent names for the active locale.
	 *
	 * @internal
	 *
	 * @param array $continents Continent data.
	 * @return array
	 */
	public function filter_continent_names( $continents ) {
		$this->record_geographical_filter_call( 'continents' );
		$continents['NA']['name'] = $this->active_locale;
		return $continents;
	}

	/**
	 * Return the option value that enables all countries.
	 *
	 * @internal
	 *
	 * @return string
	 */
	public function return_all_countries() {
		return 'all';
	}

	/**
	 * Remove registered filters and reset test state.
	 */
	public function tearDown(): void {
		if ( $this->states_global_existed ) {
			$GLOBALS['states'] = $this->states_global_value;
		} else {
			unset( $GLOBALS['states'] );
		}

		$this->states_global_existed = false;
		$this->states_global_value   = null;

		remove_filter( 'locale', array( $this, 'filter_active_locale' ) );
		remove_filter( 'locale', array( $this, 'filter_stateful_locale' ) );
		remove_filter( 'woocommerce_countries', array( $this, 'filter_country_names' ) );
		remove_filter( 'woocommerce_countries', array( $this, 'record_country_filter_call' ) );
		remove_filter( 'woocommerce_states', array( $this, 'filter_state_names' ) );
		remove_filter( 'woocommerce_continents', array( $this, 'filter_continent_names' ) );
		remove_filter( 'pre_option_woocommerce_allowed_countries', array( $this, 'return_all_countries' ) );
		remove_filter( 'pre_option_woocommerce_ship_to_countries', array( $this, 'return_all_countries' ) );

		$this->active_locale             = 'en_US';
		$this->geographical_filter_calls = array();
		$this->locale_filter_calls       = 0;
		$this->country_filter_calls      = 0;

		parent::tearDown();
	}

	/**
	 * @testdox Geographical data uses the active locale and reuses each locale cache.
	 */
	public function test_geographical_data_uses_the_active_locale_and_reuses_each_locale_cache() {
		$this->register_geographical_filters();
		$sut = new WC_Countries();

		$this->assert_geographical_locale( $sut, 'en_US' );

		$this->active_locale = 'fr_FR';
		$this->assert_geographical_locale( $sut, 'fr_FR' );

		$this->active_locale = 'en_US';
		$this->assert_geographical_locale( $sut, 'en_US' );

		$expected_calls = array(
			'countries'  => array(
				'en_US' => 1,
				'fr_FR' => 1,
			),
			'states'     => array(
				'en_US' => 1,
				'fr_FR' => 1,
			),
			'continents' => array(
				'en_US' => 1,
				'fr_FR' => 1,
			),
		);
		$this->assertSame( $expected_calls, $this->geographical_filter_calls, 'Each geographical source filter should run once per locale.' );
	}

	/**
	 * @testdox Loading country states populates only the active locale.
	 */
	public function test_load_country_states_populates_only_the_active_locale() {
		$this->register_geographical_filters();
		$sut = new WC_Countries();

		$sut->load_country_states();
		$this->assertSame( 'en_US', $sut->get_states( 'US' )['CA'], 'Loaded states should use the active locale.' );

		$this->active_locale = 'fr_FR';
		$this->assertSame( 'fr_FR', $sut->get_states( 'US' )['CA'], 'States should use the changed active locale.' );

		$this->active_locale = 'en_US';
		$this->assertSame( 'en_US', $sut->get_states( 'US' )['CA'], 'States should reuse the restored locale cache.' );
		$this->assertSame(
			array(
				'en_US' => 1,
				'fr_FR' => 1,
			),
			$this->geographical_filter_calls['states'],
			'The states source filter should run once per locale.'
		);
	}

	/**
	 * @testdox Country loading normalizes a falsey locale with one locale read.
	 */
	public function test_country_loading_normalizes_a_falsey_locale_with_one_locale_read() {
		add_filter( 'locale', array( $this, 'filter_stateful_locale' ) );
		add_filter( 'woocommerce_countries', array( $this, 'record_country_filter_call' ) );
		$sut = new WC_Countries();

		$sut->get_countries();

		$this->assertSame( 1, $this->locale_filter_calls, 'A country load should read the active locale once.' );

		$sut->get_countries();

		$this->assertSame( 2, $this->locale_filter_calls, 'Each country cache lookup should read the active locale once.' );
		$this->assertSame( 1, $this->country_filter_calls, 'The normalized fallback locale should reuse its country cache.' );
	}

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
	 * Register the geographical data filters used by locale tests.
	 */
	private function register_geographical_filters() {
		add_filter( 'locale', array( $this, 'filter_active_locale' ) );
		add_filter( 'woocommerce_countries', array( $this, 'filter_country_names' ) );
		add_filter( 'woocommerce_states', array( $this, 'filter_state_names' ) );
		add_filter( 'woocommerce_continents', array( $this, 'filter_continent_names' ) );
		add_filter( 'pre_option_woocommerce_allowed_countries', array( $this, 'return_all_countries' ) );
		add_filter( 'pre_option_woocommerce_ship_to_countries', array( $this, 'return_all_countries' ) );
	}

	/**
	 * Record a geographical source-filter call for the active locale.
	 *
	 * @param string $type Geographical data type.
	 */
	private function record_geographical_filter_call( $type ) {
		if ( ! isset( $this->geographical_filter_calls[ $type ][ $this->active_locale ] ) ) {
			$this->geographical_filter_calls[ $type ][ $this->active_locale ] = 0;
		}

		++$this->geographical_filter_calls[ $type ][ $this->active_locale ];
	}

	/**
	 * Assert that all geographical data uses the requested locale.
	 *
	 * @param WC_Countries $sut    The system under test.
	 * @param string       $locale Expected locale.
	 */
	private function assert_geographical_locale( WC_Countries $sut, $locale ) {
		$this->assertSame( $locale, $sut->get_allowed_countries()['US'], 'Allowed countries should use the active locale.' );
		$this->assertSame( $locale, $sut->get_shipping_countries()['US'], 'Shipping countries should use the active locale.' );
		$this->assertSame( $locale, $sut->get_states( 'US' )['CA'], 'States should use the active locale.' );
		$this->assertSame( $locale, $sut->get_continents()['NA']['name'], 'Continents should use the active locale.' );
	}
}
