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
		$pre_filter_counts = $this->pre_get_filter_data( 'price', $query_vars, );

		if ( isset( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'price' );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		global $wpdb;

		add_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();

		$query->query( $query_vars );

		remove_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$price_filter_sql = "
		SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
		FROM {$wpdb->wc_product_meta_lookup}
		WHERE product_id IN ( {$query->request} )
		";

		/**
		 * We can't use $wpdb->prepare() here because using %s with
		 * $wpdb->prepare() for a subquery won't work as it will escape the SQL
		 * query.
		 * We're using the query as is, same as Core does.
		 */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_row( $price_filter_sql );

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
		$pre_filter_counts = $this->pre_get_filter_data( 'stock', $query_vars );

		if ( isset( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'stock' );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
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
		$stock_status_counts = array();

		foreach ( $statuses as $status ) {
			$stock_status_count_sql = "
				SELECT COUNT( DISTINCT posts.ID ) as status_count
				FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id
				AND postmeta.meta_key = '_stock_status'
				AND postmeta.meta_value = '" . esc_sql( $status ) . "'
				WHERE posts.ID IN ( {$query->request} )
			";

			/**
			 * We can't use $wpdb->prepare() here because using %s with
			 * $wpdb->prepare() for a subquery won't work as it will escape the
			 * SQL query.
			 * We're using the query as is, same as Core does.
			 */
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result                         = $wpdb->get_row( $stock_status_count_sql );
			$stock_status_counts[ $status ] = $result->status_count;
		}

		$this->set_cache( $transient_key, $stock_status_counts );

		return $stock_status_counts;
	}

	/**
	 * Get rating counts for the current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array rating=>count pairs.
	 */
	public function get_rating_counts( array $query_vars ) {
		$pre_filter_counts = $this->pre_get_filter_data( 'rating', $query_vars );

		if ( isset( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'rating' );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		global $wpdb;

		add_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();

		$query->query( $query_vars );

		remove_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$rating_count_sql = "
			SELECT COUNT( DISTINCT product_id ) as product_count, ROUND( average_rating, 0 ) as rounded_average_rating
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN ( {$query->request} )
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
		$pre_filter_counts = $this->pre_get_filter_data( 'attribute', $query_vars, array( 'taxonomy' => $attribute_to_count ) );

		if ( isset( $pre_filter_counts ) ) {
			return $pre_filter_counts;
		}

		$transient_key = $this->get_transient_key( $query_vars, 'attribute', array( 'taxonomy' => $attribute_to_count ) );
		$cached_data   = $this->get_cache( $transient_key );

		if ( ! empty( $cached_data ) ) {
			return $cached_data;
		}

		global $wpdb;

		add_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();

		$query->query( $query_vars );

		remove_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$attributes_to_count_sql = 'AND term_taxonomy.taxonomy IN ("' . esc_sql( wc_sanitize_taxonomy_name( $attribute_to_count ) ) . '")';
		$attribute_count_sql     = "
			SELECT COUNT( DISTINCT posts.ID ) as term_count, terms.term_id as term_count_id
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
			INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			WHERE posts.ID IN ( {$query->request} )
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

		$this->set_cache( $transient_key, $results );

		return $results;
	}

	/**
	 * Get the offload filter data.
	 *
	 * @param string $filter_type The type of filter. Accepts price|stock|rating|attribute.
	 * @param array  $query_vars  The query arguments to calculate the filter data.
	 * @param array  $extra       Some filter types require extra arguments for calculation, like attribute.
	 */
	private function pre_get_filter_data( string $filter_type, array $query_vars, array $extra = array() ) {
		/**
		 * Allows offloading the filter data to external services like Elasticsearch.
		 *
		 * @hook woocommerce_pre_product_filter_data
		 *
		 * @since 9.9.0
		 *
		 * @param mixed $results      The results for current query.
		 * @param string $filter_type The type of filter. Accepts price|stock|rating|attribute.
		 * @param array $query_vars   The query arguments to calculate the filter data.
		 * @param array $extra        Some filter types require extra arguments for calculation, like attribute.
		 */
		return apply_filters( 'woocommerce_pre_product_filter_data', null, $filter_type, $query_vars, $extra );
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
			CacheController::TRANSIENT_GROUP,
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
		$transient_version = WC_Cache_Helper::get_transient_version( CacheController::TRANSIENT_GROUP );

		if ( empty( $cache['version'] ) ||
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
	 */
	private function set_cache( $key, $value ) {
		$transient_version = WC_Cache_Helper::get_transient_version( CacheController::TRANSIENT_GROUP );
		$transient_value   = array(
			'version' => $transient_version,
			'value'   => $value,
		);

		$result = set_transient( $key, $transient_value );

		return $result;
	}
}
