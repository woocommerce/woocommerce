<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\DefaultCustomerAddress;

/**
 * Tests for the WC_Countries class.
 */
class WC_Countries_Test extends \WC_Unit_Test_Case {
	/**
	 * Depth at which the geographical locale filter stops reading, so a recursion fails the test instead of the run.
	 */
	private const LOCALE_FILTER_DEPTH_LIMIT = 10;

	/**
	 * Countries instance read by the geographical locale filter, or null to create one on each call.
	 *
	 * @var WC_Countries|null
	 */
	private $locale_filter_countries = null;

	/**
	 * WC_Countries method the geographical locale filter calls.
	 *
	 * @var string
	 */
	private $locale_filter_lookup = 'get_countries';

	/**
	 * Current nesting of the geographical locale filter.
	 *
	 * @var int
	 */
	private $locale_filter_depth = 0;

	/**
	 * Deepest nesting of the geographical locale filter.
	 *
	 * @var int
	 */
	private $locale_filter_max_depth = 0;

	/**
	 * Data the geographical locale filter received from its lookups.
	 *
	 * @var array
	 */
	private $locale_filter_lookups = array();

	/**
	 * Build counts keyed by data type and switched-locale stamp.
	 *
	 * @var array<string, array<string, int>>
	 */
	private $switched_locale_stamps = array();

	/**
	 * Default customer locations the locale filter received.
	 *
	 * @var array
	 */
	private $locale_filter_default_locations = array();

	/**
	 * Countries object replaced on WC() by a test.
	 *
	 * @var WC_Countries|null
	 */
	private $original_countries = null;

	/**
	 * Locale switcher replaced by a test.
	 *
	 * @var WP_Locale_Switcher|null
	 */
	private $original_locale_switcher = null;

	/**
	 * Locale made available to the replacement locale switcher.
	 *
	 * @var string
	 */
	private $available_test_locale = '';

	/**
	 * Locale exposed through the WordPress request-locale filter.
	 *
	 * @var mixed
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
	 * Number of times the country locale filter was called.
	 *
	 * @var int
	 */
	private $country_locale_filter_calls = 0;

	/**
	 * Number of empty geographical filter calls keyed by filter name.
	 *
	 * @var array<string, int>
	 */
	private $empty_geographical_filter_calls = array();

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
	 * Filter the active request locale.
	 *
	 * @internal
	 *
	 * @param string $locale Current locale.
	 * @return mixed
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
	 * Record and return the active request locale.
	 *
	 * @internal
	 *
	 * @param string $locale Current locale.
	 * @return mixed
	 */
	public function filter_counted_active_locale( $locale ) {
		++$this->locale_filter_calls;
		return $this->active_locale;
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
	 * Return empty geographical data and record the filter call.
	 *
	 * @internal
	 *
	 * @param array $data Geographical data.
	 * @return array
	 */
	public function filter_empty_geographical_data( $data ) {
		unset( $data );

		$filter = current_filter();

		if ( ! isset( $this->empty_geographical_filter_calls[ $filter ] ) ) {
			$this->empty_geographical_filter_calls[ $filter ] = 0;
		}

		++$this->empty_geographical_filter_calls[ $filter ];
		return array();
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
	 * Stamp country locale settings for the active request locale.
	 *
	 * @internal
	 *
	 * @param array $locale Country locale settings.
	 * @return array
	 */
	public function filter_country_locale( $locale ) {
		++$this->country_locale_filter_calls;
		$locale['US']['postcode']['label'] = $this->active_locale;
		return $locale;
	}

	/**
	 * Read geographical data from a locale filter, as a "language by visitor country" extension might.
	 *
	 * @internal
	 *
	 * @param mixed $locale Current locale.
	 * @return mixed
	 */
	public function read_geographical_data_in_locale_filter( $locale ) {
		++$this->locale_filter_depth;
		$this->locale_filter_max_depth = max( $this->locale_filter_max_depth, $this->locale_filter_depth );

		if ( $this->locale_filter_depth < self::LOCALE_FILTER_DEPTH_LIMIT ) {
			$countries                     = $this->locale_filter_countries ?? new WC_Countries();
			$lookup                        = $this->locale_filter_lookup;
			$this->locale_filter_lookups[] = $countries->$lookup();
		}

		--$this->locale_filter_depth;

		return $locale;
	}

	/**
	 * Read the default customer location from a locale filter, as a "language by visitor country" extension might.
	 *
	 * @internal
	 *
	 * @param mixed $locale Current locale.
	 * @return mixed
	 */
	public function read_default_location_in_locale_filter( $locale ) {
		++$this->locale_filter_depth;
		$this->locale_filter_max_depth = max( $this->locale_filter_max_depth, $this->locale_filter_depth );

		if ( $this->locale_filter_depth < self::LOCALE_FILTER_DEPTH_LIMIT ) {
			$this->locale_filter_default_locations[] = wc_get_customer_default_location();
		}

		--$this->locale_filter_depth;

		return $locale;
	}

	/**
	 * Ask WordPress for the locale while geographical data is built, as the just-in-time translation loader does.
	 *
	 * @internal
	 *
	 * @param array $data Geographical data.
	 * @return array
	 */
	public function determine_locale_while_building( $data ) {
		determine_locale();
		return $data;
	}

	/**
	 * Stamp country names with the switched locale.
	 *
	 * @internal
	 *
	 * @param array $countries Country names.
	 * @return array
	 */
	public function stamp_country_names_with_switched_locale( $countries ) {
		$countries['US'] = $this->record_switched_locale_stamp( 'countries' );
		return $countries;
	}

	/**
	 * Stamp country locale settings with the switched locale.
	 *
	 * @internal
	 *
	 * @param array $locale Country locale settings.
	 * @return array
	 */
	public function stamp_country_locale_with_switched_locale( $locale ) {
		$locale['US']['postcode']['label'] = $this->record_switched_locale_stamp( 'country_locale' );
		return $locale;
	}

	/**
	 * Make the test locale available to the replacement locale switcher.
	 *
	 * @internal
	 *
	 * @param array $languages Available languages.
	 * @return array
	 */
	public function add_available_test_locale( $languages ) {
		$languages[] = $this->available_test_locale;
		return $languages;
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

		remove_filter( 'determine_locale', array( $this, 'filter_active_locale' ) );
		remove_filter( 'determine_locale', array( $this, 'filter_counted_active_locale' ) );
		remove_filter( 'determine_locale', array( $this, 'filter_stateful_locale' ) );
		remove_filter( 'woocommerce_countries', array( $this, 'filter_country_names' ) );
		remove_filter( 'woocommerce_countries', array( $this, 'record_country_filter_call' ) );
		remove_filter( 'woocommerce_countries', array( $this, 'filter_empty_geographical_data' ) );
		remove_filter( 'woocommerce_states', array( $this, 'filter_state_names' ) );
		remove_filter( 'woocommerce_states', array( $this, 'filter_empty_geographical_data' ) );
		remove_filter( 'woocommerce_continents', array( $this, 'filter_continent_names' ) );
		remove_filter( 'woocommerce_continents', array( $this, 'filter_empty_geographical_data' ) );
		remove_filter( 'woocommerce_get_country_locale', array( $this, 'filter_country_locale' ) );
		remove_filter( 'pre_option_woocommerce_allowed_countries', array( $this, 'return_all_countries' ) );
		remove_filter( 'pre_option_woocommerce_ship_to_countries', array( $this, 'return_all_countries' ) );
		remove_filter( 'locale', array( $this, 'read_geographical_data_in_locale_filter' ), 20 );
		remove_filter( 'determine_locale', array( $this, 'read_geographical_data_in_locale_filter' ), 20 );
		remove_filter( 'pre_determine_locale', array( $this, 'read_geographical_data_in_locale_filter' ), 20 );
		remove_filter( 'woocommerce_countries', array( $this, 'determine_locale_while_building' ) );
		remove_filter( 'woocommerce_states', array( $this, 'determine_locale_while_building' ) );
		remove_filter( 'woocommerce_get_country_locale', array( $this, 'determine_locale_while_building' ) );
		remove_filter( 'woocommerce_countries', array( $this, 'stamp_country_names_with_switched_locale' ) );
		remove_filter( 'woocommerce_get_country_locale', array( $this, 'stamp_country_locale_with_switched_locale' ) );
		remove_filter( 'get_available_languages', array( $this, 'add_available_test_locale' ) );
		remove_filter( 'locale', array( $this, 'read_default_location_in_locale_filter' ), 20 );
		remove_filter( 'determine_locale', array( $this, 'read_default_location_in_locale_filter' ), 20 );

		if ( null !== $this->original_countries ) {
			WC()->countries = $this->original_countries;
		}

		if ( null !== $this->original_locale_switcher ) {
			restore_current_locale();
			$GLOBALS['wp_locale_switcher'] = $this->original_locale_switcher; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the switcher replaced by the test.
		}

		$this->locale_filter_countries  = null;
		$this->locale_filter_lookup     = 'get_countries';
		$this->locale_filter_depth      = 0;
		$this->locale_filter_max_depth  = 0;
		$this->locale_filter_lookups    = array();
		$this->switched_locale_stamps   = array();
		$this->original_locale_switcher = null;
		$this->available_test_locale    = '';

		$this->locale_filter_default_locations = array();
		$this->original_countries              = null;

		$this->active_locale                   = 'en_US';
		$this->geographical_filter_calls       = array();
		$this->locale_filter_calls             = 0;
		$this->country_filter_calls            = 0;
		$this->country_locale_filter_calls     = 0;
		$this->empty_geographical_filter_calls = array();

		parent::tearDown();
	}

	/**
	 * @testdox Geographical data uses the active request locale and reuses each locale cache.
	 */
	public function test_geographical_data_uses_the_active_request_locale_and_reuses_each_locale_cache() {
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
	 * @testdox Loading country states populates only the active request locale.
	 */
	public function test_load_country_states_populates_only_the_active_request_locale() {
		$this->register_geographical_filters();
		$sut = new WC_Countries();

		$sut->load_country_states();
		$this->assertSame( 'en_US', $sut->get_states( 'US' )['CA'], 'Loaded states should use the active request locale.' );

		$this->active_locale = 'fr_FR';
		$this->assertSame( 'fr_FR', $sut->get_states( 'US' )['CA'], 'States should use the changed active request locale.' );

		$this->active_locale = 'en_US';
		$this->assertSame( 'en_US', $sut->get_states( 'US' )['CA'], 'States should reuse the restored request-locale cache.' );
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
	 * @testdox Country locale settings rebuild for the active request locale.
	 */
	public function test_country_locale_settings_rebuild_for_the_active_request_locale(): void {
		add_filter( 'determine_locale', array( $this, 'filter_active_locale' ) );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'filter_country_locale' ) );
		$sut = new WC_Countries();

		$this->assertSame( 'en_US', $sut->get_country_locale()['US']['postcode']['label'], 'Country locale settings should use the initial request locale.' );
		$this->assertSame( 'en_US', $sut->get_country_locale()['US']['postcode']['label'], 'Country locale settings should reuse the initial request-locale cache.' );
		$this->assertSame( 1, $this->country_locale_filter_calls, 'Country locale settings should be built once for the initial request locale.' );

		$this->active_locale = 'fr_FR';

		$this->assertSame( 'fr_FR', $sut->get_country_locale()['US']['postcode']['label'], 'Country locale settings should rebuild for the changed request locale.' );
		$this->assertSame( 'fr_FR', $sut->get_country_locale()['US']['postcode']['label'], 'Country locale settings should reuse the changed request-locale cache.' );
		$this->assertSame( 2, $this->country_locale_filter_calls, 'Country locale settings should be built once per request locale.' );
	}

	/**
	 * @testdox Country locale settings preserve a prepopulated public locale property.
	 */
	public function test_country_locale_settings_preserve_a_prepopulated_public_locale_property(): void {
		$custom_locale = array(
			'CUSTOM' => array(
				'city' => array(
					'label' => 'Extension-provided city',
				),
			),
		);
		$sut           = new WC_Countries();
		$sut->locale   = $custom_locale;
		add_filter( 'determine_locale', array( $this, 'filter_counted_active_locale' ) );

		$this->assertSame( $custom_locale, $sut->get_country_locale(), 'A prepopulated public locale property should remain authoritative.' );
		$this->assertSame( 0, $this->locale_filter_calls, 'A prepopulated public locale property should bypass request-locale evaluation.' );
	}

	/**
	 * @testdox Filtering states for specific countries reads the magic states property once.
	 *
	 * @dataProvider provide_specific_country_state_methods
	 *
	 * @param string $method                   Method under test.
	 * @param string $mode_option              Country restriction mode option.
	 * @param string $specific_countries_option Specific countries option.
	 */
	public function test_filtering_states_for_specific_countries_reads_the_magic_states_property_once( $method, $mode_option, $specific_countries_option ): void {
		update_option( $mode_option, 'specific' );
		update_option( $specific_countries_option, array( 'US', 'CA' ) );
		add_filter( 'determine_locale', array( $this, 'filter_counted_active_locale' ) );
		$sut = new class() extends WC_Countries {
			/**
			 * Number of reads through the magic states property.
			 *
			 * @var int
			 */
			public $state_property_reads = 0;

			/**
			 * Track magic states property reads.
			 *
			 * @param mixed $key Property key.
			 * @return mixed
			 */
			public function __get( $key ) {
				if ( 'states' === $key ) {
					++$this->state_property_reads;
				}

				return parent::__get( $key );
			}
		};

		$states = $sut->$method();

		$this->assertArrayHasKey( 'US', $states, 'The United States should be included in the filtered states.' );
		$this->assertArrayHasKey( 'CA', $states, 'Canada should be included in the filtered states.' );
		$this->assertSame( 1, $sut->state_property_reads, 'Filtering states should preserve one magic property read.' );
		$this->assertSame( 1, $this->locale_filter_calls, 'Filtering states should evaluate the request locale once.' );
	}

	/**
	 * @testdox Country loading normalizes a falsey locale with one locale read.
	 */
	public function test_country_loading_normalizes_a_falsey_locale_with_one_locale_read() {
		add_filter( 'determine_locale', array( $this, 'filter_stateful_locale' ) );
		add_filter( 'woocommerce_countries', array( $this, 'record_country_filter_call' ) );
		$sut = new WC_Countries();

		$sut->get_countries();

		$this->assertSame( 1, $this->locale_filter_calls, 'A country load should read the active locale once.' );

		$sut->get_countries();

		$this->assertSame( 2, $this->locale_filter_calls, 'Each country cache lookup should read the active locale once.' );
		$this->assertSame( 1, $this->country_filter_calls, 'The normalized fallback locale should reuse its country cache.' );
	}

	/**
	 * @testdox Country loading falls back for an invalid filtered locale.
	 *
	 * @dataProvider provide_invalid_locale_values
	 *
	 * @param mixed $invalid_locale Invalid filtered locale.
	 */
	public function test_country_loading_normalizes_invalid_filtered_locale( $invalid_locale ): void {
		$this->active_locale = $invalid_locale;
		add_filter( 'determine_locale', array( $this, 'filter_active_locale' ) );
		add_filter( 'woocommerce_countries', array( $this, 'record_country_filter_call' ) );
		$sut = new WC_Countries();

		$sut->get_countries();

		$this->active_locale = 'en_US';
		$sut->get_countries();

		$this->assertSame( 1, $this->country_filter_calls, 'An invalid locale should share the fallback locale cache.' );
	}

	/**
	 * @testdox Empty filtered geographical data preserves existing cache semantics (characterization, not locale coverage).
	 */
	public function test_empty_filtered_geographical_data_preserves_existing_cache_semantics(): void {
		add_filter( 'woocommerce_countries', array( $this, 'filter_empty_geographical_data' ) );
		add_filter( 'woocommerce_states', array( $this, 'filter_empty_geographical_data' ) );
		add_filter( 'woocommerce_continents', array( $this, 'filter_empty_geographical_data' ) );
		$sut = new WC_Countries();

		$this->assertSame( array(), $sut->get_countries(), 'Countries should allow an empty filtered result.' );
		$this->assertSame( array(), $sut->get_countries(), 'Countries should allow a repeated empty filtered result.' );
		$this->assertSame( array(), $sut->get_states(), 'States should allow an empty filtered result.' );
		$this->assertSame( array(), $sut->get_states(), 'States should reuse an empty filtered result.' );
		$this->assertSame( array(), $sut->get_continents(), 'Continents should allow an empty filtered result.' );
		$this->assertSame( array(), $sut->get_continents(), 'Continents should allow a repeated empty filtered result.' );
		$this->assertSame(
			array(
				'woocommerce_countries'  => 2,
				'woocommerce_states'     => 1,
				'woocommerce_continents' => 2,
			),
			$this->empty_geographical_filter_calls,
			'Countries and continents should reload empty results while states cache them.'
		);
	}

	/**
	 * @testdox A locale filter that reads geographical data runs once per lookup and gets complete data.
	 *
	 * @dataProvider provide_locale_filter_geographical_lookups
	 *
	 * @param string $hook   Locale hook the callback is attached to.
	 * @param string $lookup WC_Countries method the callback calls.
	 */
	public function test_locale_filter_reading_geographical_data_does_not_recurse( $hook, $lookup ): void {
		add_filter( 'determine_locale', array( $this, 'filter_active_locale' ) );
		add_filter( $hook, array( $this, 'read_geographical_data_in_locale_filter' ), 20 );
		$sut                           = new WC_Countries();
		$this->locale_filter_countries = $sut;
		$this->locale_filter_lookup    = $lookup;
		$source_countries              = include WC()->plugin_path() . '/i18n/countries.php';
		$source_states                 = include WC()->plugin_path() . '/i18n/states.php';

		foreach ( array( 'cold', 'warm' ) as $cache_state ) {
			$this->assertSame( $source_countries['DE'], $sut->get_countries()['DE'], "Countries should be complete on a $cache_state cache." );
			$this->assertSame( $source_states['US']['CA'], $sut->get_states( 'US' )['CA'], "States should be complete on a $cache_state cache." );
			$this->assertArrayHasKey( 'default', $sut->get_country_locale(), "Country locale settings should be complete on a $cache_state cache." );
		}

		$lookups  = $this->locale_filter_lookups;
		$expected = $sut->$lookup();

		$this->assertSame( 1, $this->locale_filter_max_depth, 'A lookup inside the locale filter should not run the filter again.' );
		$this->assertNotEmpty( $lookups, 'The locale filter should have read geographical data.' );

		foreach ( $lookups as $data ) {
			$this->assertSame( $expected, $data, 'The locale filter should get the same data as a direct lookup.' );
		}
	}

	/**
	 * @testdox A locale filter that creates its own countries instance does not recurse.
	 */
	public function test_locale_filter_creating_countries_instances_does_not_recurse(): void {
		add_filter( 'determine_locale', array( $this, 'read_geographical_data_in_locale_filter' ), 20 );
		$sut = new WC_Countries();

		$countries = $sut->get_countries();

		$this->assertSame( 1, $this->locale_filter_max_depth, 'A lookup on a new instance inside the locale filter should not run the filter again.' );
		$this->assertArrayHasKey( 'DE', $countries, 'Countries should be complete.' );
		$this->assertArrayHasKey( 'DE', $this->locale_filter_lookups[0], 'The locale filter should get complete data from its own instance.' );
	}

	/**
	 * @testdox Data built by a locale filter while the locale is being resolved is not cached for a guessed locale.
	 */
	public function test_geographical_data_built_while_resolving_the_locale_is_not_cached(): void {
		$this->active_locale = 'fr_FR';
		$this->register_geographical_filters();
		add_filter( 'determine_locale', array( $this, 'read_geographical_data_in_locale_filter' ), 20 );
		$sut                           = new WC_Countries();
		$this->locale_filter_countries = $sut;

		$this->assertSame( 'fr_FR', $sut->get_countries()['US'], 'Countries should use the resolved request locale.' );

		$this->active_locale = 'en_US';

		$this->assertSame( 'en_US', $sut->get_countries()['US'], 'Countries for another locale should not reuse data built while resolving an earlier locale.' );
	}

	/**
	 * @testdox A locale filter that reads the default customer location gets it while WooCommerce reads allowed countries.
	 *
	 * @dataProvider provide_default_location_locale_filters
	 *
	 * @param string $hook              Locale hook the callback is attached to.
	 * @param string $allowed_countries Allowed countries mode.
	 */
	public function test_locale_filter_reading_default_location_during_allowed_countries_lookup( $hook, $allowed_countries ): void {
		update_option( 'woocommerce_allowed_countries', $allowed_countries );
		update_option( 'woocommerce_specific_allowed_countries', array( 'US', 'CA' ) );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_default_customer_address', DefaultCustomerAddress::BASE );
		$sut                      = new WC_Countries();
		$this->original_countries = WC()->countries;
		WC()->countries           = $sut;
		add_filter( $hook, array( $this, 'read_default_location_in_locale_filter' ), 20 );

		foreach ( array( 'cold', 'warm' ) as $cache_state ) {
			$this->assertArrayHasKey( 'CA', $sut->get_allowed_countries(), "Allowed countries should be complete on a $cache_state cache." );
		}

		$this->assertSame( 1, $this->locale_filter_max_depth, 'Reading the default location inside the locale filter should not run the filter again.' );
		$this->assertNotEmpty( $this->locale_filter_default_locations, 'The locale filter should have read the default customer location.' );

		foreach ( $this->locale_filter_default_locations as $location ) {
			$this->assertSame(
				array(
					'country' => 'US',
					'state'   => 'CA',
				),
				$location,
				'The locale filter should get the store base location.'
			);
		}
	}

	/**
	 * @testdox Helpers use country and state properties that a subclass declares.
	 */
	public function test_helpers_use_geographical_properties_declared_by_a_subclass(): void {
		$sut = new class() extends WC_Countries {
			/**
			 * Declared country names.
			 *
			 * @var array
			 */
			public $countries = array(
				'US' => 'Declared US',
				'CA' => 'Declared CA',
			);

			/**
			 * Declared state names.
			 *
			 * @var array
			 */
			public $states = array(
				'US' => array( 'CA' => 'Declared California' ),
			);
		};

		$this->assert_helpers_use_geographical_properties( $sut, 'Declared' );
	}

	/**
	 * @testdox Helpers use country and state properties assigned on an instance.
	 */
	public function test_helpers_use_geographical_properties_assigned_on_an_instance(): void {
		$sut            = new WC_Countries();
		$sut->countries = array(
			'US' => 'Assigned US',
			'CA' => 'Assigned CA',
		);
		$sut->states    = array(
			'US' => array( 'CA' => 'Assigned California' ),
		);

		$this->assert_helpers_use_geographical_properties( $sut, 'Assigned' );
	}

	/**
	 * @testdox Asking for the locale while geographical data is being built does not build it again.
	 *
	 * @dataProvider provide_geographical_builds
	 *
	 * @param string $build_hook Filter that runs while the data is being built.
	 * @param string $lookup     WC_Countries method that builds the data.
	 */
	public function test_locale_lookup_while_building_geographical_data_does_not_build_it_again( $build_hook, $lookup ): void {
		add_filter( 'determine_locale', array( $this, 'filter_active_locale' ) );
		$sut = new WC_Countries();
		$sut->$lookup();
		$this->locale_filter_countries = $sut;
		$this->locale_filter_lookup    = $lookup;
		add_filter( 'locale', array( $this, 'read_geographical_data_in_locale_filter' ), 20 );
		add_filter( $build_hook, array( $this, 'determine_locale_while_building' ) );
		$this->active_locale = 'fr_FR';

		$data = $sut->$lookup();

		$this->assertLessThan( self::LOCALE_FILTER_DEPTH_LIMIT, $this->locale_filter_max_depth, 'Asking for the locale during a build should not start the same build again.' );
		$this->assertNotEmpty( $data, 'The data built for the new locale should be complete.' );
	}

	/**
	 * @testdox Switching locales keeps per-locale caches while a locale filter reads geographical data.
	 *
	 * @dataProvider provide_switched_locale_lookups
	 *
	 * @param string $lookup WC_Countries method the locale filter calls.
	 */
	public function test_switching_locales_keeps_per_locale_caches_with_a_geographical_locale_filter( $lookup ): void {
		$this->use_locale_switcher_with( 'fr_FR' );
		add_filter( 'woocommerce_countries', array( $this, 'stamp_country_names_with_switched_locale' ) );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'stamp_country_locale_with_switched_locale' ) );
		$sut = new WC_Countries();
		$this->assert_switched_locale_stamps( $sut, 'site' );
		$this->locale_filter_countries = $sut;
		$this->locale_filter_lookup    = $lookup;
		add_filter( 'locale', array( $this, 'read_geographical_data_in_locale_filter' ), 20 );

		$this->assertTrue( switch_to_locale( 'fr_FR' ), 'The test locale should be available to switch to.' );
		$this->assert_switched_locale_stamps( $sut, 'fr_FR' );

		restore_previous_locale();
		$this->assert_switched_locale_stamps( $sut, 'site' );

		$this->assertSame(
			array(
				'site'  => 1,
				'fr_FR' => 1,
			),
			$this->switched_locale_stamps['countries'],
			'Countries should be built once per locale.'
		);
		$this->assertLessThan( self::LOCALE_FILTER_DEPTH_LIMIT, $this->locale_filter_max_depth, 'The locale filter should not recurse through the geographical lookups.' );
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
	 * @testdox 'get_address_fields' carries a locale-only 'hidden' flag through the merge with the default fields.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hidden Value of the 'hidden' flag set via the locale filter.
	 * @return void
	 */
	public function test_get_address_fields_includes_hidden_flag_from_locale( $hidden ) {
		$locale_filter = function ( $locale ) use ( $hidden ) {
			$locale['ES']['postcode']['hidden'] = $hidden;
			return $locale;
		};
		add_filter( 'woocommerce_get_country_locale', $locale_filter );

		$fields = ( new WC_Countries() )->get_address_fields( 'ES', 'billing_' );

		remove_filter( 'woocommerce_get_country_locale', $locale_filter );

		$this->assertSame( $hidden, $fields['billing_postcode']['hidden'], 'The locale hidden flag should survive the merge with the default fields.' );
	}

	/**
	 * @testdox 'get_address_fields' does not add a 'hidden' key when the locale does not define one.
	 *
	 * @return void
	 */
	public function test_get_address_fields_has_no_hidden_key_when_locale_does_not_define_one() {
		$fields = ( new WC_Countries() )->get_address_fields( 'ES', 'billing_' );

		$this->assertArrayNotHasKey( 'hidden', $fields['billing_postcode'] );
	}

	/**
	 * @testdox 'get_address_fields' hides the postcode and makes the state optional for Qatar, matching the UAE.
	 *
	 * @return void
	 */
	public function test_get_address_fields_relaxes_postcode_and_state_for_qatar() {
		$fields = ( new WC_Countries() )->get_address_fields( 'QA', 'billing_' );

		$this->assertTrue( $fields['billing_postcode']['hidden'] ?? false, 'Qatar addresses have no postcode, so the field should be hidden.' );
		$this->assertFalse( $fields['billing_postcode']['required'], 'Qatar addresses have no postcode, so the field should not be required.' );
		$this->assertFalse( $fields['billing_state']['required'], 'WooCommerce lists no subdivisions for Qatar, so the state should not be required.' );
		$this->assertFalse( $fields['billing_state']['hidden'] ?? false, 'Qatar follows the UAE, which keeps the state visible rather than hiding it like Bahrain and Kuwait.' );
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
	 * Invalid values returned by the WordPress locale filter.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provide_invalid_locale_values() {
		return array(
			'array'                 => array( array() ),
			'non-stringable object' => array( new stdClass() ),
		);
	}

	/**
	 * Specific-country state methods and their options.
	 *
	 * @return array<string, array<string>>
	 */
	public function provide_specific_country_state_methods() {
		return array(
			'allowed states'  => array( 'get_allowed_country_states', 'woocommerce_allowed_countries', 'woocommerce_specific_allowed_countries' ),
			'shipping states' => array( 'get_shipping_country_states', 'woocommerce_ship_to_countries', 'woocommerce_specific_ship_to_countries' ),
		);
	}

	/**
	 * Locale hooks and the geographical lookups a callback on them makes.
	 *
	 * @return array<string, array<string>>
	 */
	public function provide_locale_filter_geographical_lookups() {
		return array(
			'locale reads countries'               => array( 'locale', 'get_countries' ),
			'locale reads states'                  => array( 'locale', 'get_states' ),
			'locale reads country locale settings' => array( 'locale', 'get_country_locale' ),
			'determine_locale reads countries'     => array( 'determine_locale', 'get_countries' ),
			'determine_locale reads continents'    => array( 'determine_locale', 'get_continents' ),
			'pre_determine_locale reads states'    => array( 'pre_determine_locale', 'get_states' ),
		);
	}

	/**
	 * Filters that run while geographical data is built, and the lookups that build it.
	 *
	 * @return array<string, array<string>>
	 */
	public function provide_geographical_builds() {
		return array(
			'country list'            => array( 'woocommerce_countries', 'get_countries' ),
			'state list'              => array( 'woocommerce_states', 'get_states' ),
			'country locale settings' => array( 'woocommerce_get_country_locale', 'get_country_locale' ),
		);
	}

	/**
	 * Locale hooks and allowed-country modes for a locale filter that reads the default customer location.
	 *
	 * @return array<string, array<string>>
	 */
	public function provide_default_location_locale_filters() {
		return array(
			'locale, all countries'                => array( 'locale', 'all' ),
			'locale, specific countries'           => array( 'locale', 'specific' ),
			'determine_locale, all countries'      => array( 'determine_locale', 'all' ),
			'determine_locale, specific countries' => array( 'determine_locale', 'specific' ),
		);
	}

	/**
	 * Lookups a locale filter makes while locales are switched.
	 *
	 * @return array<string, array<string>>
	 */
	public function provide_switched_locale_lookups() {
		return array(
			'countries'               => array( 'get_countries' ),
			'country locale settings' => array( 'get_country_locale' ),
		);
	}

	/**
	 * Replace the global locale switcher with one that can switch to the given locale.
	 *
	 * @param string $locale Locale to make available.
	 */
	private function use_locale_switcher_with( $locale ): void {
		$this->available_test_locale = $locale;
		add_filter( 'get_available_languages', array( $this, 'add_available_test_locale' ) );

		$this->original_locale_switcher = $GLOBALS['wp_locale_switcher'];
		$GLOBALS['wp_locale_switcher']  = new WP_Locale_Switcher(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The core switcher reads available languages once, on construction.
		$GLOBALS['wp_locale_switcher']->init();
	}

	/**
	 * Record a build for the switched locale and return its stamp.
	 *
	 * @param string $type Data type.
	 * @return string
	 */
	private function record_switched_locale_stamp( $type ) {
		$stamp = is_locale_switched() ? $GLOBALS['wp_locale_switcher']->get_switched_locale() : 'site';

		if ( ! isset( $this->switched_locale_stamps[ $type ][ $stamp ] ) ) {
			$this->switched_locale_stamps[ $type ][ $stamp ] = 0;
		}

		++$this->switched_locale_stamps[ $type ][ $stamp ];

		return $stamp;
	}

	/**
	 * Assert that the country and state helpers read the object's countries and states properties.
	 *
	 * @param WC_Countries $sut    The system under test.
	 * @param string       $prefix Prefix of the property values.
	 */
	private function assert_helpers_use_geographical_properties( WC_Countries $sut, $prefix ): void {
		add_filter( 'pre_option_woocommerce_allowed_countries', array( $this, 'return_all_countries' ) );
		add_filter( 'pre_option_woocommerce_ship_to_countries', array( $this, 'return_all_countries' ) );
		update_option( 'woocommerce_default_country', 'US:CA' );

		$this->assertSame( "$prefix US", $sut->get_allowed_countries()['US'], 'Allowed countries should use the countries property.' );
		$this->assertSame( "$prefix US", $sut->get_shipping_countries()['US'], 'Shipping countries should use the countries property.' );
		$this->assertSame( "$prefix California", $sut->get_allowed_country_states()['US']['CA'], 'Allowed states should use the states property.' );
		$this->assertSame( "$prefix California", $sut->get_shipping_country_states()['US']['CA'], 'Shipping states should use the states property.' );
		$this->assertStringContainsString(
			"$prefix CA",
			$sut->get_formatted_address(
				array(
					'city'    => 'Toronto',
					'country' => 'CA',
				)
			),
			'Formatted addresses should use the countries property.'
		);
	}

	/**
	 * Assert that countries and country locale settings carry the expected switched-locale stamp.
	 *
	 * @param WC_Countries $sut   The system under test.
	 * @param string       $stamp Expected stamp.
	 */
	private function assert_switched_locale_stamps( WC_Countries $sut, $stamp ): void {
		$this->assertSame( $stamp, $sut->get_countries()['US'], "Countries should use the $stamp locale cache." );
		$this->assertSame( $stamp, $sut->get_country_locale()['US']['postcode']['label'], "Country locale settings should use the $stamp locale." );
	}

	/**
	 * Register the geographical data filters used by locale tests.
	 */
	private function register_geographical_filters() {
		add_filter( 'determine_locale', array( $this, 'filter_active_locale' ) );
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
		$this->assertSame( $locale, $sut->get_allowed_country_states()['US']['CA'], 'Allowed states should use the active locale.' );
		$this->assertSame( $locale, $sut->get_shipping_country_states()['US']['CA'], 'Shipping states should use the active locale.' );
		$this->assertSame( $locale, $sut->get_continents()['NA']['name'], 'Continents should use the active locale.' );
		$this->assertSame( $locale, $sut->get_shipping_continents()['NA']['name'], 'Shipping continents should use the active locale.' );
	}
}
