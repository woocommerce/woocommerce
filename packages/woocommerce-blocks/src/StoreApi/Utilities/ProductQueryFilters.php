<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\ProductQuery;

/**
 * Product Query filters class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @since 2.5.0
 */
class ProductQueryFilters {
	/**
	 * Get filtered min price for current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array
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
	 * Get attribute counts for the current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @param array            $attributes Attributes to count, either names or ids.
	 * @return array termId=>count pairs.
	 */
	public function get_attribute_counts( $request, $attributes = [] ) {
		global $wpdb;

		// Remove paging and sorting params from the request.
		$request->set_param( 'page', null );
		$request->set_param( 'per_page', null );
		$request->set_param( 'order', null );
		$request->set_param( 'orderby', null );

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

		if ( count( $attributes ) === count( array_filter( $attributes, 'is_numeric' ) ) ) {
			$attributes = array_map( 'wc_attribute_taxonomy_name_by_id', wp_parse_id_list( $attributes ) );
		}

		$attributes_to_count     = array_map( 'wc_sanitize_taxonomy_name', $attributes );
		$attributes_to_count_sql = 'AND term_taxonomy.taxonomy IN ("' . implode( '","', $attributes_to_count ) . '")';
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
}
