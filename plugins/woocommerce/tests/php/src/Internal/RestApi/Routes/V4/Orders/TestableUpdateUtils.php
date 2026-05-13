<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Orders;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\UpdateUtils;
use WC_Order;

/**
 * Testable subclass that exposes the protected update_meta_data method.
 */
class TestableUpdateUtils extends UpdateUtils {

	/**
	 * Public wrapper for the protected update_meta_data method.
	 *
	 * @param WC_Order $order     Order object.
	 * @param array    $meta_data Meta data array.
	 */
	public function call_update_meta_data( WC_Order $order, array $meta_data ) {
		$this->update_meta_data( $order, $meta_data );
	}
}
