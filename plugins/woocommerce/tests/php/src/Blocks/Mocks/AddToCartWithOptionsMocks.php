<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

/**
 * Registers the Add to Cart with Options mock blocks, which `init` skips under a classic theme.
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
	 * Register the given mock blocks, skipping any already registered, since the registry outlives a test.
	 *
	 * @param string ...$block_names Block names without the `woocommerce/` namespace.
	 */
	public static function register( string ...$block_names ): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		foreach ( $block_names as $block_name ) {
			if ( ! $registry->is_registered( 'woocommerce/' . $block_name ) ) {
				$mock_class = self::MOCKS[ $block_name ];
				new $mock_class();
			}
		}
	}

	/**
	 * Register every mock block that is not registered yet.
	 */
	public static function register_all(): void {
		self::register( ...array_keys( self::MOCKS ) );
	}
}
