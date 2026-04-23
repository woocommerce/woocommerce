<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\GraphQLController;
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
	 *
	 * Skips on PHP < 8.1 because the settings fields reference
	 * GraphQLController constants, and that class uses PHP 8.0+ syntax that
	 * cannot be parsed on 7.4. In production the class is only loaded after
	 * {@see Main::is_enabled()} gates on PHP 8.1+; these tests replicate the
	 * same gate so the autoload never triggers a parse error.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'GraphQL settings tests require PHP 8.1+.' );
		}

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

	/**
	 * @testdox add_settings defines the endpoint URL text field with the default constant as default.
	 */
	public function test_add_settings_defines_endpoint_url_field(): void {
		$fields = $this->sut->add_settings( array(), Settings::SECTION_ID );
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertArrayHasKey( Main::OPTION_ENDPOINT_URL, $by_id );
		$this->assertSame( 'text', $by_id[ Main::OPTION_ENDPOINT_URL ]['type'] );
		$this->assertSame( GraphQLController::DEFAULT_ENDPOINT_URL, $by_id[ Main::OPTION_ENDPOINT_URL ]['default'] );
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
}
