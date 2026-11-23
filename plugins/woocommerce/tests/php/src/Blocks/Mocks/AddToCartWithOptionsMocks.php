<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

/**
 * Maps the Add to Cart with Options block names to their mock classes.
 */
class AddToCartWithOptionsMocks {

	/**
	 * Mock class for each block name, without the `woocommerce/` namespace.
	 */
	private const MOCKS = array(
		'add-to-cart-with-options'                      => AddToCartWithOptionsMock::class,
		'add-to-cart-with-options-quantity-selector'    => AddToCartWithOptionsQuantitySelectorMock::class,
		'add-to-cart-with-options-grouped-product-selector' => AddToCartWithOptionsGroupedProductSelectorMock::class,
		'add-to-cart-with-options-grouped-product-item' => AddToCartWithOptionsGroupedProductItemMock::class,
		'add-to-cart-with-options-grouped-product-item-selector' => AddToCartWithOptionsGroupedProductItemSelectorMock::class,
		'add-to-cart-with-options-variation-selector'   => AddToCartWithOptionsVariationSelectorMock::class,
		'add-to-cart-with-options-variation-selector-attribute' => AddToCartWithOptionsVariationSelectorAttributeMock::class,
		'add-to-cart-with-options-variation-selector-attribute-name' => AddToCartWithOptionsVariationSelectorAttributeNameMock::class,
	);

	/**
	 * Get mock classes keyed by full block name, for per-test registry replacement.
	 *
	 * @param string ...$block_names Block names without the namespace; omit to get all mocks.
	 * @return array<string, string>
	 */
	public static function get_mock_classes( string ...$block_names ): array {
		$mock_classes = array();
		foreach ( $block_names ? $block_names : array_keys( self::MOCKS ) as $block_name ) {
			$mock_classes[ 'woocommerce/' . $block_name ] = self::MOCKS[ $block_name ];
		}
		return $mock_classes;
	}
}
