<?php
/**
 * Fulfillments Data Store Interface
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;

/**
 * Interface FulfillmentsDataStoreInterface
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore
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
