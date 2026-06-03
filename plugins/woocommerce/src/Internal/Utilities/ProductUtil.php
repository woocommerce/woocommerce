<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Class with general utility methods related to products.
 */
class ProductUtil {
	/**
	 * Delete the transients related to a specific product.
	 * If the product is a variation, delete the transients for the parent too.
	 *
	 * @param WC_Product|int $product_or_id The product or the product id.
	 * @return void
	 */
	public function delete_product_specific_transients( $product_or_id ) {
		$parent_id = 0;
		if ( $product_or_id instanceof \WC_Product ) {
			$product    = $product_or_id;
			$product_id = $product->get_id();
		} else {
			$product_id = $product_or_id;
			$product    = wc_get_product( $product_id );
		}

		if ( $product instanceof \WC_Product_Variation ) {
			$parent_id = $product->get_parent_id();
		}

		$product_specific_transient_names = array(
			'wc_product_children_',
			'wc_var_prices_',
			'wc_related_',
			'wc_child_has_weight_',
			'wc_child_has_dimensions_',
		);

		foreach ( $product_specific_transient_names as $transient ) {
			delete_transient( $transient . $product_id );
			if ( $parent_id ) {
				delete_transient( $transient . $parent_id );
			}
		}
	}

	/**
	 * Prime featured and gallery image attachment caches for a collection of products in a single
	 * batched query, instead of priming each product's images separately.
	 *
	 * @param array $products Products whose image attachments should be primed. Non-product items are ignored.
	 * @return void
	 */
	public function prime_image_caches( array $products ): void {
		$products  = array_filter( $products, static fn( $product ) => $product instanceof \WC_Product );
		$featured  = array_map( static fn( $product ) => $product->get_image_id(), $products );
		$gallery   = array_map( static fn( $product ) => $product->get_gallery_image_ids(), $products );
		$image_ids = array_filter( array_unique( array_map( 'intval', array_merge( $featured, ...$gallery ) ) ) );
		if ( ! empty( $image_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $image_ids );
		}
	}
}
