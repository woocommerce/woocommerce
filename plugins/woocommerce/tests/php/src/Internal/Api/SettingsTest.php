<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\GraphQLController;
use Automattic\WooCommerce\Internal\Api\Main;
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
	 * Clean up filters registered by tests so global state doesn't leak.
	 */
	public function tearDown(): void {
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
	 * @testdox add_settings defines the max query depth field with min=1 and the default constant as default.
	 */
	public function test_add_settings_defines_max_query_depth_field(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_MAX_QUERY_DEPTH, $by_id );
		$this->assertSame( 'number', $by_id[ Main::OPTION_MAX_QUERY_DEPTH ]['type'] );
		$this->assertSame(
			(string) GraphQLController::DEFAULT_MAX_QUERY_DEPTH,
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
			(string) GraphQLController::DEFAULT_MAX_QUERY_COMPLEXITY,
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
}
