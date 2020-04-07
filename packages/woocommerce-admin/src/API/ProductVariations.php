<?php
/**
 * REST API Product Variations Controller
 *
 * Handles requests to /products/variations.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Product variations controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Product_Variations_Controller
 */
class ProductVariations extends \WC_REST_Product_Variations_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params           = parent::get_collection_params();
		$params['search'] = array(
			'description'       => __( 'Search by similar product name or sku.', 'woocommerce' ),
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
		add_filter( 'posts_where', array( 'Automattic\WooCommerce\Admin\API\Products', 'add_wp_query_filter' ), 10, 2 );
		add_filter( 'posts_join', array( 'Automattic\WooCommerce\Admin\API\Products', 'add_wp_query_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( 'Automattic\WooCommerce\Admin\API\Products', 'add_wp_query_group_by' ), 10, 2 );
		$response = parent::get_items( $request );
		remove_filter( 'posts_where', array( 'Automattic\WooCommerce\Admin\API\Products', 'add_wp_query_filter' ), 10 );
		remove_filter( 'posts_join', array( 'Automattic\WooCommerce\Admin\API\Products', 'add_wp_query_join' ), 10 );
		remove_filter( 'posts_groupby', array( 'Automattic\WooCommerce\Admin\API\Products', 'add_wp_query_group_by' ), 10 );
		return $response;
	}

	/**
	 * Get the Product's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['name']      = array(
			'description' => __( 'Product parent name.', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['type']      = array(
			'description' => __( 'Product type.', 'woocommerce' ),
			'type'        => 'string',
			'default'     => 'variation',
			'enum'        => array( 'variation' ),
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['parent_id'] = array(
			'description' => __( 'Product parent ID.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
		);

		return $schema;
	}

	/**
	 * Prepare a single variation output for response.
	 *
	 * @param  WC_Data         $object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$context  = empty( $request['context'] ) ? 'view' : $request['context'];
		$response = parent::prepare_object_for_response( $object, $request );
		$data     = $response->get_data();

		$data['name']      = $object->get_name( $context );
		$data['type']      = $object->get_type();
		$data['parent_id'] = $object->get_parent_id( $context );

		$response->set_data( $data );

		return $response;
	}
}
