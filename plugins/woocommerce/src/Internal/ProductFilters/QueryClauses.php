<?php
/**
 * QueryClauses class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\QueryClausesGenerator;
use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\MainQueryClausesGenerator;
use Automattic\WooCommerce\Internal\ProductFilters\CacheController;
use WC_Tax;
use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class for filter clauses.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class QueryClauses implements QueryClausesGenerator, MainQueryClausesGenerator {
	/**
	 * Hold the filter params.
	 *
	 * @var Params
	 */
	private $params;

	/**
	 * Initialize the query clauses.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @param Params $params The filter params.
	 * @return void
	 */
	final public function init( Params $params ): void {
		$this->params = $params;
	}

	/**
	 * Add conditional query clauses based on the filter params in query vars.
	 *
	 * There isn't a clause for rating filter because we use tax_query for it
	 * (product_visibility).
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses( array $args, \WP_Query $wp_query ): array {
		if ( $wp_query->get( 'filter_stock_status' ) ) {
			$stock_statuses = trim( $wp_query->get( 'filter_stock_status' ) );
			$stock_statuses = explode( ',', $stock_statuses );

			$args = $this->add_stock_clauses( $args, $stock_statuses );
		}

		if ( $wp_query->get( 'min_price' ) || $wp_query->get( 'max_price' ) ) {
			$price_range = array(
				'min_price' => $wp_query->get( 'min_price' ),
				'max_price' => $wp_query->get( 'max_price' ),
			);
			$price_range = array_filter( $price_range );
			$args        = $this->add_price_clauses( $args, $price_range );
		}

		$args = $this->add_attribute_clauses(
			$args,
			$this->get_chosen_attributes( $wp_query->query_vars )
		);

		$args = $this->add_taxonomy_clauses(
			$args,
			$this->get_chosen_taxonomies( $wp_query->query_vars )
		);

		return $args;
	}

	/**
	 * Add query clauses for main query.
	 * WooCommerce handles attribute, price, and rating filters in the main query.
	 * This method is used to add stock status and taxonomy filters to the main query.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses_for_main_query( array $args, \WP_Query $wp_query ): array {
		if (
			! $wp_query->is_main_query() ||
			'product_query' !== $wp_query->get( 'wc_query' )
		) {
			return $args;
		}

		if ( $wp_query->get( 'filter_stock_status' ) ) {
			$stock_statuses = trim( $wp_query->get( 'filter_stock_status' ) );
			$stock_statuses = explode( ',', $stock_statuses );
			$stock_statuses = array_filter( $stock_statuses );

			$args = $this->add_stock_clauses( $args, $stock_statuses );
		}

		$args = $this->add_taxonomy_clauses(
			$args,
			$this->get_chosen_taxonomies( $wp_query->query_vars )
		);

		return $args;
	}

	/**
	 * Add query clauses for stock filter.
	 *
	 * @param array $args           Query args.
	 * @param array $stock_statuses Stock statuses to be queried.
	 * @return array
	 */
	public function add_stock_clauses( array $args, array $stock_statuses ): array {
		$stock_statuses = array_filter( $stock_statuses );

		if ( empty( $stock_statuses ) ) {
			return $args;
		}

		$filtered_stock_statuses = array_intersect(
			array_map( 'esc_sql', $stock_statuses ),
			array_keys( wc_get_product_stock_status_options() )
		);

		if ( ! empty( $filtered_stock_statuses ) ) {
			$args['join']   = $this->append_product_sorting_table_join( $args['join'] );
			$args['where'] .= ' AND wc_product_meta_lookup.stock_status IN ("' . implode( '","', $filtered_stock_statuses ) . '")';
		}

		if ( ! empty( $stock_statuses ) && empty( $filtered_stock_statuses ) ) {
			$args['where'] .= ' AND 1=0';
		}

		return $args;
	}

	/**
	 * Add query clauses for price filter.
	 *
	 * @param array $args        Query args.
	 * @param array $price_range {
	 *     Price range array.
	 *
	 *     @type int|string $min_price Optional. Min price.
	 *     @type int|string $max_price Optional. Max Price.
	 * }
	 * @return array
	 */
	public function add_price_clauses( array $args, array $price_range ): array {
		if ( ! isset( $price_range['min_price'] ) && ! isset( $price_range['max_price'] ) ) {
			return $args;
		}

		global $wpdb;

		$adjust_for_taxes = $this->should_adjust_price_filters_for_displayed_taxes();
		$args['join']     = $this->append_product_sorting_table_join( $args['join'] );

		if ( isset( $price_range['min_price'] ) ) {
			$min_price_filter = intval( $price_range['min_price'] );

			if ( $adjust_for_taxes ) {
				$args['where'] .= $this->get_price_filter_query_for_displayed_taxes( $min_price_filter, 'max_price', '>=' );
			} else {
				$args['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.max_price >= %f ', $min_price_filter );
			}
		}

		if ( isset( $price_range['max_price'] ) ) {
			$max_price_filter = intval( $price_range['max_price'] );

			if ( $adjust_for_taxes ) {
				$args['where'] .= $this->get_price_filter_query_for_displayed_taxes( $max_price_filter, 'min_price', '<=' );
			} else {
				$args['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.min_price <= %f ', $max_price_filter );
			}
		}

		return $args;
	}

	/**
	 * Add query clauses for filtering products by attributes.
	 *
	 * @param array $args              Query args.
	 * @param array $chosen_attributes {
	 *     Chosen attributes array.
	 *
	 *     @type array {$taxonomy: Attribute taxonomy name} {
	 *         @type string[] $terms      Chosen terms' slug.
	 *         @type string   $query_type Query type. Accepts 'and' or 'or'.
	 *     }
	 * }
	 *
	 * @return array
	 */
	public function add_attribute_clauses( array $args, array $chosen_attributes ): array {
		if ( empty( $chosen_attributes ) ) {
			return $args;
		}

		global $wpdb;

		// The extra derived table ("SELECT product_or_parent_id FROM") is needed for performance
		// (causes the filtering subquery to be executed only once).
		$clause_root = " {$wpdb->posts}.ID IN ( SELECT product_or_parent_id FROM (";
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$in_stock_clause = ' AND in_stock = 1';
		} else {
			$in_stock_clause = '';
		}

		$attribute_ids_for_and_filtering = array();
		$clauses                         = array();

		// Get all terms for all attribute taxonomies in one query for better performance.
		$all_terms_slugs = array();
		foreach ( $chosen_attributes as $data ) {
			if ( ! empty( $data['terms'] ) && is_array( $data['terms'] ) ) {
				$all_terms_slugs = array_merge( $all_terms_slugs, $data['terms'] );
			}
		}

		$all_terms = get_terms(
			array(
				'taxonomy'   => array_keys( $chosen_attributes ),
				'slug'       => $all_terms_slugs,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $all_terms ) ) {
			return $args;
		}

		// Group terms by taxonomy for easier processing.
		$terms_by_taxonomy = array();
		foreach ( $all_terms as $term ) {
			$terms_by_taxonomy[ $term->taxonomy ][] = $term;
		}

		foreach ( $chosen_attributes as $taxonomy => $data ) {
			$current_attribute_terms    = $terms_by_taxonomy[ $taxonomy ] ?? array();
			$term_ids_by_slug           = wp_list_pluck( $current_attribute_terms, 'term_id', 'slug' );
			$term_ids_to_filter_by      = array_values( array_intersect_key( $term_ids_by_slug, array_flip( $data['terms'] ) ) );
			$term_ids_to_filter_by      = array_map( 'absint', $term_ids_to_filter_by );
			$term_ids_to_filter_by_list = '(' . join( ',', $term_ids_to_filter_by ) . ')';
			$is_and_query               = 'and' === strtolower( $data['query_type'] );

			$count = count( $term_ids_to_filter_by );

			if ( 0 !== $count ) {
				if ( $is_and_query && $count > 1 ) {
					$attribute_ids_for_and_filtering = array_merge( $attribute_ids_for_and_filtering, $term_ids_to_filter_by );
				} else {
					$clauses[] = "
							{$clause_root}
							SELECT product_or_parent_id
							FROM {$this->get_lookup_table_name()} lt
							WHERE term_id in {$term_ids_to_filter_by_list}
							{$in_stock_clause}
						)";
				}
			}
		}

		if ( ! empty( $attribute_ids_for_and_filtering ) ) {
			$count                      = count( $attribute_ids_for_and_filtering );
			$term_ids_to_filter_by_list = '(' . join( ',', $attribute_ids_for_and_filtering ) . ')';
			$clauses[]                  = "
				{$clause_root}
				SELECT product_or_parent_id
				FROM {$this->get_lookup_table_name()} lt
				WHERE is_variation_attribute=0
				{$in_stock_clause}
				AND term_id in {$term_ids_to_filter_by_list}
				GROUP BY product_id
				HAVING COUNT(product_id)={$count}
				UNION
				SELECT product_or_parent_id
				FROM {$this->get_lookup_table_name()} lt
				WHERE is_variation_attribute=1
				{$in_stock_clause}
				AND term_id in {$term_ids_to_filter_by_list}
			)";
		}

		if ( ! empty( $clauses ) ) {
			// "temp" is needed because the extra derived tables require an alias.
			$args['where'] .= ' AND (' . join( ' temp ) AND ', $clauses ) . ' temp ))';
		} elseif ( ! empty( $chosen_attributes ) ) {
			$args['where'] .= ' AND 1=0';
		}

		return $args;
	}

	/**
	 * Add query clauses for taxonomy filter (e.g., product_cat, product_tag).
	 *
	 * @param array $args           Query args.
	 * @param array $chosen_taxonomies {
	 *     Chosen taxonomies array.
	 *
	 *     @type array {$taxonomy: Taxonomy name} {
	 *         @type string[] $terms Chosen terms' slug.
	 *     }
	 * }
	 * @return array
	 */
	public function add_taxonomy_clauses( array $args, array $chosen_taxonomies ): array {
		if ( empty( $chosen_taxonomies ) ) {
			return $args;
		}

		global $wpdb;

		$tax_queries = array();

		$all_terms = get_terms(
			array(
				'taxonomy'   => array_keys( $chosen_taxonomies ),
				'slug'       => array_merge( ...array_values( $chosen_taxonomies ) ),
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $all_terms ) ) {
			/**
			 * No error logging needed here because:
			 * 1. Taxonomy existence is already validated in the initial get_terms() call above
			 * 2. get_terms() only returns WP_Error for invalid taxonomy or rare DB connection issues
			 * 3. If the taxonomy was invalid, we would have failed earlier and never reached this code
			 * 4. Database errors would likely affect the entire request, not just this call
			 */
			return $args;
		}

		$term_ids_by_taxonomy = array();

		foreach ( $all_terms as $term ) {
			$term_ids_by_taxonomy[ $term->taxonomy ][] = $term->term_id;
		}

		foreach ( $term_ids_by_taxonomy as $taxonomy => $term_ids ) {
			if ( empty( $term_ids ) ) {
				continue;
			}

			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$expanded_term_ids = $term_ids;

				foreach ( $term_ids as $term_id ) {
					$cache_key = WC_Cache_Helper::get_cache_prefix( CacheController::CACHE_GROUP ) . 'child_terms_' . $taxonomy . '_' . $term_id;
					$children  = wp_cache_get( $cache_key );

					if ( false === $children ) {
						$children = get_terms(
							array(
								'taxonomy'   => $taxonomy,
								'child_of'   => $term_id,
								'fields'     => 'ids',
								'hide_empty' => false,
							)
						);

						if ( ! is_wp_error( $children ) ) {
							wp_cache_set( $cache_key, $children, '', HOUR_IN_SECONDS );
						} else {
							$children = array();
						}
					}

					$expanded_term_ids = array_merge( $expanded_term_ids, $children );
				}

				$term_ids = array_unique( $expanded_term_ids );
			}

			$term_ids_list = '(' . implode( ',', array_map( 'absint', $term_ids ) ) . ')';

			/*
			 * Use EXISTS subquery for taxonomy filtering for several key benefits:
			 *
			 * 1. Performance: EXISTS stops execution as soon as the first matching row is found,
			 *    making it faster than JOIN approaches that need to process all matches.
			 *
			 * 2. No duplicate rows: Unlike JOINs, EXISTS doesn't create duplicate rows when
			 *    a product has multiple matching terms, eliminating the need for DISTINCT.
			 *
			 * 3. Clean boolean logic: We only care IF a product has the terms, not HOW MANY
			 *    or which specific ones, making EXISTS semantically correct.
			 *
			 * 4. Efficient combination: Multiple taxonomy filters can be combined with AND
			 *    without complex GROUP BY logic or performance degradation.
			 */
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$tax_queries[] = $wpdb->prepare(
				"EXISTS (
					SELECT 1 FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tr.object_id = {$wpdb->posts}.ID
					AND tt.taxonomy = %s
					AND tt.term_id IN {$term_ids_list}
				)",
				$taxonomy
			);
		}

		if ( ! empty( $tax_queries ) ) {
			$args['where'] .= ' AND (' . implode( ' AND ', $tax_queries ) . ')';
		} else {
			$args['where'] .= ' AND 1=0';
		}

		return $args;
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	private function append_product_sorting_table_join( string $sql ): string {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}
		return $sql;
	}

	/**
	 * If price filters need adjustment to work with displayed taxes, this returns true.
	 *
	 * This logic is used when prices are stored in the database differently to how they are being displayed, with regards
	 * to taxes.
	 *
	 * @return boolean
	 */
	private function should_adjust_price_filters_for_displayed_taxes(): bool {
		$display  = get_option( 'woocommerce_tax_display_shop' );
		$database = wc_prices_include_tax() ? 'incl' : 'excl';

		return $display !== $database;
	}

	/**
	 * Get query for price filters when dealing with displayed taxes.
	 *
	 * @param float  $price_filter Price filter to apply.
	 * @param string $column Price being filtered (min or max).
	 * @param string $operator Comparison operator for column. Accepts '>=' or '<='.
	 * @return string Constructed query.
	 */
	private function get_price_filter_query_for_displayed_taxes( float $price_filter, string $column = 'min_price', string $operator = '>=' ): string {
		global $wpdb;

		if ( ! in_array( $operator, array( '>=', '<=' ), true ) ) {
			return '';
		}

		// Select only used tax classes to avoid unwanted calculations.
		$cache_key           = WC_Cache_Helper::get_cache_prefix( 'filter_clauses' ) . 'tax_classes';
		$product_tax_classes = wp_cache_get( $cache_key );

		if ( ! $product_tax_classes ) {
			$product_tax_classes = $wpdb->get_col( "SELECT DISTINCT tax_class FROM {$wpdb->wc_product_meta_lookup};" );
			wp_cache_set( $cache_key, $product_tax_classes );
		}

		if ( empty( $product_tax_classes ) ) {
			return '';
		}

		$or_queries = array();

		// We need to adjust the filter for each possible tax class and combine the queries into one.
		foreach ( $product_tax_classes as $tax_class ) {
			$adjusted_price_filter = $this->adjust_price_filter_for_tax_class( $price_filter, $tax_class );
			$or_queries[]          = $wpdb->prepare(
				'( wc_product_meta_lookup.tax_class = %s AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )',
				$tax_class,
				$adjusted_price_filter
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->prepare(
			' AND (
				wc_product_meta_lookup.tax_status = "taxable" AND ( 0=1 OR ' . implode( ' OR ', $or_queries ) . ')
				OR ( wc_product_meta_lookup.tax_status != "taxable" AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )
			) ',
			$price_filter
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Adjusts a price filter based on a tax class and whether or not the amount includes or excludes taxes.
	 *
	 * This calculation logic is based on `wc_get_price_excluding_tax` and `wc_get_price_including_tax` in core.
	 *
	 * @param float  $price_filter Price filter amount as entered.
	 * @param string $tax_class Tax class for adjustment.
	 * @return float
	 */
	private function adjust_price_filter_for_tax_class( float $price_filter, string $tax_class ): float {
		$tax_display    = get_option( 'woocommerce_tax_display_shop' );
		$tax_rates      = WC_Tax::get_rates( $tax_class );
		$base_tax_rates = WC_Tax::get_base_tax_rates( $tax_class );

		// If prices are shown incl. tax, we want to remove the taxes from the filter amount to match prices stored excl. tax.
		if ( 'incl' === $tax_display ) {
			/**
			 * Filters if taxes should be removed from locations outside the store base location.
			 *
			 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing
			 * with out of base locations. e.g. If a product costs 10 including tax, all users will pay 10
			 * regardless of location and taxes.
			 *
			 * @since 2.6.0
			 *
			 * @internal Matches filter name in WooCommerce core.
			 *
			 * @param boolean $adjust_non_base_location_prices True by default.
			 * @return boolean
			 */
			$taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $price_filter, $base_tax_rates, true ) : WC_Tax::calc_tax( $price_filter, $tax_rates, true );
			return $price_filter - array_sum( $taxes );
		}

		// If prices are shown excl. tax, add taxes to match the prices stored in the DB.
		$taxes = WC_Tax::calc_tax( $price_filter, $tax_rates, false );

		return $price_filter + array_sum( $taxes );
	}

	/**
	 * Get an array of attributes and terms selected from query arguments.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array
	 */
	private function get_chosen_attributes( array $query_vars ): array {
		$chosen_attributes = array();

		if ( empty( $query_vars ) ) {
			return $chosen_attributes;
		}

		foreach ( $query_vars as $key => $value ) {
			if ( 0 === strpos( $key, 'filter_' ) ) {
				$attribute    = wc_sanitize_taxonomy_name( str_replace( 'filter_', '', $key ) );
				$taxonomy     = wc_attribute_taxonomy_name( $attribute );
				$filter_terms = ! empty( $value ) ? explode( ',', wc_clean( wp_unslash( $value ) ) ) : array();

				if ( empty( $filter_terms ) || ! taxonomy_exists( $taxonomy ) || ! wc_attribute_taxonomy_id_by_name( $attribute ) ) {
					continue;
				}

				$query_type                                   = ! empty( $query_vars[ 'query_type_' . $attribute ] ) && in_array( $query_vars[ 'query_type_' . $attribute ], array( 'and', 'or' ), true ) ? wc_clean( wp_unslash( $query_vars[ 'query_type_' . $attribute ] ) ) : '';
				$chosen_attributes[ $taxonomy ]['terms']      = array_map( 'sanitize_title', $filter_terms ); // Ensures correct encoding.
				$chosen_attributes[ $taxonomy ]['query_type'] = $query_type ? $query_type : 'and';
			}
		}

		return $chosen_attributes;
	}

	/**
	 * Get an array of taxonomies and terms selected from query arguments.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array
	 */
	private function get_chosen_taxonomies( array $query_vars ): array {
		$chosen_taxonomies = array();

		if ( empty( $query_vars ) ) {
			return $chosen_taxonomies;
		}

		foreach ( $this->params->get_param( 'taxonomy' ) as $taxonomy => $param ) {
			if ( isset( $query_vars[ $param ] ) && ! empty( trim( $query_vars[ $param ] ) ) ) {
				$chosen_taxonomies[ $taxonomy ] = array_filter( array_map( 'sanitize_title', explode( ',', $query_vars[ $param ] ) ) );
			}
		}

		return $chosen_taxonomies;
	}

	/**
	 * Get attribute lookup table name.
	 *
	 * @return string
	 */
	private function get_lookup_table_name(): string {
		return wc_get_container()->get( LookupDataStore::class )->get_lookup_table_name();
	}
}
