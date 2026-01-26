<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks;

use WC_Tax;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;

/**
 * Process the query data for filtering purposes.
 */
final class QueryFilters {
	/**
	 * Initialization method.
	 *
	 * @internal
	 */
	public function init() {}

	/**
	 * Filter the posts clauses of the main query to support global filters.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function main_query_filter( $args, $wp_query ) {
		if (
			! $wp_query->is_main_query() ||
			'product_query' !== $wp_query->get( 'wc_query' )
		) {
			return $args;
		}

		if ( $wp_query->get( 'filter_stock_status' ) ) {
			$args = $this->stock_filter_clauses( $args, $wp_query );
		}

		return $args;
	}

	/**
	 * Add conditional query clauses based on the filter params in query vars.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses( $args, $wp_query ) {
		$args = $this->stock_filter_clauses( $args, $wp_query );
		$args = $this->price_filter_clauses( $args, $wp_query );
		$args = $this->attribute_filter_clauses( $args, $wp_query );

		return $args;
	}

	/**
	 * Get price data for current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return object
	 */
	public function get_filtered_price( $query_vars ) {
		global $wpdb;

		add_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$query->query( $query_vars );
		$product_query_sql = $query->request;

		remove_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$price_filter_sql = "
		SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
		FROM {$wpdb->wc_product_meta_lookup}
		WHERE product_id IN ( {$product_query_sql} )
		";

		return $wpdb->get_row( $price_filter_sql ); // phpcs:ignore
	}

	/**
	 * Get stock status counts for the current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array status=>count pairs.
	 */
	public function get_stock_status_counts( $query_vars ) {
		global $wpdb;
		$stock_status_options = array_map( 'esc_sql', array_keys( wc_get_product_stock_status_options() ) );

		add_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_vars );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$stock_status_counts = array();

		foreach ( $stock_status_options as $status ) {
			$stock_status_count_sql = $this->generate_stock_status_count_query( $status, $product_query_sql, $stock_status_options );

			$result = $wpdb->get_row( $stock_status_count_sql ); // phpcs:ignore
			$stock_status_counts[ $status ] = $result->status_count;
		}

		return $stock_status_counts;
	}

	/**
	 * Get rating counts for the current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array rating=>count pairs.
	 */
	public function get_rating_counts( $query_vars ) {
		global $wpdb;

		add_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$query->query( $query_vars );
		$product_query_sql = $query->request;

		remove_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$rating_count_sql = "
			SELECT COUNT( DISTINCT product_id ) as product_count, ROUND( average_rating, 0 ) as rounded_average_rating
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN ( {$product_query_sql} )
			AND average_rating > 0
			GROUP BY rounded_average_rating
			ORDER BY rounded_average_rating DESC
		";

		$results = $wpdb->get_results( $rating_count_sql ); // phpcs:ignore

		return array_map( 'absint', wp_list_pluck( $results, 'product_count', 'rounded_average_rating' ) );
	}

	/**
	 * Get attribute counts for the current products.
	 *
	 * @param array  $query_vars         The WP_Query arguments.
	 * @param string $attribute_to_count Attribute taxonomy name.
	 * @return array termId=>count pairs.
	 */
	public function get_attribute_counts( $query_vars, $attribute_to_count ) {
		global $wpdb;

		add_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_vars );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $this, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$attributes_to_count = esc_sql( wc_sanitize_taxonomy_name( $attribute_to_count ) );

		$attribute_count_sql = "SELECT COUNT(DISTINCT posts.ID) as term_count, terms.term_id as term_count_id
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
			INNER JOIN {$wpdb->terms} AS terms ON term_taxonomy.term_id = terms.term_id
			WHERE posts.ID IN ( {$product_query_sql} )
			AND term_taxonomy.taxonomy IN ('{$attributes_to_count}')
			AND posts.post_status = 'publish'
			AND posts.post_type = 'product'
			GROUP BY terms.term_id
			ORDER BY terms.name ASC";

		$results = $wpdb->get_results( $attribute_count_sql ); // phpcs:ignore

		return array_map( 'absint', wp_list_pluck( $results, 'term_count', 'term_count_id' ) );
	}

	/**
	 * Add query clauses for stock filter.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	private function stock_filter_clauses( $args, $wp_query ) {
		if ( ! $wp_query->get( 'filter_stock_status' ) ) {
			return $args;
		}

		$args['join']   = $this->append_product_sorting_table_join( $args['join'] );
		$args['where'] .= ' AND wc_product_meta_lookup.stock_status IN (\'' . implode( '\',\'', array_map( 'esc_sql', explode( ',', $wp_query->get( 'filter_stock_status' ) ) ) ) . '\')';

		return $args;
	}

	/**
	 * Add query clauses for price filter.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	private function price_filter_clauses( $args, $wp_query ) {
		if ( ! $wp_query->get( 'min_price' ) && ! $wp_query->get( 'max_price' ) ) {
			return $args;
		}

		global $wpdb;

		$adjust_for_taxes = $this->adjust_price_filters_for_displayed_taxes();
		$args['join']     = $this->append_product_sorting_table_join( $args['join'] );

		if ( $wp_query->get( 'min_price' ) ) {
			$min_price_filter = intval( $wp_query->get( 'min_price' ) );

			if ( $adjust_for_taxes ) {
				$args['where'] .= $this->get_price_filter_query_for_displayed_taxes( $min_price_filter, 'max_price', '>=' );
			} else {
				$args['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.max_price >= %f ', $min_price_filter );
			}
		}

		if ( $wp_query->get( 'max_price' ) ) {
			$max_price_filter = intval( $wp_query->get( 'max_price' ) );

			if ( $adjust_for_taxes ) {
				$args['where'] .= $this->get_price_filter_query_for_displayed_taxes( $max_price_filter, 'min_price', '<=' );
			} else {
				$args['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.min_price <= %f ', $max_price_filter );
			}
		}

		return $args;
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	private function append_product_sorting_table_join( $sql ) {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}
		return $sql;
	}

	/**
	 * Generate calculate query by stock status.
	 *
	 * @param string $status status to calculate.
	 * @param string $product_query_sql product query for current filter state.
	 * @param array  $stock_status_options available stock status options.
	 *
	 * @return false|string
	 */
	private function generate_stock_status_count_query( $status, $product_query_sql, $stock_status_options ) {
		if ( ! in_array( $status, $stock_status_options, true ) ) {
			return false;
		}
		global $wpdb;
		$status = esc_sql( $status );
		return "
			SELECT COUNT( DISTINCT posts.ID ) as status_count
			FROM {$wpdb->posts} as posts
			INNER JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id
			AND postmeta.meta_key = '_stock_status'
			AND postmeta.meta_value = '{$status}'
			WHERE posts.ID IN ( {$product_query_sql} )
		";
	}

	/**
	 * Get query for price filters when dealing with displayed taxes.
	 *
	 * @param float  $price_filter Price filter to apply.
	 * @param string $column Price being filtered (min or max).
	 * @param string $operator Comparison operator for column.
	 * @return string Constructed query.
	 */
	private function get_price_filter_query_for_displayed_taxes( $price_filter, $column = 'min_price', $operator = '>=' ) {
		global $wpdb;

		// Select only used tax classes to avoid unwanted calculations.
		$product_tax_classes = array_filter( $wpdb->get_col( "SELECT DISTINCT tax_class FROM {$wpdb->wc_product_meta_lookup};" ) );

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
	 * If price filters need adjustment to work with displayed taxes, this returns true.
	 *
	 * This logic is used when prices are stored in the database differently to how they are being displayed, with regards
	 * to taxes.
	 *
	 * @return boolean
	 */
	private function adjust_price_filters_for_displayed_taxes() {
		$display  = get_option( 'woocommerce_tax_display_shop' );
		$database = wc_prices_include_tax() ? 'incl' : 'excl';

		return $display !== $database;
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
	private function adjust_price_filter_for_tax_class( $price_filter, $tax_class ) {
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
	 * Get attribute lookup table name.
	 *
	 * @return string
	 */
	private function get_lookup_table_name() {
		return wc_get_container()->get( LookupDataStore::class )->get_lookup_table_name();
	}

	/**
	 * Add query clauses for attribute filter.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	private function attribute_filter_clauses( $args, $wp_query ) {
		$chosen_attributes = $this->get_chosen_attributes( $wp_query->query_vars );

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

		foreach ( $chosen_attributes as $taxonomy => $data ) {
			$all_terms                  = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);
			$term_ids_by_slug           = wp_list_pluck( $all_terms, 'term_id', 'slug' );
			$term_ids_to_filter_by      = array_values( array_intersect_key( $term_ids_by_slug, array_flip( $data['terms'] ) ) );
			$term_ids_to_filter_by      = array_map( 'absint', $term_ids_to_filter_by );
			$term_ids_to_filter_by_list = '(' . join( ',', $term_ids_to_filter_by ) . ')';
			$is_and_query               = 'and' === $data['query_type'];

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
	 * Get an array of attributes and terms selected from query arguments.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array
	 */
	private function get_chosen_attributes( $query_vars ) {
		$chosen_attributes = array();

		if ( empty( $query_vars ) ) {
			return $chosen_attributes;
		}

		foreach ( $query_vars as $key => $value ) {
			if ( 0 === strpos( $key, 'filter_' ) ) {
				if ( ! is_string( $value ) ) {
					continue;
				}

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
}
