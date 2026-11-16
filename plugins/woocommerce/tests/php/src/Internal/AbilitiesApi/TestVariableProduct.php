<?php
/**
 * TestVariableProduct class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * A variable product type of an extension, whose variations have a class of their own.
 */
class TestVariableProduct extends \WC_Product_Variable {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'test-variable';
	}
}
