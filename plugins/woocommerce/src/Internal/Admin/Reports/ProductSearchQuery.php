<?php
/**
 * ProductSearchQuery class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the SQL that resolves a free-text product search to product IDs.
 *
 * @internal
 *
 * @since 11.2.0
 */
class ProductSearchQuery {

	/**
	 * Normalizes the `search` REST argument into a list of terms.
	 *
	 * @since 11.2.0
	 *
	 * @param string|string[] $value Raw argument value, a list of terms or a comma separated string.
	 * @return string[] Search terms.
	 */
	public static function parse_terms( $value ) {
		// Split on commas only. The array coercion WordPress applies to a string argument
		// (`wp_parse_list()`) also splits on whitespace, which would break multi-word terms.
		$terms = is_array( $value ) ? $value : explode( ',', (string) $value );

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', $terms ),
				function ( $term ) {
					return '' !== $term;
				}
			)
		);
	}

	/**
	 * Returns the REST collection parameter definition for the product search.
	 *
	 * @since 11.2.0
	 *
	 * @return array Parameter definition.
	 */
	public static function get_collection_param() {
		return array(
			'description'       => __( 'Limit result to products whose name or SKU matches any of the given terms.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'parse_terms' ),
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'string',
			),
		);
	}

	/**
	 * Returns a SELECT statement resolving the given search terms to product IDs.
	 *
	 * The statement yields a single `product_id` column, for use as a derived table or as the
	 * right-hand side of an `IN (...)` clause.
	 *
	 * @since 11.2.0
	 *
	 * @param string[] $terms           Search terms. A product matches if it matches any term.
	 * @param int[]    $restrict_to_ids Optional. Product IDs to intersect the results with.
	 * @return string SQL statement, or an empty string when there is nothing to search for.
	 */
	public static function get_ids_subquery( $terms, $restrict_to_ids = array() ) {
		global $wpdb;

		$terms = self::parse_terms( $terms );

		if ( empty( $terms ) ) {
			return '';
		}

		$sku_enabled   = wc_product_sku_enabled();
		$term_clauses  = array();
		$where_clauses = array();

		foreach ( $terms as $term ) {
			$clause = $wpdb->prepare( 'posts.post_title LIKE %s', '%' . $wpdb->esc_like( $term ) . '%' );
			if ( $sku_enabled ) {
				// Matches Admin\API\Products, which compares the SKU against the term unwrapped and
				// unescaped, so a LIKE wildcard in the term stays a wildcard here. Escaping it would
				// make the report disagree with the search box on what the term matches.
				$clause .= $wpdb->prepare( ' OR product_meta_lookup.sku LIKE %s', $term );
			}

			$term_clauses[] = "( {$clause} )";
		}

		$statuses    = get_post_stati( array( 'exclude_from_search' => false ) );
		$status_list = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $status_list is a generated list of %s placeholders.
		$where_clauses[] = $wpdb->prepare( "posts.post_status IN ( {$status_list} )", $statuses );
		$where_clauses[] = '( ' . implode( ' OR ', $term_clauses ) . ' )';

		$restrict_to_ids = (array) $restrict_to_ids;
		if ( ! empty( $restrict_to_ids ) ) {
			$where_clauses[] = 'posts.ID IN ( ' . implode( ',', array_map( 'intval', $restrict_to_ids ) ) . ' )';
		}

		$join = $sku_enabled
			? " LEFT JOIN {$wpdb->wc_product_meta_lookup} AS product_meta_lookup ON posts.ID = product_meta_lookup.product_id"
			: '';

		$where = implode( ' AND ', $where_clauses );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- All interpolated values are prepared above.
		return "SELECT DISTINCT posts.ID AS product_id
			FROM {$wpdb->posts} AS posts{$join}
			WHERE posts.post_type = 'product' AND {$where}";
	}
}
