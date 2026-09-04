<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Api\Infrastructure\GraphQLControllerBase;
use Automattic\WooCommerce\Api\Infrastructure\Main;
use Automattic\WooCommerce\Internal\Api\QueryCache;
use Automattic\WooCommerce\Internal\Api\Settings;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WC_Unit_Test_Case;

/**
 * Tests for the GraphQL API Settings class.
 */
class SettingsTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var Settings
	 */
	private $sut;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_or_disable_feature( true );
		$this->sut = new Settings();
	}

	/**
	 * Clean up filters and options registered by tests so global state doesn't leak.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_get_sections_advanced', array( $this->sut, 'add_section' ) );
		remove_filter( 'woocommerce_get_settings_advanced', array( $this->sut, 'add_settings' ), 10 );
		remove_filter(
			'woocommerce_admin_settings_sanitize_option_' . Main::OPTION_ENDPOINT_URL,
			array( $this->sut, 'sanitize_endpoint_url' ),
			10
		);
		delete_option( Main::OPTION_ENDPOINT_URL );
		$this->enable_or_disable_feature( false );
		parent::tearDown();
	}

	/**
	 * Enable or disable the GraphQL API feature.
	 *
	 * @param bool $enable True to enable, false to disable.
	 */
	private function enable_or_disable_feature( bool $enable ): void {
		update_option(
			wc_get_container()->get( FeaturesController::class )->feature_enable_option_name( 'dual_code_graphql_api' ),
			$enable ? 'yes' : 'no'
		);
	}

	/**
	 * @testdox register hooks add_section and add_settings into WooCommerce's advanced settings filters.
	 */
	public function test_register_hooks_both_advanced_filters(): void {
		$this->sut->register();

		$this->assertNotFalse(
			has_filter( 'woocommerce_get_sections_advanced', array( $this->sut, 'add_section' ) ),
			'add_section should be hooked to woocommerce_get_sections_advanced.'
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_get_settings_advanced', array( $this->sut, 'add_settings' ) ),
			'add_settings should be hooked to woocommerce_get_settings_advanced.'
		);
	}

	/**
	 * @testdox add_section appends the graphql section while preserving existing ones.
	 */
	public function test_add_section_appends_graphql_section(): void {
		$result = $this->sut->add_section( array( 'features' => 'Features' ) );

		$this->assertArrayHasKey( Settings::SECTION_ID, $result );
		$this->assertArrayHasKey( 'features', $result );
	}

	/**
	 * @testdox add_settings defines the GET endpoint checkbox with a 'yes' default.
	 */
	public function test_add_settings_defines_get_endpoint_checkbox(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_GET_ENDPOINT_ENABLED, $by_id );
		$this->assertSame( 'checkbox', $by_id[ Main::OPTION_GET_ENDPOINT_ENABLED ]['type'] );
		$this->assertSame( 'yes', $by_id[ Main::OPTION_GET_ENDPOINT_ENABLED ]['default'] );
	}

	/**
	 * @testdox add_settings defines the APQ checkbox with a 'yes' default (PHP 8.1+).
	 */
	public function test_add_settings_defines_apq_checkbox(): void {
		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'GraphQL settings require PHP 8.1+.' );
		}

		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_APQ_ENABLED, $by_id );
		$this->assertSame( 'checkbox', $by_id[ Main::OPTION_APQ_ENABLED ]['type'] );
		$this->assertSame( 'yes', $by_id[ Main::OPTION_APQ_ENABLED ]['default'] );
	}

	/**
	 * @testdox add_settings defines the endpoint URL text field with the default constant as default.
	 */
	public function test_add_settings_defines_endpoint_url_field(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_ENDPOINT_URL, $by_id );
		$this->assertSame( 'text', $by_id[ Main::OPTION_ENDPOINT_URL ]['type'] );
		$this->assertSame( GraphQLControllerBase::DEFAULT_ENDPOINT_URL, $by_id[ Main::OPTION_ENDPOINT_URL ]['default'] );
	}

	/**
	 * @testdox sanitize_endpoint_url returns the normalized input for a well-formed URL.
	 */
	public function test_sanitize_endpoint_url_accepts_valid_url(): void {
		$result = $this->sut->sanitize_endpoint_url( null, array(), 'wc/v4/graphql' );
		$this->assertSame( 'wc/v4/graphql', $result );
	}

	/**
	 * @testdox sanitize_endpoint_url strips surrounding slashes.
	 */
	public function test_sanitize_endpoint_url_strips_surrounding_slashes(): void {
		$result = $this->sut->sanitize_endpoint_url( null, array(), '/wc/v4/graphql/' );
		$this->assertSame( 'wc/v4/graphql', $result );
	}

	/**
	 * @testdox sanitize_endpoint_url rejects invalid input and returns the previously stored value.
	 * @dataProvider provider_invalid_endpoint_url_inputs
	 *
	 * @param string $raw_input The raw submitted value.
	 */
	public function test_sanitize_endpoint_url_rejects_invalid_input( string $raw_input ): void {
		update_option( Main::OPTION_ENDPOINT_URL, 'wc/v4/graphql' );

		$result = $this->sut->sanitize_endpoint_url( null, array(), $raw_input );

		$this->assertSame( 'wc/v4/graphql', $result, 'Invalid input should not overwrite the previously stored value.' );
	}

	/**
	 * Inputs the sanitize handler should reject.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_invalid_endpoint_url_inputs(): array {
		return array(
			'empty string'       => array( '' ),
			'slashes only'       => array( '///' ),
			'single segment'     => array( 'graphql' ),
			'spaces in segment'  => array( 'wc/my graphql' ),
			'special characters' => array( 'wc/graph*ql' ),
		);
	}

	/**
	 * @testdox sanitize_endpoint_url falls back to the stored value when the raw input is not a string.
	 * @dataProvider provider_non_string_endpoint_url_inputs
	 *
	 * @param mixed $raw_input The raw submitted value (null, array, etc.).
	 */
	public function test_sanitize_endpoint_url_handles_non_string_input( $raw_input ): void {
		update_option( Main::OPTION_ENDPOINT_URL, 'wc/v4/graphql' );

		$result = $this->sut->sanitize_endpoint_url( null, array(), $raw_input );

		$this->assertSame( 'wc/v4/graphql', $result, 'Non-string input should not overwrite the previously stored value.' );
	}

	/**
	 * Non-string raw inputs the sanitize handler may receive from POST data.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function provider_non_string_endpoint_url_inputs(): array {
		return array(
			'null'  => array( null ),
			'array' => array( array( 'wc/graphql' ) ),
		);
	}

	/**
	 * @testdox add_settings returns the input unchanged on PHP < 8.1.
	 */
	public function test_add_settings_is_noop_on_unsupported_php(): void {
		if ( PHP_VERSION_ID >= 80100 ) {
			$this->markTestSkipped( 'Only relevant on PHP < 8.1.' );
		}

		$input  = array( array( 'id' => 'existing' ) );
		$result = $this->sut->add_settings( $input, Settings::SECTION_ID );

		$this->assertSame( $input, $result );
	}

	/**
	 * @testdox add_settings defines the ObjectCache checkbox with a 'yes' default.
	 */
	public function test_add_settings_defines_object_cache_checkbox(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_OBJECT_CACHE_ENABLED, $by_id );
		$this->assertSame( 'checkbox', $by_id[ Main::OPTION_OBJECT_CACHE_ENABLED ]['type'] );
		$this->assertSame( 'yes', $by_id[ Main::OPTION_OBJECT_CACHE_ENABLED ]['default'] );
	}

	/**
	 * @testdox add_settings defines the OPcache checkbox with a 'yes' default.
	 */
	public function test_add_settings_defines_opcache_checkbox(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_OPCACHE_ENABLED, $by_id );
		$this->assertSame( 'checkbox', $by_id[ Main::OPTION_OPCACHE_ENABLED ]['type'] );
		$this->assertSame( 'yes', $by_id[ Main::OPTION_OPCACHE_ENABLED ]['default'] );
	}

	/**
	 * @testdox add_settings defines the max query depth field with min=1 and the default constant as default.
	 */
	public function test_add_settings_defines_max_query_depth_field(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_MAX_QUERY_DEPTH, $by_id );
		$this->assertSame( 'number', $by_id[ Main::OPTION_MAX_QUERY_DEPTH ]['type'] );
		$this->assertSame(
			(string) GraphQLControllerBase::DEFAULT_MAX_QUERY_DEPTH,
			$by_id[ Main::OPTION_MAX_QUERY_DEPTH ]['default']
		);
		$this->assertSame( '1', $by_id[ Main::OPTION_MAX_QUERY_DEPTH ]['custom_attributes']['min'] );
	}

	/**
	 * @testdox add_settings defines the max query complexity field with min=1 and the default constant as default.
	 */
	public function test_add_settings_defines_max_query_complexity_field(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_MAX_QUERY_COMPLEXITY, $by_id );
		$this->assertSame( 'number', $by_id[ Main::OPTION_MAX_QUERY_COMPLEXITY ]['type'] );
		$this->assertSame(
			(string) GraphQLControllerBase::DEFAULT_MAX_QUERY_COMPLEXITY,
			$by_id[ Main::OPTION_MAX_QUERY_COMPLEXITY ]['default']
		);
		$this->assertSame( '1', $by_id[ Main::OPTION_MAX_QUERY_COMPLEXITY ]['custom_attributes']['min'] );
	}

	/**
	 * @testdox add_settings returns the original settings unchanged when the section id does not match.
	 */
	public function test_add_settings_passes_through_for_other_sections(): void {
		$existing = array( array( 'id' => 'placeholder' ) );

		$result = $this->sut->add_settings( $existing, 'some_other_section' );

		$this->assertSame( $existing, $result );
	}

	/**
	 * @testdox add_section returns sections unchanged when the feature is disabled.
	 */
	public function test_add_section_does_not_register_when_feature_is_off(): void {
		$this->enable_or_disable_feature( false );

		$result = $this->sut->add_section( array( 'features' => 'Features' ) );

		$this->assertArrayNotHasKey( Settings::SECTION_ID, $result );
	}

	/**
	 * @testdox add_settings returns settings unchanged when the feature is disabled.
	 */
	public function test_add_settings_does_not_register_when_feature_is_off(): void {
		$this->enable_or_disable_feature( false );

		$result = $this->sut->add_settings( array(), Settings::SECTION_ID );

		$this->assertSame( array(), $result );
	}

	/**
	 * @testdox add_settings defines the parsed query cache TTL field with min=1 and the default constant as default.
	 */
	public function test_add_settings_defines_query_cache_ttl_field(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_QUERY_CACHE_TTL, $by_id );
		$this->assertSame( 'number', $by_id[ Main::OPTION_QUERY_CACHE_TTL ]['type'] );
		$this->assertSame(
			(string) QueryCache::DEFAULT_CACHE_TTL,
			$by_id[ Main::OPTION_QUERY_CACHE_TTL ]['default']
		);
		$this->assertSame( '1', $by_id[ Main::OPTION_QUERY_CACHE_TTL ]['custom_attributes']['min'] );
	}
}
