<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

/**
 * CartMock used to test cart block functions.
 */
class CartCheckoutUtilsMock extends CartCheckoutUtils {
	/**
	 * Protected test wrapper for deep_sort_with_accents.
	 *
	 * @param array $array_to_sort The array we want to sort.
	 */
	public static function deep_sort_test( $array_to_sort ) {
		return self::deep_sort_with_accents( $array_to_sort );
	}

	/**
	 * Protected test wrapper for migrate_checkout_block_field_visibility_attributes.
	 */
	public static function migrate_checkout_block_field_visibility_attributes_test() {
		return self::migrate_checkout_block_field_visibility_attributes();
	}

	/**
	 * Mock templates for testing.
	 *
	 * @var array
	 */
	public static $mock_templates = [];

	/**
	 * Whether to mock block theme.
	 *
	 * @var bool
	 */
	public static $mock_block_theme = false;

	/**
	 * Override is_cart_block_default for testing with mocked data.
	 *
	 * @return bool
	 */
	public static function is_cart_block_default() {
		if ( self::$mock_block_theme && ! empty( self::$mock_templates ) ) {
			foreach ( self::$mock_templates as $template ) {
				if ( 'cart' === $template->slug && false !== strpos( $template->content, 'wp:woocommerce/cart' ) ) {
					return true;
				}
			}
			return false;
		}
		return parent::is_cart_block_default();
	}

	/**
	 * Override is_checkout_block_default for testing with mocked data.
	 *
	 * @return bool
	 */
	public static function is_checkout_block_default() {
		if ( self::$mock_block_theme && ! empty( self::$mock_templates ) ) {
			foreach ( self::$mock_templates as $template ) {
				if ( 'checkout' === $template->slug && false !== strpos( $template->content, 'wp:woocommerce/checkout' ) ) {
					return true;
				}
			}
			return false;
		}
		return parent::is_checkout_block_default();
	}
}
