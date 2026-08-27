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
	 * A search resolves to a subquery, the `categories` and `products` filters to an ID list.
	 *
	 * @since 11.2.0
	 *
	 * @param string          $column            Product ID column to compare, qualified with its table name.
	 * @param string|string[] $search_terms      Value of the `search` query argument.
	 * @param array           $included_products Product IDs the `categories` and `products` filters resolve to.
	 * @return string SQL condition, or an empty string when the report is not restricted.
	 */
	public static function get_condition( string $column, $search_terms, array $included_products ): string {
		$search_subquery = ProductSearchQuery::get_ids_subquery( $search_terms, $included_products );
		if ( '' !== $search_subquery ) {
			return "{$column} IN ( {$search_subquery} )";
		}

		$id_list = implode( ',', $included_products );

		return $id_list ? "{$column} IN ( {$id_list} )" : '';
	}
}
