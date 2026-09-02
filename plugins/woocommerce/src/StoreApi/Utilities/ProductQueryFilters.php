<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\StoreApi\Utilities\ProductQuery;

/**
 * Product Query filters class.
 */
class ProductQueryFilters {
	/**
	 * Get filtered min price for current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return object
	 */
	public function get_filtered_price( $request ) {
		global $wpdb;

		// Regenerate the products query without min/max price request params.
		unset( $request['min_price'], $request['max_price'] );

		// Grab the request from the WP Query object, and remove SQL_CALC_FOUND_ROWS and Limits so we get a list of all products.
		$product_query = new ProductQuery();

		add_filter( 'posts_clauses', array( $product_query, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_args                   = $product_query->prepare_objects_query( $request );
		$query_args['no_found_rows']  = true;
		$query_args['posts_per_page'] = -1;
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_args );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $product_query, 'add_query_clauses' ), 10 );
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
	 * @param \WP_REST_Request $request The request object.
	 * @return array status=>count pairs.
	 */
	public function get_stock_status_counts( $request ) {
		global $wpdb;
		$product_query         = new ProductQuery();
		$stock_status_options  = array_map( 'esc_sql', array_keys( wc_get_product_stock_status_options() ) );
		$hide_outofstock_items = get_option( 'woocommerce_hide_out_of_stock_items' );
		if ( 'yes' === $hide_outofstock_items ) {
			unset( $stock_status_options[ ProductStockStatus::OUT_OF_STOCK ] );
		}

		add_filter( 'posts_clauses', array( $product_query, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_args = $product_query->prepare_objects_query( $request );
		unset( $query_args['stock_status'] );
		$query_args['no_found_rows']  = true;
		$query_args['posts_per_page'] = -1;
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_args );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $product_query, 'add_query_clauses' ), 10 );
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
	 * Get attribute counts for the current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @param array            $attributes Attributes to count, either names or ids.
	 * @return array termId=>count pairs.
	 */
	public function get_attribute_counts( $request, $attributes = [] ) {
		// Remove paging and sorting params from the request.
		$request->set_param( 'page', null );
		$request->set_param( 'per_page', null );
		$request->set_param( 'order', null );
		$request->set_param( 'orderby', null );

		// Convert request to query_vars for FilterData.
		$product_query = new ProductQuery();
		$query_vars    = $product_query->prepare_objects_query( $request );

		if ( count( $attributes ) === count( array_filter( $attributes, 'is_numeric' ) ) ) {
			$attributes = array_map( 'wc_attribute_taxonomy_name_by_id', wp_parse_id_list( $attributes ) );
		}

		// Use FilterData with ProductQuery as QueryClausesGenerator. This shares the cached,
		// invalidation-aware filter-data path used by get_taxonomy_counts() and the product filter blocks.
		$container = wc_get_container();

		$filter_data_provider = $container->get( \Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider::class );
		$filter_data          = $filter_data_provider->with( $product_query );

		$all_counts = array();

		// Get counts for each attribute taxonomy individually so each is cached separately.
		foreach ( $attributes as $attribute ) {
			$taxonomy = wc_sanitize_taxonomy_name( $attribute );
			if ( ! $taxonomy ) {
				continue;
			}
			$counts = $filter_data->get_attribute_counts( $query_vars, $taxonomy );
			// Each attribute taxonomy owns a disjoint set of term IDs, so the union operator safely
			// merges the term_id => count pairs without colliding or overwriting between taxonomies.
			$all_counts = $all_counts + $counts;
		}

		return $all_counts;
	}

	/**
	 * Get rating counts for the current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array rating=>count pairs.
	 */
	public function get_rating_counts( $request ) {
		global $wpdb;

		// Regenerate the products query without rating request params.
		unset( $request['rating'] );

		// Grab the request from the WP Query object, and remove SQL_CALC_FOUND_ROWS and Limits so we get a list of all products.
		$product_query = new ProductQuery();

		add_filter( 'posts_clauses', array( $product_query, 'add_query_clauses' ), 10, 2 );
		add_filter( 'posts_pre_query', '__return_empty_array' );

		$query_args                   = $product_query->prepare_objects_query( $request );
		$query_args['no_found_rows']  = true;
		$query_args['posts_per_page'] = -1;
		$query                        = new \WP_Query();
		$result                       = $query->query( $query_args );
		$product_query_sql            = $query->request;

		remove_filter( 'posts_clauses', array( $product_query, 'add_query_clauses' ), 10 );
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
	 * Get taxonomy counts for the current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @param array            $taxonomies Taxonomies to count.
	 * @return array termId=>count pairs.
	 */
	public function get_taxonomy_counts( $request, $taxonomies = [] ) {
		// Remove paging and sorting params from the request.
		$request->set_param( 'page', null );
		$request->set_param( 'per_page', null );
		$request->set_param( 'order', null );
		$request->set_param( 'orderby', null );

		// Convert request to query_vars for FilterData.
		$product_query = new ProductQuery();
		$query_vars    = $product_query->prepare_objects_query( $request );

		// Use FilterData with ProductQuery as QueryClausesGenerator.
		$container = wc_get_container();

		$filter_data_provider = $container->get( \Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider::class );
		$filter_data          = $filter_data_provider->with( $product_query );

		$all_counts = array();

		// Get counts for each taxonomy individually.
		foreach ( $taxonomies as $taxonomy ) {
			$counts     = $filter_data->get_taxonomy_counts( $query_vars, $taxonomy );
			$all_counts = $all_counts + $counts; // Use + operator to preserve keys.
		}

		return $all_counts;
	}
}
