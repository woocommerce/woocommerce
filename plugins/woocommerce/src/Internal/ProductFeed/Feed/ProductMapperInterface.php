<?php
/**
 * Product Mapper Interface.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

/**
 * Product Mapper Interface.
 *
 * @since 10.5.0
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
