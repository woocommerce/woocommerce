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
		$params                 = parent::get_collection_params();
		$params['product_name'] = array(
			'description'       => __( 'Search for a similar product name.', 'wc-admin' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}

	/**
	 * Add product name filtering to the WC API.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( ! empty( $request['product_name'] ) ) {
			$args['post_title__like'] = $request['product_name'];
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
		add_filter( 'posts_where', array( __CLASS__, 'add_wp_query_post_title_filter' ), 10, 2 );
		$response = parent::get_items( $request );
		remove_filter( 'posts_where', array( __CLASS__, 'add_wp_query_post_title_filter' ), 10 );
		return $response;
	}

	/**
	 * Add post title searching to WP Query
	 *
	 * @param string $where Where clause used to search posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public static function add_wp_query_post_title_filter( $where, $wp_query ) {
		global $wpdb;

		$post_title_search = $wp_query->get( 'post_title__like' );
		if ( $post_title_search ) {
			$post_title_search = $wpdb->esc_like( $post_title_search );
			$post_title_search = ' \'%' . $post_title_search . '%\'';
			$where            .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $post_title_search;
		}

		return $where;
	}

}
