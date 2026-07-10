<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Caches\ProductCountCache;

/**
 * Class with general utility methods related to products.
 */
class ProductUtil {
	/**
	 * Delete all product transients for a set of products.
	 *
	 * Fixed-name transients are deleted once for the whole set, and the
	 * woocommerce_delete_product_transients action fires once per product ID.
	 *
	 * @param array $product_ids Product IDs whose transients are being deleted.
	 * @return void
	 */
	public function delete_product_transients_for_products( array $product_ids ): void {
		$product_ids = array_unique( array_map( 'absint', $product_ids ) );

		// Transient data to clear with a fixed name which may be stale after product updates.
		$transients_to_clear = array(
			'wc_products_onsale',
			'wc_featured_products',
			'wc_outofstock_count',
			'wc_low_stock_count',
		);

		foreach ( $transients_to_clear as $transient ) {
			delete_transient( $transient );
		}

		$product_ids_to_clear = array_filter( $product_ids );
		if ( ! empty( $product_ids_to_clear ) ) {
			$this->delete_product_specific_transients_for_products( $product_ids_to_clear );
		}

		// Kept for compatibility, WooCommerce core doesn't use product transient versions anymore.
		\WC_Cache_Helper::get_transient_version( 'product', true );

		foreach ( $product_ids as $product_id ) {
			/**
			 * Fires after product transients are deleted.
			 *
			 * @since 2.3.0
			 *
			 * @param int $product_id Product ID whose transients were deleted.
			 */
			do_action( 'woocommerce_delete_product_transients', $product_id );
		}
	}

	/**
	 * Delete the transients related to a specific product.
	 * If the product is a variation, delete the transients for the parent too.
	 *
	 * @param WC_Product|int $product_or_id The product or the product id.
	 * @return void
	 */
	public function delete_product_specific_transients( $product_or_id ) {
		$this->delete_product_specific_transients_for_products( array( $product_or_id ) );
	}

	/**
	 * Delete the transients related to a set of products.
	 * If a product is a variation, delete the transients for the parent too.
	 *
	 * @param array $products_or_ids Products or product ids.
	 * @return void
	 */
	public function delete_product_specific_transients_for_products( array $products_or_ids ) {
		$product_ids = array();

		foreach ( $products_or_ids as $product_or_id ) {
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

			$product_ids[] = $product_id;
			if ( $parent_id ) {
				$product_ids[] = $parent_id;
			}
		}

		$product_ids = array_unique( array_filter( array_map( 'absint', $product_ids ) ) );

		$product_specific_transient_names = array(
			'wc_product_children_',
			'wc_var_prices_',
			'wc_related_',
			'wc_child_has_weight_',
			'wc_child_has_dimensions_',
		);

		foreach ( $product_ids as $product_id ) {
			foreach ( $product_specific_transient_names as $transient ) {
				delete_transient( $transient . $product_id );
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

	/**
	 * Counts per-status number of products of a given post type.
	 *
	 * @since 11.0.0
	 *
	 * @param string $post_type Post type (e.g. 'product', 'product_variation').
	 * @return array<string,int>
	 */
	public function get_counts_for_type( string $post_type ): array {
		$product_count_cache = wc_get_container()->get( ProductCountCache::class );
		$count_per_status    = $product_count_cache->get( $post_type );

		if ( null === $count_per_status ) {
			$count_per_status = array_merge(
				array_fill_keys( array_keys( get_post_stati() ), 0 ),
				(array) wp_count_posts( $post_type )
			);

			$product_count_cache->set_multiple( $post_type, $count_per_status );
		}

		return array_map( 'intval', $count_per_status );
	}
}
