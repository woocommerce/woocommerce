<?php
/**
 * Checkout mode and page selection for extension browser tests.
 *
 * @package WooCommerce\ExtensionTooling
 */

namespace Automattic\WooCommerce\ExtensionTooling;

/**
 * Shared methods for test fixtures that define THEME_LEGACY and THEME_BLOCKS.
 */
trait Checkout_Mode {

	/**
	 * Select the checkout mode and activate its theme when needed.
	 *
	 * @param string $mode Checkout mode.
	 */
	public static function switch_test_mode( $mode ) {
		$mode  = ( 'blocks' === $mode ) ? 'blocks' : 'legacy';
		$theme = ( 'blocks' === $mode ) ? self::THEME_BLOCKS : self::THEME_LEGACY;

		update_option( 'e2e_tests_mode', $mode );

		if ( get_stylesheet() !== $theme ) {
			switch_theme( $theme );
		}
	}

	/**
	 * Select the cart page for the active checkout mode.
	 *
	 * @param int|string $cart_page_id Current cart page ID.
	 * @return int|string Cart page ID.
	 */
	public static function filter_cart_page_id( $cart_page_id ) {
		if ( 'legacy' === get_option( 'e2e_tests_mode', 'legacy' ) ) {
			$legacy_page_id = (int) get_option( 'tests_legacy_woocommerce_cart_page_id', 0 );
			if ( $legacy_page_id > 0 ) {
				return $legacy_page_id;
			}
		}

		return $cart_page_id;
	}

	/**
	 * Select the checkout page for the active checkout mode.
	 *
	 * @param int|string $checkout_page_id Current checkout page ID.
	 * @return int|string Checkout page ID.
	 */
	public static function filter_checkout_page_id( $checkout_page_id ) {
		if ( 'legacy' === get_option( 'e2e_tests_mode', 'legacy' ) ) {
			$legacy_page_id = (int) get_option( 'tests_legacy_woocommerce_checkout_page_id', 0 );
			if ( $legacy_page_id > 0 ) {
				return $legacy_page_id;
			}
		}

		return $checkout_page_id;
	}
}
