<?php
/**
 * Product Loader class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loader for products.
 */
class ProductLoader {
	/**
	 * Retrieves products from WooCommerce.
	 *
	 * @see wc_get_products()
	 *
	 * @param array $args The arguments to pass to wc_get_products().
	 * @return array|stdClass Number of pages and an array of product objects if
	 *                        paginate is true, or just an array of values.
	 */
	public function get_products( $args ) {
		return wc_get_products( $args );
	}
}
