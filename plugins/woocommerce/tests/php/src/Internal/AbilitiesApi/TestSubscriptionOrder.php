<?php
/**
 * TestSubscriptionOrder class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * An order object of a type that an extension adds, such as a subscription.
 */
class TestSubscriptionOrder extends \WC_Order {

	/**
	 * Order type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'shop_subscription';
	}
}
