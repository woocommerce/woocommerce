<?php
/**
 * REST API Products Controller
 *
 * Handles requests to /products/*
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Products controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Products_Controller
 */
class WC_Admin_REST_Products_Controller extends WC_REST_Products_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Adds properties that can be embed via ?_embed=1.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$properties_to_embed = array(
			'id',
			'name',
			'slug',
			'permalink',
			'images',
			'description',
			'short_description',
		);

		foreach ( $properties_to_embed as $property ) {
			$schema['properties'][ $property ]['context'][] = 'embed';
		}

		return $schema;
	}

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
		add_filter( 'posts_where', array( __CLASS__, 'add_wp_query_product_search_filter' ), 10, 2 );
		add_filter( 'posts_join', array( __CLASS__, 'add_wp_query_product_search_join' ), 10, 2 );
		$response = parent::get_items( $request );
		remove_filter( 'posts_where', array( __CLASS__, 'add_wp_query_product_search_filter' ), 10 );
		remove_filter( 'posts_join', array( __CLASS__, 'add_wp_query_product_search_join' ), 10 );
		return $response;
	}

	/**
	 * Allow searching by product name or sku in WP Query
	 *
	 * @param string $where Where clause used to search posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public static function add_wp_query_product_search_filter( $where, $wp_query ) {
		global $wpdb;

		$search = trim( $wp_query->get( 'search' ) );
		if ( $search ) {
			$search = $wpdb->esc_like( $search );
			$search = "'%" . $search . "%'";
			$where .= ' AND (' . $wpdb->posts . '.post_title LIKE ' . $search;
			$where .= wc_product_sku_enabled() ? ' OR ps_post_meta.meta_key = "_sku" AND ps_post_meta.meta_value LIKE ' . $search . ')' : ')';
		}

		return $where;
	}

	/**
	 * Join posts meta table when product search is present and meta query is not present.
	 *
	 * @param string $join Join clause used to search posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public static function add_wp_query_product_search_join( $join, $wp_query ) {
		global $wpdb;

		$search = trim( $wp_query->get( 'search' ) );
		if ( $search && wc_product_sku_enabled() ) {
			$join .= ' INNER JOIN ' . $wpdb->postmeta . ' AS ps_post_meta ON ps_post_meta.post_id = ' . $wpdb->posts . '.ID';
		}

		return $join;
	}

}
