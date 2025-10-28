<?php
/**
 * Product mapper interface.
 *
 * @package WooCommerce\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\ProductCatalog\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for mapping WooCommerce products to catalog format.
 *
 * @package WooCommerce\ProductCatalog
 */
interface ProductMapperInterface {
	/**
	 * Map a product to a feed row.
	 *
	 * @param \WC_Product $product The product to map.
	 * @return array The feed row.
	 */
	public function map_product( \WC_Product $product ): array;
}
