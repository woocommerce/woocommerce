<?php
/**
 * REST API Data controller
 *
 * Handles requests to the /data/location endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Data controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Data_Index_Controller extends WC_REST_Data_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'data';

	/**
	 * Register routes.
	 *
	 * @since 3.1.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		) );
	}

	/**
	 * Return the list of data resources
	 *
	 * @since  3.1.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$data    = array();
		$resources = array(
			array(
				'slug' => 'locations',
				'description' => __( 'List of supported continents, countries, and states', 'woocommerce' ),
			),
			array(
				'slug' => 'locations/<continent>',
				'description' => __( 'List of supported continents, countries, and states; restricted to the given continent', 'woocommerce' ),
			),
			array(
				'slug' => 'locations/<continent>/<country>',
				'description' => __( 'List of supported continents, countries, and states; restricted to the given continent and country', 'woocommerce' ),
			),
		);

		foreach ( $resources as $resource ) {
			$item   = $this->prepare_item_for_response( (object) $resource, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a data resource object for serialization.
	 *
	 * @param stdClass $report Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $resource, $request ) {
		$data = array(
			'slug'        => $resource->slug,
			'description' => $resource->description,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $resource->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		) );

		return $response;
	}
}
