<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\Main;
use Automattic\WooCommerce\Internal\Api\Settings;
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
		$this->sut = new Settings();
	}

	/**
	 * Clean up filters registered by tests so global state doesn't leak.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_get_sections_advanced', array( $this->sut, 'add_section' ) );
		remove_filter( 'woocommerce_get_settings_advanced', array( $this->sut, 'add_settings' ), 10 );
		parent::tearDown();
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
}
