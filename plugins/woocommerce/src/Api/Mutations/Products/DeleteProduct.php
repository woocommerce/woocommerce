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

		// Capture the raw return value. A `(bool)` cast would coerce
		// filter-originated `WP_Error` objects to `true`, reporting failure
		// as success; we need to detect that case explicitly and surface
		// the underlying error instead.
		$deleted = $wc_product->delete( $force );

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		if ( $deleted instanceof \WP_Error ) {
			throw new ApiException(
				$deleted->get_error_message(),
				'INTERNAL_ERROR',
				status_code: 500,
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		return true === $deleted;
	}
}
