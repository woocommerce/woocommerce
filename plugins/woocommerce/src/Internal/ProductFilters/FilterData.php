<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use WC_Cache_Helper;
use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\QueryClausesGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * Class for filter counts.
 */
class FilterData {
	/**
	 * Instance of QueryClauses.
	 *
	 * @var QueryClausesGenerator
	 */
	private $query_clauses;

	/**
	 * Constructor.
	 *
	 * @param QueryClausesGenerator $query_clauses Instance of QueryClausesGenerator.
	 */
	public function __construct( QueryClausesGenerator $query_clauses ) {
		$this->query_clauses = $query_clauses;
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

			$attributes_to_count_sql = 'AND term_taxonomy.taxonomy IN ("' . esc_sql( wc_sanitize_taxonomy_name( $attribute_to_count ) ) . '")';
			$attribute_count_sql     = "
			SELECT COUNT( DISTINCT posts.ID ) as term_count, terms.term_id as term_count_id
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
			INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			WHERE posts.ID IN ( {$product_ids} )
			{$attributes_to_count_sql}
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

			$taxonomies_to_count_sql = 'AND term_taxonomy.taxonomy IN ("' . esc_sql( wc_sanitize_taxonomy_name( $taxonomy_to_count ) ) . '")';
			$taxonomy_count_sql      = "
				SELECT COUNT( DISTINCT term_relationships.object_id ) as term_count, term_taxonomy.term_taxonomy_id as term_count_id
				FROM {$wpdb->term_relationships} AS term_relationships
				INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
				WHERE term_relationships.object_id IN ( {$product_ids} )
				{$taxonomies_to_count_sql}
				GROUP BY term_taxonomy.term_taxonomy_id
			";

			/**
			 * We can't use $wpdb->prepare() here because using %s with
			 * $wpdb->prepare() for a subquery won't work as it will escape the
			 * SQL query.
			 * We're using the query as is, same as Core does.
			 */
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $taxonomy_count_sql );
			$results = array_map( 'absint', wp_list_pluck( $results, 'term_count', 'term_count_id' ) );
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

		$result = set_transient( $key, $transient_value );

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
