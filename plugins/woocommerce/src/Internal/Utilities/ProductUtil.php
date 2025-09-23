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
}
