<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces;

/**
 * Defines the contract for classes responsible for transforming
 * raw platform data into a standardized format suitable for the WooCommerce Importer.
 */
interface PlatformMapperInterface {

	/**
	 * Maps raw platform product data to a standardized array format.
	 *
	 * @param object $platform_data The raw product data object from the source platform (e.g., Shopify product node).
	 *
	 * @return array A standardized array representing the product, understandable by the WooCommerce_Product_Importer.
	 *               The specific structure of this array needs to be defined and adhered to.
	 */
	public function map_product_data( object $platform_data ): array;
}
