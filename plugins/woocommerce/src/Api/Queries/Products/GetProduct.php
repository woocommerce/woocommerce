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
#[Name( 'product' )]
#[Description( 'Retrieve a single product by ID.' )]
#[RequiredCapability( 'read_product' )]
class GetProduct {
	/**
	 * Authorize access to a specific product.
	 *
	 * Admins can read any product. Non-admin users can only read products
	 * they authored themselves.
	 *
	 * Every inaccessible case throws `AuthorizationException('Product not
	 * found.')` — whether the ID doesn't exist, points at a non-product
	 * post type, or points at a product the caller doesn't own. This
	 * prevents callers from enumerating product IDs vs non-product post
	 * IDs via the response they get back (which would otherwise be "not
	 * found" vs "no permission").
	 *
	 * @param int  $id              The product ID.
	 * @param bool $_preauthorized  Whether the declared capability check passed.
	 * @return bool Whether the current user can read this product.
	 * @throws AuthorizationException When the product is not accessible.
	 */
	public function authorize( int $id, bool $_preauthorized ): bool {
		$post = get_post( $id );

		if ( ! $post || 'product' !== $post->post_type ) {
			throw new AuthorizationException( 'Product not found.' );
		}

		// Honor the declared #[RequiredCapability].
		if ( $_preauthorized ) {
			return true;
		}

		// Non-admin users can only read their own products. Throw the same
		// "not found" exception rather than returning false — a distinct
		// "you don't have permission" error here would tell the caller
		// that the ID is a product (just not theirs), leaking the
		// product-ID space vs the rest of the post-ID space.
		//
		// Reject guest users explicitly: get_current_user_id() returns 0
		// for unauthenticated callers, and products created via WP-CLI,
		// imports, or programmatic inserts without an author can have
		// post_author = 0 — a bare `!==` check would mis-grant access to
		// anonymous callers for those products.
		$current_user_id = get_current_user_id();
		if ( 0 === $current_user_id || $current_user_id !== (int) $post->post_author ) {
			throw new AuthorizationException( 'Product not found.' );
		}

		return true;
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
