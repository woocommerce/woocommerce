<?php
/**
 * Fulfillments Data Store Interface
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Fulfillments;

/**
 * Interface FulfillmentsDataStoreInterface
 *
 * @package Automattic\WooCommerce\Internal\DataStores\Fulfillments
 */
interface FulfillmentsDataStoreInterface {
	/**
	 * Read the fulfillment data.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return Fulfillment[] Fulfillment object.
	 */
	public function read_fulfillments( int $order_id ): array;
}
