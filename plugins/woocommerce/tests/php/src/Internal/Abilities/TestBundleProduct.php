<?php
/**
 * TestBundleProduct class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * A product type whose class extends WC_Product, like a bundle.
 */
class TestBundleProduct extends \WC_Product {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'test-bundle';
	}
}
