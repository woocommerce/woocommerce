<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Utils\Products;

/**
 * Repository for product persistence operations.
 *
 * Designed to be injected via the DI container into commands
 * that need to load or save products.
 */
class ProductRepository {
	/**
	 * Find a product by ID.
	 *
	 * @param int $id The product ID.
	 * @return ?\WC_Product The product, or null if not found.
	 */
	public function find( int $id ): ?\WC_Product {
		$product = wc_get_product( $id );
		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Save a product.
	 *
	 * @param \WC_Product $product The product to save.
	 */
	public function save( \WC_Product $product ): void {
		$product->save();
	}
}
