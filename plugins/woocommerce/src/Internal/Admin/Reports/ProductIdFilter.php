<?php
/**
 * ProductIdFilter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the condition restricting a products report to the products its filters resolve to.
 *
 * Both products report data stores need it and one extends the other, so it lives here rather than
 * on a data store, where it would become part of what extensions inherit.
 *
 * @internal
 *
 * @since 11.2.0
 */
class ProductIdFilter {

	/**
	 * Returns the condition restricting a report to a set of products.
	 *
	 * A search resolves to a subquery, the `categories` and `products` filters to an ID list. The
	 * subquery already covers those filters, since it is built restricted to the same IDs.
	 *
	 * @since 11.2.0
	 *
	 * @param string $column            Product ID column to compare, qualified with its table name.
	 * @param string $search_subquery   Statement the `search` argument resolves to, from
	 *                                  `ProductSearchQuery::get_ids_subquery()`. Empty when the
	 *                                  report carries no search.
	 * @param array  $included_products Product IDs the `categories` and `products` filters resolve to.
	 * @return string SQL condition, or an empty string when the report is not restricted.
	 */
	public static function get_condition( string $column, string $search_subquery, array $included_products ): string {
		if ( '' !== $search_subquery ) {
			return "{$column} IN ( {$search_subquery} )";
		}

		$id_list = implode( ',', $included_products );

		return $id_list ? "{$column} IN ( {$id_list} )" : '';
	}
}
