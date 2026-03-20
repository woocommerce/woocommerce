<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Queries\Products;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\ReturnType;
use Automattic\WooCommerce\Api\AuthorizationException;
use Automattic\WooCommerce\Api\Interfaces\Product;
use Automattic\WooCommerce\Api\Utils\Products\ProductMapper;

/**
 * Query to retrieve a single product by ID.
 *
 * Demonstrates: authorize(), $_query_info, AuthorizationException.
 *
 * Authorization logic: admins (manage_woocommerce) can read any product,
 * non-admin users can only read their own products.
 */
#[Name( 'Product' )]
#[Description( 'Retrieve a single product by ID.' )]
#[RequiredCapability( 'read_product' )]
class GetProduct {
	/**
	 * Authorize access to a specific product.
	 *
	 * Admins can read any product. Non-admin users can only read products
	 * they authored themselves.
	 *
	 * @param int $id The product ID.
	 * @return bool Whether the current user can read this product.
	 * @throws AuthorizationException When the product does not exist.
	 */
	public function authorize( int $id ): bool {
		$post = get_post( $id );

		if ( ! $post || 'product' !== $post->post_type ) {
			throw new AuthorizationException( 'Product not found.' );
		}

		// Admins can read any product.
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		// Non-admin users can only read their own products.
		return get_current_user_id() === (int) $post->post_author;
	}

	/**
	 * Retrieve a product by ID.
	 *
	 * @param int    $id          The product ID.
	 * @param ?array $_query_info Unified query info tree from the GraphQL request.
	 * @return ?object
	 */
	#[ReturnType( Product::class )]
	public function execute(
		#[Description( 'The ID of the product to retrieve.' )]
		int $id,
		?array $_query_info = null,
	): ?object {
		$wc_product = wc_get_product( $id );

		if ( ! $wc_product instanceof \WC_Product ) {
			return null;
		}

		return ProductMapper::from_wc_product( $wc_product, $_query_info );
	}
}
