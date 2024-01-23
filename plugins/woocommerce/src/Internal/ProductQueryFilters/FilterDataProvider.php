<?php
/**
 * FilterDataProvider class file.
 */

namespace Automattic\WooCommerce\Internal\ProductQueryFilters;

defined( 'ABSPATH' ) || exit;

/**
 * Class for filter data.
 */
class FilterDataProvider {
	/**
	 * Instance of FilterClausesGenerator.
	 *
	 * @var FilterClausesGenerator
	 */
	private $filter_clauses_generator;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FilterClausesGenerator $filter_clauses_generator Instance of FilterClausesGenerator.
	 *
	 * @return void
	 */
	final public function init( ClausesGeneratorInterface $filter_clauses_generator ): void {
		$this->set_filter_clauses_generator_provider( $filter_clauses_generator );
	}

	/**
	 * Allow setting the clauses provider at run time.
	 *
	 * @param FilterClausesGenerator $filter_clauses_generator Instance of FilterClausesGenerator.
	 *
	 * @return void
	 */
	final public function set_filter_clauses_generator_provider( ClausesGeneratorInterface $filter_clauses_generator ): void {
		$this->filter_clauses_generator = $filter_clauses_generator;
	}

	/**
	 * Get price data for current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return object
	 */
	public function get_filtered_price( $query_vars ) {
		global $wpdb;

		add_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_vars );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10 );
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

		add_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_vars );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10 );
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

		add_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_vars );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$rating_count_sql = "
			SELECT COUNT( DISTINCT product_id ) as product_count, ROUND( average_rating, 0 ) as rounded_average_rating
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN ( {$product_query_sql} )
			AND average_rating > 0
			GROUP BY rounded_average_rating
			ORDER BY rounded_average_rating ASC
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

		add_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_vars['no_found_rows']  = true;
		$query_vars['posts_per_page'] = -1;
		$query_vars['fields']         = 'ids';
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_vars );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $this->filter_clauses_generator, 'add_query_clauses' ), 10 );
		remove_filter( 'posts_pre_query', '__return_empty_array' );

		$attributes_to_count_sql = 'AND term_taxonomy.taxonomy IN ("' . esc_sql( wc_sanitize_taxonomy_name( $attribute_to_count ) ) . '")';
		$attribute_count_sql     = "
			SELECT COUNT( DISTINCT posts.ID ) as term_count, terms.term_id as term_count_id
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
			INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			WHERE posts.ID IN ( {$product_query_sql} )
			{$attributes_to_count_sql}
			GROUP BY terms.term_id
		";

		$results = $wpdb->get_results( $attribute_count_sql ); // phpcs:ignore

		return array_map( 'absint', wp_list_pluck( $results, 'term_count', 'term_count_id' ) );
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
}
