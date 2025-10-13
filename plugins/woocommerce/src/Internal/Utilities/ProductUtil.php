<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Class with general utility methods related to products.
 */
class ProductUtil {
	/**
	 * Get the last modified date for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return int|null Timestamp of last modification (or creation), or null if product doesn't exist.
	 */
	public function get_last_modified_date( int $product_id ): ?int {
		global $wpdb;

		// Query the posts table directly we're using the default CPT data store (the default),
		// otherwise fallback to retrieving the full product object.

		$data_store = \WC_Data_Store::load( 'product' );
		if ( $data_store instanceof \WC_Product_Data_Store_CPT ) {
			$post_date = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(NULLIF(post_modified_gmt, %s), post_date_gmt) FROM {$wpdb->posts} WHERE ID = %d",
					'0000-00-00 00:00:00',
					$product_id
				)
			);

			return $post_date ? strtotime( $post_date ) : null;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}

		$date_modified = $product->get_date_modified();
		$date_created  = $product->get_date_created();

		return $date_modified ? $date_modified->getTimestamp() : ( $date_created ? $date_created->getTimestamp() : null );
	}

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
}
