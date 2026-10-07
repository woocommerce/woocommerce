<?php
/**
 * TestMembershipProduct class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * A product of a type that an extension adds.
 */
class TestMembershipProduct extends \WC_Product_Simple {

	/**
	 * Product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'membership';
	}
}
