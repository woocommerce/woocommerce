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
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Retrieve variations without specifying a parent product.
		if ( "/{$this->namespace}/variations" === $request->get_route() ) {
			unset( $args['post_parent'] );
		}

		return $args;
	}
}
