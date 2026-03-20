<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Mutations\Products;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;

/**
 * Mutation to delete a product.
 *
 * Demonstrates: mutation returning bool.
 */
#[Description( 'Delete a product.' )]
#[RequiredCapability( 'manage_woocommerce' )]
class DeleteProduct {
	/**
	 * Execute the mutation.
	 *
	 * @param int  $id    The product ID.
	 * @param bool $force Whether to permanently delete (bypass trash).
	 * @return bool Whether the product was deleted.
	 * @throws ApiException When the product is not found.
	 */
	public function execute(
		#[Description( 'The ID of the product to delete.' )]
		int $id,
		#[Description( 'Whether to permanently delete the product (bypass trash).' )]
		bool $force = false,
	): bool {
		$wc_product = wc_get_product( $id );

		if ( ! $wc_product instanceof \WC_Product ) {
			throw new ApiException( 'Product not found.', 'NOT_FOUND', status_code: 404 );
		}

		$wc_product->delete( $force );

		return $force || 0 === $wc_product->get_id();
	}
}
