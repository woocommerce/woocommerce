<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Class with general utility methods related to products.
 */
class ProductUtil {
	/**
	 * Delete the transients related to a specific product.
	 *
	 * @param int $product_id The product id.
	 * @return void
	 */
	public function delete_product_specific_transients( int $product_id ) {
		$product_specific_transient_names = array(
			'wc_product_children_',
			'wc_var_prices_',
			'wc_related_',
			'wc_child_has_weight_',
			'wc_child_has_dimensions_',
		);

		foreach ( $product_specific_transient_names as $transient ) {
			delete_transient( $transient . $product_id );
		}
	}
}
