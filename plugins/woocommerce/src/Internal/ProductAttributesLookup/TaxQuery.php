<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductAttributesLookup;

/**
 * Tax query class introduced to optimize SQL performance in bigger product/category catalogs.
 */
class TaxQuery extends \WP_Tax_Query {
	/**
	 * Generates SQL JOIN and WHERE clauses for a "first-order" query clause.
	 *
	 * @since 10.9.0
	 *
	 * @param array $clause       Query clause (passed by reference).
	 * @param array $parent_query Parent query array.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a first-order query.
	 *
	 *     @type string[] $join  Array of SQL fragments to append to the main JOIN clause.
	 *     @type string[] $where Array of SQL fragments to append to the main WHERE clause.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query ) {
		global $wpdb;

		// Optimization note: targeting only the 'IN' operator, where the default 'LEFT JOIN' causes performance issues.
		if ( 'IN' !== $clause['operator'] ) {
			return parent::get_sql_for_clause( $clause, $parent_query );
		}

		// Call the parent method so it does necessary cleanup with its private APIs.
		$fallback = parent::get_sql_for_clause( $clause, $parent_query );
		if ( array( '' ) === $fallback['join'] && array( '0 = 1' ) === $fallback['where'] ) {
			return $fallback;
		}

		// Optimization note: 'fan-out LEFT JOIN' -> 'pre-materialized subquery' transition for better SQL performance.
		$terms = implode( ',', array_map( 'absint', $clause['terms'] ) );
		return array(
			'join'  => array( '' ),
			'where' => array( "$this->primary_table.$this->primary_id_column IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ( $terms ) )" ),
		);
	}
}
