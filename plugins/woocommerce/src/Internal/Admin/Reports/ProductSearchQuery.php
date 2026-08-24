<?php
/**
 * ProductSearchQuery class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Reports;

use WP_Query;

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
		$terms = self::parse_terms( $terms );

		if ( empty( $terms ) ) {
			return '';
		}

		$args = array(
			'post_type'           => 'product',
			// The search box queries products with `status=any`, which covers every status that
			// is not excluded from search. A drafted product can still have sales to report.
			'post_status'         => 'any',
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			// The report orders and pages the result itself, so the subquery does not have to.
			'orderby'             => 'none',
			'no_found_rows'       => true,
			// The query is never run, so its empty result would only put an entry nothing
			// reads back into the post query cache.
			'cache_results'       => false,
			self::TERMS_QUERY_VAR => $terms,
		);

		$restrict_to_ids = (array) $restrict_to_ids;
		if ( ! empty( $restrict_to_ids ) ) {
			$args['post__in'] = array_map( 'intval', $restrict_to_ids );
		}

		$statement = self::build_statement( $args );

		// A plugin filtering `posts_fields` can add columns to the statement, so name the one
		// this returns instead of passing the whole row on.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- WP_Query prepares the statement it builds.
		return "SELECT DISTINCT ID AS product_id FROM ( {$statement} ) AS wc_analytics_product_search_results";
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
				// Matches Admin\API\Products, which compares the SKU against the term unwrapped and
				// unescaped, so a LIKE wildcard in the term stays a wildcard here. Escaping it would
				// make the report disagree with the search box on what the term matches.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $alias is a class constant.
				$clause .= $wpdb->prepare( " OR {$alias}.sku LIKE %s", $term );
			}

			$term_clauses[] = "( {$clause} )";
		}

		return $where . ' AND ( ' . implode( ' OR ', $term_clauses ) . ' )';
	}

	/**
	 * Returns the statement WP_Query builds for the given arguments, without running it.
	 *
	 * The search only has to compose into the report query, so the statement is what is needed
	 * rather than the rows. Building it through WP_Query keeps `posts_join`, `posts_where` and
	 * the rest of the query filters in play. Multilingual plugins restrict products to the
	 * active language through them, and the search box the report has to agree with is a
	 * WP_Query too, so a hand written statement would answer differently on those sites.
	 *
	 * @param array $args Query arguments.
	 * @return string SQL statement.
	 */
	private static function build_statement( array $args ): string {
		add_filter( 'posts_join', array( __CLASS__, 'add_wp_query_join' ), 10, 2 );
		add_filter( 'posts_where', array( __CLASS__, 'add_wp_query_filter' ), 10, 2 );
		// WP_Query builds the statement before it runs it, so short-circuit the results.
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query = new WP_Query();
		$query->query( $args );

		remove_filter( 'posts_join', array( __CLASS__, 'add_wp_query_join' ), 10 );
		remove_filter( 'posts_where', array( __CLASS__, 'add_wp_query_filter' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		return $query->request;
	}
}
