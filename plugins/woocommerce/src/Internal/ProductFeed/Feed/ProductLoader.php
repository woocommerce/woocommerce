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
 *
 * @since 10.5.0
 */
class ProductLoader {
	/**
	 * Retrieves products from WooCommerce.
	 *
	 * @since 10.5.0
	 *
	 * @see wc_get_products()
	 *
	 * @param array $args The arguments to pass to wc_get_products().
	 * @return array|\stdClass Number of pages and an array of product objects if
	 *                         paginate is true, or just an array of values.
	 */
	public function get_products( array $args ) {
		return wc_get_products( $args );
	}
}
