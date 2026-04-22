<?php
/**
 * ProductsSettingsPage tests.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\ReactSettingsPages;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPages\ProductsSettingsPage;
use WC_Unit_Test_Case;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPages\ProductsSettingsPage
 */
class ProductsSettingsPageTest extends WC_Unit_Test_Case {

	/**
	 * @var ProductsSettingsPage
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProductsSettingsPage();
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
	 * @testdox get_field_options returns null for unrelated field ids
	 */
	public function test_get_field_options_returns_null_for_unrelated_fields(): void {
		$this->assertNull( $this->sut->get_field_options( 'totally_unrelated', array(), '' ) );
		$this->assertNull( $this->sut->get_field_options( 'woocommerce_currency', array(), '' ) );
	}

	/**
	 * @testdox get_field_options synthesizes weight-unit options including "kg"
	 */
	public function test_get_field_options_weight_units_include_kg(): void {
		$options = $this->sut->get_field_options( 'woocommerce_weight_unit', array(), '' );

		$this->assertIsArray( $options );
		$this->assertNotEmpty( $options );

		foreach ( $options as $opt ) {
			$this->assertArrayHasKey( 'label', $opt );
			$this->assertArrayHasKey( 'value', $opt );
		}

		$values = array_column( $options, 'value' );
		$this->assertContains( 'kg', $values );
	}

	/**
	 * @testdox get_field_options synthesizes dimension-unit options including "cm"
	 */
	public function test_get_field_options_dimension_units_include_cm(): void {
		$options = $this->sut->get_field_options( 'woocommerce_dimension_unit', array(), '' );

		$this->assertIsArray( $options );
		$this->assertNotEmpty( $options );

		$values = array_column( $options, 'value' );
		$this->assertContains( 'cm', $values );
	}

	/**
	 * @testdox get_field_options synthesizes product-type options including "simple"
	 */
	public function test_get_field_options_product_types_include_simple(): void {
		$options = $this->sut->get_field_options( 'woocommerce_product_type', array(), '' );

		$this->assertIsArray( $options );
		$this->assertNotEmpty( $options );

		$values = array_column( $options, 'value' );
		$this->assertContains( 'simple', $values );
	}

	/**
	 * @testdox get_field_options synthesizes a shop-page list with the "Select a page…" placeholder at the top
	 */
	public function test_get_field_options_shop_page_has_placeholder(): void {
		$options = $this->sut->get_field_options( 'woocommerce_shop_page_id', array(), '' );

		$this->assertIsArray( $options );
		$this->assertNotEmpty( $options );

		// Shape: every entry has label + value.
		foreach ( $options as $opt ) {
			$this->assertArrayHasKey( 'label', $opt );
			$this->assertArrayHasKey( 'value', $opt );
		}

		// First entry must be the "Select a page…" placeholder with empty value.
		$this->assertSame( '', $options[0]['value'] );
		$this->assertSame( __( 'Select a page…', 'woocommerce' ), $options[0]['label'] );
	}
}
