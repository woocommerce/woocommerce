<?php
namespace Automattic\WooCommerce\Blocks\Tests\Mocks;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

/**
 * CartMock used to test cart block functions.
 */
class CartCheckoutUtilsMock extends CartCheckoutUtils {
	/**
	 * Protected test wrapper for deep_sort_with_accents.
	 *
	 * @param array $array The array we want to sort.
	 */
	public static function deep_sort_test( $array ) {
		return self::deep_sort_with_accents( $array );
	}
}
