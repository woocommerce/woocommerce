<?php
/**
 * Unit tests for wc-template-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Template
 */

declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
/**
 * Class WC_Template_Functions_Tests.
 *
 * @covers ::wc_get_formatted_cart_item_data
 */
class WC_Template_Functions_Tests extends \WC_Unit_Test_Case {

	/**
	 * Build a minimal cart item array around a stub variation product.
	 *
	 * The stub variation only needs to support the type check and `get_name()`
	 * since `wc_get_formatted_cart_item_data` operates entirely off the cart
	 * item array, not a real product save.
	 *
	 * @param string $name      The product name (including any variation suffix).
	 * @param array  $variation Variation attributes keyed by `attribute_*`.
	 * @return array
	 */
	private function build_variation_cart_item( string $name, array $variation ): array {
		$product = $this->getMockBuilder( WC_Product_Variation::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_type', 'get_name' ) )
			->getMock();

		$product->method( 'is_type' )->willReturnCallback(
			static function ( $type ) {
				return 'variation' === $type;
			}
		);
		$product->method( 'get_name' )->willReturn( $name );

		return array(
			'data'      => $product,
			'variation' => $variation,
		);
	}

	/**
	 * @testdox Should not render any metadata when every variation attribute is part of the variation title.
	 */
	public function test_returns_empty_when_all_attributes_in_title(): void {
		$cart_item = $this->build_variation_cart_item(
			'Test Hoodie - Blue, S',
			array(
				'attribute_color' => 'Blue',
				'attribute_size'  => 'S',
			)
		);

		$output = wc_get_formatted_cart_item_data( $cart_item, true );

		$this->assertSame( '', $output, 'When every attribute is in the title, no metadata should be rendered.' );
	}

	/**
	 * @testdox Should render every variation attribute as metadata when at least one is missing from the title.
	 */
	public function test_renders_all_attributes_when_one_missing_from_title(): void {
		$cart_item = $this->build_variation_cart_item(
			'Test T-Shirt - Blue',
			array(
				'attribute_color' => 'Blue',
				'attribute_size'  => 'S',
			)
		);

		$output = wc_get_formatted_cart_item_data( $cart_item, true );

		$this->assertStringContainsString( 'Color: Blue', $output, 'Color should be rendered as metadata when another attribute is missing from the title.' );
		$this->assertStringContainsString( 'Size: S', $output, 'Size should be rendered as metadata when it is missing from the title.' );
	}

	/**
	 * @testdox Should skip empty variation attribute values.
	 */
	public function test_skips_empty_attribute_values(): void {
		$cart_item = $this->build_variation_cart_item(
			'Test Beanie',
			array(
				'attribute_color' => '',
			)
		);

		$output = wc_get_formatted_cart_item_data( $cart_item, true );

		$this->assertSame( '', $output, 'Attributes with empty values should not be rendered.' );
	}
}
