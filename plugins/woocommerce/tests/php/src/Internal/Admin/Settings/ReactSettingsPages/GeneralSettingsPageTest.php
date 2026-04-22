<?php
/**
 * GeneralSettingsPage tests.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\ReactSettingsPages;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPages\GeneralSettingsPage;
use WC_Unit_Test_Case;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPages\GeneralSettingsPage
 */
class GeneralSettingsPageTest extends WC_Unit_Test_Case {

	/**
	 * @var GeneralSettingsPage
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new GeneralSettingsPage();
	}

	/**
	 * @testdox get_extra_type_map returns empty array for every section
	 */
	public function test_get_extra_type_map_is_empty(): void {
		$this->assertSame( array(), $this->sut->get_extra_type_map( '' ) );
		$this->assertSame( array(), $this->sut->get_extra_type_map( 'anything' ) );
	}

	/**
	 * @testdox get_extra_supported_types returns empty array for every section
	 */
	public function test_get_extra_supported_types_is_empty(): void {
		$this->assertSame( array(), $this->sut->get_extra_supported_types( '' ) );
		$this->assertSame( array(), $this->sut->get_extra_supported_types( 'anything' ) );
	}

	/**
	 * @testdox get_field_options returns null for field ids this page does not synthesize
	 */
	public function test_get_field_options_returns_null_for_unrelated_fields(): void {
		$this->assertNull( $this->sut->get_field_options( 'woocommerce_store_address', array(), '' ) );
		$this->assertNull( $this->sut->get_field_options( 'totally_unrelated', array(), '' ) );
	}

	/**
	 * @testdox get_field_options synthesizes a currency list including USD
	 */
	public function test_get_field_options_currency_includes_usd(): void {
		$options = $this->sut->get_field_options( 'woocommerce_currency', array(), '' );

		$this->assertIsArray( $options );
		$this->assertNotEmpty( $options );

		foreach ( $options as $opt ) {
			$this->assertArrayHasKey( 'label', $opt );
			$this->assertArrayHasKey( 'value', $opt );
		}

		$values = array_column( $options, 'value' );
		$this->assertContains( 'USD', $values );

		$usd_label = null;
		foreach ( $options as $opt ) {
			if ( 'USD' === $opt['value'] ) {
				$usd_label = $opt['label'];
				break;
			}
		}
		$this->assertIsString( $usd_label );
		$this->assertStringContainsString( 'USD', $usd_label );
		$this->assertStringContainsString( 'dollar', $usd_label );
	}

	/**
	 * @testdox get_field_options synthesizes country options that include US:CA and country:state shape
	 */
	public function test_get_field_options_country_includes_state_keyed_entries(): void {
		$options = $this->sut->get_field_options( 'woocommerce_default_country', array(), '' );

		$this->assertIsArray( $options );
		$this->assertNotEmpty( $options );

		$values = array_column( $options, 'value' );

		// US has states, so there must be entries like "US:CA", "US:TX", etc.
		$us_state_entries = array_filter( $values, static fn( $v ) => is_string( $v ) && str_starts_with( $v, 'US:' ) );
		$this->assertNotEmpty( $us_state_entries, 'Expected at least one US:<state> entry' );
		$this->assertContains( 'US:CA', $values );

		// At least one country without states should appear as a bare country code.
		$bare_country_entries = array_filter( $values, static fn( $v ) => is_string( $v ) && false === strpos( $v, ':' ) );
		$this->assertNotEmpty( $bare_country_entries, 'Expected at least one bare country-code entry (country with no states)' );
	}
}
