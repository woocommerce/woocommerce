<?php
/**
 * Fulfillments Data Store Interface
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;

/**
 * Interface FulfillmentsDataStoreInterface
 *
 * @package Automattic\WooCommerce\Internal\DataStores\Fulfillments
 */
interface FulfillmentsDataStoreInterface {
	/**
	 * Read the fulfillment data.
	 *
	 * @param string $entity_type The entity type.
	 * @param string $entity_id The entity ID.
	 *
	 * @return Fulfillment[] Fulfillment object.
	 */
	public function read_fulfillments( string $entity_type, string $entity_id ): array;
}
