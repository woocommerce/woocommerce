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
}
