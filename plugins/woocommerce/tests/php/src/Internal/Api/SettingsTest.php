<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

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
	 * @testdox add_section appends the graphql section while preserving existing ones (PHP 8.1+).
	 */
	public function test_add_section_appends_graphql_section(): void {
		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'GraphQL settings require PHP 8.1+.' );
		}

		$result = $this->sut->add_section( array( 'features' => 'Features' ) );

		$this->assertArrayHasKey( Settings::SECTION_ID, $result );
		$this->assertArrayHasKey( 'features', $result );
	}

	/**
	 * @testdox add_section is a no-op on PHP < 8.1.
	 */
	public function test_add_section_is_noop_on_unsupported_php(): void {
		if ( PHP_VERSION_ID >= 80100 ) {
			$this->markTestSkipped( 'Only relevant on PHP < 8.1.' );
		}

		$input  = array( 'features' => 'Features' );
		$result = $this->sut->add_section( $input );

		$this->assertSame( $input, $result );
	}

	/**
	 * @testdox add_settings defines the GET endpoint checkbox with a 'yes' default (PHP 8.1+).
	 */
	public function test_add_settings_defines_get_endpoint_checkbox(): void {
		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'GraphQL settings require PHP 8.1+.' );
		}

		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_GET_ENDPOINT_ENABLED, $by_id );
		$this->assertSame( 'checkbox', $by_id[ Main::OPTION_GET_ENDPOINT_ENABLED ]['type'] );
		$this->assertSame( 'yes', $by_id[ Main::OPTION_GET_ENDPOINT_ENABLED ]['default'] );
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
}
