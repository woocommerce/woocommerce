<?php
/**
 * Product Query Interface.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for querying mapped product data without file delivery concerns.
 *
 * Enables pull/query consumers (e.g., UCP, live APIs) to retrieve product data
 * using the same mapping and validation pipeline as push-feed integrations,
 * without depending on CSV or file delivery semantics.
 *
 * @since 10.10.0
 */
interface ProductQueryInterface {
	/**
	 * Query mapped products with pagination.
	 *
	 * Returns an array containing mapped product data that passed validation,
	 * along with pagination metadata.
	 *
	 * @since 10.10.0
	 *
	 * @param array $args Additional query arguments to merge with integration defaults.
	 * @param int   $page Page number (1-based).
	 * @param int   $limit Products per page.
	 * @return array {
	 *     @type array $products        Mapped product data that passed validation
	 *                                  (post-validation). Use count($products) for the
	 *                                  number actually returned on this page.
	 *     @type int   $total           Pre-validation total: count of products matched
	 *                                  by the underlying query, before the validator
	 *                                  filter. Use this for total catalog size and to
	 *                                  compute pagination. May be greater than
	 *                                  count($products) when invalid entries are dropped.
	 *     @type int   $max_num_pages   Total pages available, based on the pre-validation
	 *                                  $total and the requested $limit.
	 * }
	 */
	public function query_products( array $args, int $page, int $limit ): array;

	/**
	 * Get a single mapped product by ID.
	 *
	 * Honors the integration's query args (status, type, tax_query) so excluded
	 * products (e.g., draft, pos-hidden) are not returned.
	 *
	 * @since 10.10.0
	 *
	 * @param int $product_id Product ID.
	 * @return array|null Mapped product data, or null if not found or excluded.
	 */
	public function get_product( int $product_id ): ?array;

	/**
	 * Get the total count of products matching the current query configuration.
	 *
	 * @since 10.10.0
	 *
	 * @param array $args Additional query arguments to merge with integration defaults.
	 * @return int Total matching product count.
	 */
	public function get_total_count( array $args = array() ): int;
}
