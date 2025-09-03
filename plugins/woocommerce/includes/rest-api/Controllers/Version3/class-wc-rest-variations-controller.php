<?php
/**
 * REST API variations controller
 *
 * Handles requests to the /variations endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   10.3.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * REST API variations controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Variations_Controller
 */
class WC_REST_Variations_Controller extends WC_REST_Product_Variations_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'variations';

	/**
	 * Register the routes for variations.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get the Product's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

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
	 * @param  WC_Data         $data_object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $data_object, $request ) {
		$context  = empty( $request['context'] ) ? 'view' : $request['context'];
		$response = parent::prepare_object_for_response( $data_object, $request );
		$data     = $response->get_data();

		$data['parent_id'] = $data_object->get_parent_id( $context );

		$response->set_data( $data );

		return $response;
	}
}
