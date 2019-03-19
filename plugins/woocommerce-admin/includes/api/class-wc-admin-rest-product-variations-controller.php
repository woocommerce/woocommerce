<?php
/**
 * REST API Product Variations Controller
 *
 * Handles requests to /products/variations.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product variations controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Product_Variations_Controller
 */
class WC_Admin_REST_Product_Variations_Controller extends WC_REST_Product_Variations_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params           = parent::get_collection_params();
		$params['search'] = array(
			'description'       => __( 'Search by similar product name or sku.', 'woocommerce-admin' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}

	/**
	 * Add product name and sku filtering to the WC API.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( ! empty( $request['search'] ) ) {
			$args['search'] = $request['search'];
			unset( $args['s'] );
		}

		return $args;
	}

	/**
	 * Get a collection of posts and add the post title filter option to WP_Query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		add_filter( 'posts_where', array( 'WC_Admin_REST_Products_Controller', 'add_wp_query_product_search_filter' ), 10, 2 );
		add_filter( 'posts_join', array( 'WC_Admin_REST_Products_Controller', 'add_wp_query_product_search_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( 'WC_Admin_REST_Products_Controller', 'add_wp_query_product_search_group_by' ), 10, 2 );
		$response = parent::get_items( $request );
		remove_filter( 'posts_where', array( 'WC_Admin_REST_Products_Controller', 'add_wp_query_product_search_filter' ), 10 );
		remove_filter( 'posts_join', array( 'WC_Admin_REST_Products_Controller', 'add_wp_query_product_search_join' ), 10 );
		remove_filter( 'posts_groupby', array( 'WC_Admin_REST_Products_Controller', 'add_wp_query_product_search_group_by' ), 10 );
		return $response;
	}
}
