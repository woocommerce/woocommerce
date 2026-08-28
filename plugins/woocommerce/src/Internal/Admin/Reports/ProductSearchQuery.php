<?php
/**
 * ProductSearchQuery class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Reports;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the SQL that resolves a free-text product search to product IDs, and the condition
 * restricting a products report to the products its filters resolve to.
 *
 * @internal
 *
 * @since 11.2.0
 */
class ProductSearchQuery {

	/**
	 * Query variable carrying the search terms into the WP_Query filters below.
	 *
	 * @var string
	 */
	private const TERMS_QUERY_VAR = 'wc_analytics_product_search';

	/**
	 * Alias of the product meta lookup table joined for the SKU comparison.
	 *
	 * @var string
	 */
	private const LOOKUP_ALIAS = 'wc_analytics_product_search_lookup';

	/**
	 * Normalizes the `search` REST argument into a list of terms.
	 *
	 * @since 11.2.0
	 *
	 * @param string|string[] $value Raw argument value, a list of terms or a comma separated string.
	 * @return string[] Search terms.
	 */
	public static function parse_terms( $value ) {
		// Not `wp_parse_list()`, which also splits on whitespace and would break multi-word terms.
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
	 * The statement yields a single `product_id` column, for use as a derived table or in an `IN (...)` clause.
	 *
	 * @since 11.2.0
	 *
	 * @param string|string[] $terms           Search terms. A product matches if it matches any term.
	 * @param int[]           $restrict_to_ids Optional. Product IDs to intersect the results with. An ID
	 *                                         that cannot belong to a product matches nothing.
	 * @return string SQL statement, or an empty string when there is nothing to search for.
	 */
	public static function get_ids_subquery( $terms, $restrict_to_ids = array() ) {
		$terms = self::parse_terms( $terms );

		if ( empty( $terms ) ) {
			return '';
		}

		$args = array(
			'post_type'           => 'product',
			// Matches the search box, and a drafted product can still have sales to report.
			'post_status'         => 'any',
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			// The report orders and pages the result itself, so the subquery does not have to.
			'orderby'             => 'none',
			'no_found_rows'       => true,
			// The query is never run, so its empty result is not worth caching.
			'cache_results'       => false,
			self::TERMS_QUERY_VAR => $terms,
		);

		$restrict_to_ids = (array) $restrict_to_ids;
		if ( ! empty( $restrict_to_ids ) ) {
			// WP_Query runs `post__in` through `absint()`, which would read the `-1` the report
			// filters use for an empty set as product ID 1. Clamp to 0, which matches nothing.
			$args['post__in'] = array_map( static fn( $id ) => max( 0, (int) $id ), $restrict_to_ids );
		}

		$statement = self::build_statement( $args );

		// A plugin filtering `posts_fields` can add columns, so name the one this returns.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- WP_Query prepares the statement it builds.
		return "SELECT DISTINCT ID AS product_id FROM ( {$statement} ) AS wc_analytics_product_search_results";
	}

	/**
	 * Returns the condition restricting a report to a set of products.
	 *
	 * A search resolves to a subquery, the `categories` and `products` filters to an ID list. The
	 * subquery already covers those filters, since it is built restricted to the same IDs.
	 *
	 * Not a data store method: both products data stores need it and one extends the other, so it
	 * would become part of what extensions inherit.
	 *
	 * @since 11.2.0
	 *
	 * @param string $column            Product ID column to compare, qualified with its table name.
	 * @param string $search_subquery   Statement the `search` argument resolves to, from
	 *                                  `get_ids_subquery()`. Empty when the report carries no search.
	 * @param array  $included_products Product IDs the `categories` and `products` filters resolve to.
	 * @return string SQL condition, or an empty string when the report is not restricted.
	 */
	public static function get_id_condition( string $column, string $search_subquery, array $included_products ): string {
		if ( '' !== $search_subquery ) {
			return "{$column} IN ( {$search_subquery} )";
		}

		$id_list = implode( ',', $included_products );

		return $id_list ? "{$column} IN ( {$id_list} )" : '';
	}

	/**
	 * Returns the product IDs the given search terms resolve to.
	 *
	 * For callers that need the matches themselves rather than a statement to compose with.
	 * An empty list means the terms matched nothing, which is not the same as `null`, meaning
	 * there was nothing to search for.
	 *
	 * @since 11.2.0
	 *
	 * @param string|string[] $terms           Search terms. A product matches if it matches any term.
	 * @param int[]           $restrict_to_ids Optional. Product IDs to intersect the results with.
	 * @return int[]|null Matching product IDs, or null when there is nothing to search for.
	 */
	public static function get_ids( $terms, $restrict_to_ids = array() ) {
		global $wpdb;

		$subquery = self::get_ids_subquery( $terms, $restrict_to_ids );
		if ( '' === $subquery ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $subquery is built from prepared fragments; the containing report is what caches.
		return array_map( 'intval', $wpdb->get_col( $subquery ) );
	}

	/**
	 * Adds the SKU lookup table to a product search query.
	 *
	 * @internal Hooked on `posts_join` for the duration of the search query.
	 *
	 * @since 11.2.0
	 *
	 * @param string   $join     Join clause.
	 * @param WP_Query $wp_query Query being built.
	 * @return string Join clause.
	 */
	public static function add_wp_query_join( $join, $wp_query ) {
		global $wpdb;

		if ( ! $wp_query->get( self::TERMS_QUERY_VAR ) || ! wc_product_sku_enabled() ) {
			return $join;
		}

		$alias = self::LOOKUP_ALIAS;

		return $join . " LEFT JOIN {$wpdb->wc_product_meta_lookup} AS {$alias} ON {$wpdb->posts}.ID = {$alias}.product_id ";
	}

	/**
	 * Restricts a product search query to the products matching any of its terms.
	 *
	 * @internal Hooked on `posts_where` for the duration of the search query.
	 *
	 * @since 11.2.0
	 *
	 * @param string   $where    Where clause.
	 * @param WP_Query $wp_query Query being built.
	 * @return string Where clause.
	 */
	public static function add_wp_query_filter( $where, $wp_query ) {
		global $wpdb;

		$terms = $wp_query->get( self::TERMS_QUERY_VAR );
		if ( ! $terms ) {
			return $where;
		}

		$sku_enabled  = wc_product_sku_enabled();
		$alias        = self::LOOKUP_ALIAS;
		$term_clauses = array();

		foreach ( (array) $terms as $term ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->posts is a table name.
			$clause = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $term ) . '%' );
			if ( $sku_enabled ) {
				// Matches Admin\API\Products, which leaves the term unescaped, so a LIKE wildcard stays
				// one. Escaping it would make the report disagree with the search box on what matches.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $alias is a class constant.
				$clause .= $wpdb->prepare( " OR {$alias}.sku LIKE %s", $term );
			}

			$term_clauses[] = "( {$clause} )";
		}

		return $where . ' AND ( ' . implode( ' OR ', $term_clauses ) . ' )';
	}

	/**
	 * Skips running a product search query, which is built for its statement rather than its rows.
	 *
	 * Only the search query itself is skipped. A query another plugin runs from one of the filters
	 * above, to work out how to filter this one, still has to return its own results.
	 *
	 * @internal Hooked on `posts_pre_query` for the duration of the search query.
	 *
	 * @since 11.2.0
	 *
	 * @param array|null $posts    Posts to return instead of running the query, or null to run it.
	 * @param WP_Query   $wp_query Query being built.
	 * @return array|null Posts to return instead of running the query, or null to run it.
	 */
	public static function skip_wp_query_results( $posts, $wp_query ) {
		return $wp_query->get( self::TERMS_QUERY_VAR ) ? array() : $posts;
	}

	/**
	 * Returns the statement WP_Query builds for the given arguments, without running it.
	 *
	 * The search composes into the report query, so the statement is what is needed rather than the
	 * rows. Going through WP_Query keeps the query filters in play, which is how multilingual plugins
	 * restrict products to the active language and how the search box itself resolves a term.
	 *
	 * @param array $args Query arguments.
	 * @return string SQL statement.
	 */
	private static function build_statement( array $args ): string {
		add_filter( 'posts_join', array( __CLASS__, 'add_wp_query_join' ), 10, 2 );
		add_filter( 'posts_where', array( __CLASS__, 'add_wp_query_filter' ), 10, 2 );
		add_filter( 'posts_pre_query', array( __CLASS__, 'skip_wp_query_results' ), 10, 2 );

		try {
			$query = new WP_Query();
			$query->query( $args );

			return $query->request;
		} finally {
			// A filter left behind would follow every later query in the request, so drop them
			// even when the query above threw.
			remove_filter( 'posts_join', array( __CLASS__, 'add_wp_query_join' ), 10 );
			remove_filter( 'posts_where', array( __CLASS__, 'add_wp_query_filter' ), 10 );
			remove_filter( 'posts_pre_query', array( __CLASS__, 'skip_wp_query_results' ), 10 );
		}
	}
}
