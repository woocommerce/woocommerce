<?php
/**
 * TestSubscriptionProduct class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * A product type whose class extends the simple product class, like a subscription.
 */
class TestSubscriptionProduct extends \WC_Product_Simple {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'test-subscription';
	}
}
