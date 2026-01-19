<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\QueryClausesGenerator;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;
use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class for filter counts.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class FilterData {
	/**
	 * Instance of QueryClauses.
	 *
	 * @var QueryClausesGenerator
	 */
	private $query_clauses;

	/**
	 * Instance of TaxonomyHierarchyData.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $taxonomy_hierarchy_data;

	/**
	 * Constructor.
	 *
	 * @param QueryClausesGenerator $query_clauses Instance of QueryClausesGenerator.
	 * @param TaxonomyHierarchyData $taxonomy_hierarchy_data Instance of TaxonomyHierarchyData.
	 */
	public function __construct( QueryClausesGenerator $query_clauses, TaxonomyHierarchyData $taxonomy_hierarchy_data ) {
		$this->query_clauses           = $query_clauses;
		$this->taxonomy_hierarchy_data = $taxonomy_hierarchy_data;
	}

	/**
	 * Get price data for current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return object
	 */
	public function get_filtered_price( array $query_vars ) {
		/**
		 * Allows offloading the filter data to external services like Elasticsearch.
		 *
		 * @hook woocommerce_pre_product_filter_data
		 *
		 * @since 9.9.0
		 *
		 * @param array  $results      The results for current query.
		 * @param string $filter_type  The type of filter. Accepts price|stock|rating|attribute.
		 * @param array  $query_vars   The query arguments to calculate the filter data.
		 * @param array  $extra        Some filter types require extra arguments for calculation, like attribute.
		 * @return array The filtered results or null to continue with default processing.
		 */
		$pre_filter_counts = apply_filters( 'woocommerce_pre_product_filter_data', null, 'price', $query_vars, array() );

		if ( is_array( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'price' );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$results     = array();
		$product_ids = $this->get_cached_product_ids( $query_vars );

		if ( $product_ids ) {
			global $wpdb;

			$price_filter_sql = "
			SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN ( {$product_ids} )
			";

			/**
			* We can't use $wpdb->prepare() here because using %s with
			* $wpdb->prepare() for a subquery won't work as it will escape the SQL
			* query.
			* We're using the query as is, same as Core does.
			*/
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = (array) $wpdb->get_row( $price_filter_sql );
		}

		/**
		 * Filters the product filter data before it is returned.
		 *
		 * @hook woocommerce_product_filter_data
		 * @since 9.9.0
		 *
		 * @param array  $results      The results for current query.
		 * @param string $filter_type  The type of filter. Accepts price|stock|rating|attribute.
		 * @param array  $query_vars   The query arguments to calculate the filter data.
		 * @param array  $extra        Some filter types require extra arguments for calculation, like attribute.
		 * @return array The filtered results
		 */
		$results = apply_filters( 'woocommerce_product_filter_data', $results, 'price', $query_vars, array() );

		$this->set_cache( $transient_key, $results );

		return $results;
	}

	/**
	 * Get stock status counts for the current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @param array $statuses   Array of stock status values to count.
	 * @return array status=>count pairs.
	 */
	public function get_stock_status_counts( array $query_vars, array $statuses ) {
		/**
		 * Filter the data. @see get_filtered_price() for full documentation.
		 */
		$pre_filter_counts = apply_filters( 'woocommerce_pre_product_filter_data', null, 'stock', $query_vars, array() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		if ( is_array( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'stock' );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$results     = array();
		$product_ids = $this->get_cached_product_ids( $query_vars );

		if ( $product_ids ) {
			global $wpdb;

			foreach ( $statuses as $status ) {
				$stock_status_count_sql = "
					SELECT COUNT( DISTINCT posts.ID ) as status_count
					FROM {$wpdb->posts} as posts
					INNER JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id
					AND postmeta.meta_key = '_stock_status'
					AND postmeta.meta_value = '" . esc_sql( $status ) . "'
					WHERE posts.ID IN ( {$product_ids} )
				";

				/**
				* We can't use $wpdb->prepare() here because using %s with
				* $wpdb->prepare() for a subquery won't work as it will escape the
				* SQL query.
				* We're using the query as is, same as Core does.
				*/
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$result             = $wpdb->get_row( $stock_status_count_sql );
				$results[ $status ] = $result->status_count;
			}
		}

		/**
		 * Filter the results. @see get_filtered_price() for full documentation.
		 */
		$results = apply_filters( 'woocommerce_product_filter_data', $results, 'stock', $query_vars, array() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		$this->set_cache( $transient_key, $results );

		return $results;
	}

	/**
	 * Get rating counts for the current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array rating=>count pairs.
	 */
	public function get_rating_counts( array $query_vars ) {
		/**
		 * Filter the data. @see get_filtered_price() for full documentation.
		 */
		$pre_filter_counts = apply_filters( 'woocommerce_pre_product_filter_data', null, 'rating', $query_vars, array() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		if ( is_array( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'rating' );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$results     = array();
		$product_ids = $this->get_cached_product_ids( $query_vars );

		if ( $product_ids ) {
			global $wpdb;

			$rating_count_sql = "
				SELECT COUNT( DISTINCT product_id ) as product_count, ROUND( average_rating, 0 ) as rounded_average_rating
				FROM {$wpdb->wc_product_meta_lookup}
				WHERE product_id IN ( {$product_ids} )
				AND average_rating > 0
				GROUP BY rounded_average_rating
				ORDER BY rounded_average_rating DESC
			";

			/**
			* We can't use $wpdb->prepare() here because using %s with
			* $wpdb->prepare() for a subquery won't work as it will escape the
			* SQL query.
			* We're using the query as is, same as Core does.
			*/
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $rating_count_sql );
			$results = array_map( 'absint', wp_list_pluck( $results, 'product_count', 'rounded_average_rating' ) );
		}

		/**
		 * Filter the results. @see get_filtered_price() for full documentation.
		 */
		$results = apply_filters( 'woocommerce_product_filter_data', $results, 'rating', $query_vars, array() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		$this->set_cache( $transient_key, $results );

		return $results;
	}

	/**
	 * Get attribute counts for the current products.
	 *
	 * @param array  $query_vars         The WP_Query arguments.
	 * @param string $attribute_to_count Attribute taxonomy name.
	 * @return array termId=>count pairs.
	 */
	public function get_attribute_counts( array $query_vars, string $attribute_to_count ) {
		/**
		 * Filter the data. @see get_filtered_price() for full documentation.
		 */
		$pre_filter_counts = apply_filters( 'woocommerce_pre_product_filter_data', null, 'attribute', $query_vars, array( 'taxonomy' => $attribute_to_count ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		if ( is_array( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'attribute', array( 'taxonomy' => $attribute_to_count ) );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$results     = array();
		$product_ids = $this->get_cached_product_ids( $query_vars );

		if ( $product_ids ) {
			global $wpdb;

			$taxonomy_escaped    = esc_sql( wc_sanitize_taxonomy_name( $attribute_to_count ) );
			$attribute_count_sql = "
				SELECT COUNT( DISTINCT posts.ID ) as term_count, terms.term_id as term_count_id
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
				INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
				INNER JOIN {$wpdb->terms} AS terms USING( term_id )
				WHERE posts.ID IN ( {$product_ids} )
				AND term_taxonomy.taxonomy = '{$taxonomy_escaped}'
				GROUP BY terms.term_id
			";

			/**
			 * We can't use $wpdb->prepare() here because using %s with
			 * $wpdb->prepare() for a subquery won't work as it will escape the
			 * SQL query.
			 * We're using the query as is, same as Core does.
			 */
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $attribute_count_sql );
			$results = array_map( 'absint', wp_list_pluck( $results, 'term_count', 'term_count_id' ) );
		}

		/**
		 * Filter the results. @see get_filtered_price() for full documentation.
		 *
		 * @since 9.9.0
		 */
		$results = apply_filters( 'woocommerce_product_filter_data', $results, 'attribute', $query_vars, array( 'taxonomy' => $attribute_to_count ) );

		$this->set_cache( $transient_key, $results );

		return $results;
	}

	/**
	 * Get taxonomy counts for the current products.
	 *
	 * @param array  $query_vars The WP_Query arguments.
	 * @param string $taxonomy_to_count   Taxonomy name.
	 * @return array termId=>count pairs.
	 */
	public function get_taxonomy_counts( array $query_vars, string $taxonomy_to_count ) {
		/**
		 * Filter the data. @see get_filtered_price() for full documentation.
		 *
		 * @since 9.9.0
		 */
		$pre_filter_counts = apply_filters( 'woocommerce_pre_product_filter_data', null, 'taxonomy', $query_vars, array( 'taxonomy' => $taxonomy_to_count ) );

		if ( is_array( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'taxonomy', array( 'taxonomy' => $taxonomy_to_count ) );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$results     = array();
		$product_ids = $this->get_cached_product_ids( $query_vars );

		if ( $product_ids ) {
			global $wpdb;

			$taxonomy_escaped = esc_sql( wc_sanitize_taxonomy_name( $taxonomy_to_count ) );

			if ( is_taxonomy_hierarchical( $taxonomy_to_count ) ) {
				$results = $this->get_hierarchical_taxonomy_counts( $product_ids, $taxonomy_to_count );
			} else {
				$taxonomy_count_sql = "
					SELECT COUNT( DISTINCT term_relationships.object_id ) as term_count, term_taxonomy.term_taxonomy_id as term_count_id
					FROM {$wpdb->term_relationships} AS term_relationships
					INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
					WHERE term_relationships.object_id IN ( {$product_ids} )
					AND term_taxonomy.taxonomy = '{$taxonomy_escaped}'
					GROUP BY term_taxonomy.term_taxonomy_id
				";

				/**
				 * We can't use $wpdb->prepare() here because using %s with
				 * $wpdb->prepare() for a subquery won't work as it will escape the
				 * SQL query.
				 * We're using the query as is, same as Core does.
				 */
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$base_results = $wpdb->get_results( $taxonomy_count_sql );
				$results      = array_map( 'absint', wp_list_pluck( $base_results, 'term_count', 'term_count_id' ) );
			}
		}

		/**
		 * Filter the results. @see get_filtered_price() for full documentation.
		 *
		 * @since 9.9.0
		 */
		$results = apply_filters( 'woocommerce_product_filter_data', $results, 'taxonomy', $query_vars, array( 'taxonomy' => $taxonomy_to_count ) );

		$this->set_cache( $transient_key, $results );

		return $results;
	}

	/**
	 * Get hierarchical taxonomy counts using optimized hierarchy data.
	 *
	 * @param string $product_ids   Comma-separated list of product IDs.
	 * @param string $taxonomy_name Original taxonomy name for hierarchy methods.
	 * @return array Array of term_id => count pairs.
	 */
	private function get_hierarchical_taxonomy_counts( string $product_ids, string $taxonomy_name ) {
		global $wpdb;

		// Step 1: Get all terms that have products in the filtered set (1 query).
		$taxonomy_escaped = esc_sql( wc_sanitize_taxonomy_name( $taxonomy_name ) );
		$base_terms_sql   = "
			SELECT DISTINCT tt.term_id, tt.term_taxonomy_id
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tr.object_id IN ( {$product_ids} )
			AND tt.taxonomy = '{$taxonomy_escaped}'
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$base_terms = $wpdb->get_results( $base_terms_sql );

		if ( empty( $base_terms ) ) {
			return array();
		}

		// Step 2: Build hierarchy relationships using TaxonomyHierarchyData.
		$hierarchy_counts = array();
		$processed_terms  = array();

		// Process each base term and its ancestors.
		foreach ( $base_terms as $term ) {
			$term_id = (int) $term->term_id;

			// Count for the term itself and all its descendants.
			if ( ! isset( $hierarchy_counts[ $term_id ] ) ) {
				$descendants                  = $this->taxonomy_hierarchy_data->get_descendants( $term_id, $taxonomy_name );
				$descendants[]                = $term_id; // Include the term itself.
				$hierarchy_counts[ $term_id ] = $descendants;
			}

			// Get ancestors using hierarchy data.
			$ancestors = $this->taxonomy_hierarchy_data->get_ancestors( $term_id, $taxonomy_name );
			foreach ( $ancestors as $ancestor_id ) {
				if ( in_array( $ancestor_id, $processed_terms, true ) ) {
					continue;
				}

				$descendants   = $this->taxonomy_hierarchy_data->get_descendants( $ancestor_id, $taxonomy_name );
				$descendants[] = $ancestor_id; // Include the ancestor term itself.

				$hierarchy_counts[ $ancestor_id ] = $descendants;
				$processed_terms[]                = $ancestor_id;
			}
		}

		if ( empty( $hierarchy_counts ) ) {
			return array();
		}

		// Step 3: Execute batch counting using a single query with CASE statements.
		$count_cases = array();
		foreach ( $hierarchy_counts as $term_id => $term_ids ) {
			$term_ids_str  = implode( ',', array_map( 'absint', $term_ids ) );
			$count_cases[] = "COUNT(DISTINCT CASE WHEN tt.term_id IN ({$term_ids_str}) THEN tr.object_id END) as count_{$term_id}";
		}

		$batch_count_sql = '
			SELECT ' . implode( ', ', $count_cases ) . "
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tr.object_id IN ( {$product_ids} )
			AND tt.taxonomy = '{$taxonomy_escaped}'
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count_result = $wpdb->get_row( $batch_count_sql, ARRAY_A );

		if ( empty( $count_result ) ) {
			return array();
		}

		// Parse results back to term_id => count format.
		$final_counts = array();
		foreach ( $hierarchy_counts as $term_id => $term_ids ) {
			$count_key = "count_{$term_id}";
			if ( isset( $count_result[ $count_key ] ) && $count_result[ $count_key ] > 0 ) {
				$final_counts[ $term_id ] = absint( $count_result[ $count_key ] );
			}
		}

		return $final_counts;
	}

	/**
	 * Get filter data transient key.
	 *
	 * @param array  $query_vars   The query arguments to calculate the filter data.
	 * @param string $filter_type The type of filter. Accepts price|stock|rating|attribute.
	 * @param array  $extra        Some filter types require extra arguments for calculation, like attribute.
	 */
	private function get_transient_key( $query_vars, $filter_type, $extra = array() ) {
		return sprintf(
			'wc_%s_%s',
			CacheController::CACHE_GROUP,
			md5(
				wp_json_encode(
					array(
						'query_vars'  => $query_vars,
						'extra'       => $extra,
						'filter_type' => $filter_type,
					)
				)
			)
		);
	}

	/**
	 * Get cached filter data.
	 *
	 * @param string $key Transient key.
	 */
	private function get_cache( $key ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return null;
		}

		$cache             = get_transient( $key );
		$transient_version = WC_Cache_Helper::get_transient_version( CacheController::CACHE_GROUP );

		if ( empty( $cache['version'] ) ||
			! is_array( $cache['value'] ) ||
			empty( $cache['value'] ) ||
			$transient_version !== $cache['version']
		) {
			return null;
		}

		return $cache['value'];
	}

	/**
	 * Set the cache with transient version to invalidate all at once when needed.
	 *
	 * @param string $key   Transient key.
	 * @param mix    $value Value to set.
	 *
	 * @return bool True if the cache was set, false otherwise.
	 */
	private function set_cache( $key, $value ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		$transient_version = WC_Cache_Helper::get_transient_version( CacheController::CACHE_GROUP );
		$transient_value   = array(
			'version' => $transient_version,
			'value'   => $value,
		);

		$result = set_transient( $key, $transient_value, DAY_IN_SECONDS );

		return $result;
	}

	/**
	 * Get cached product IDs from query vars.
	 *
	 * Executes a WP_Query with the given query vars and returns a comma-separated string of product IDs.
	 * Results are cached to avoid repeated database queries.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return string Comma-separated list of product IDs.
	 */
	private function get_cached_product_ids( array $query_vars ) {
		$cache_key = WC_Cache_Helper::get_cache_prefix( CacheController::CACHE_GROUP ) . md5( wp_json_encode( $query_vars ) );
		$cache     = wp_cache_get( $cache_key );

		if ( $cache ) {
			return $cache;
		}

		add_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();

		$query->query( $query_vars );

		remove_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		global $wpdb;

		// The query is already prepared by WP_Query.
		$results = $wpdb->get_results( $query->request, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $results ) {
			$results = array();
		}

		$results = implode( ',', array_column( $results, 'ID' ) );

		wp_cache_set( $cache_key, $results );

		return $results;
	}
}
