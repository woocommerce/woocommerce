<?php
/**
 * TestVariationProduct class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * A variation class of an extension.
 */
class TestVariationProduct extends \WC_Product_Variation {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'test_variation';
	}

	/**
	 * A variation of this class is also a variation for WooCommerce.
	 *
	 * @param string|string[] $type Type or types to check.
	 * @return bool
	 */
	public function is_type( $type ) {
		return 'variation' === $type || ( is_array( $type ) && in_array( 'variation', $type, true ) ) || parent::is_type( $type );
	}
}
