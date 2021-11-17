<?php

/**
 * WC Tombstone API Controller.
 *
 * @extends WC_REST_CRUD_Controller
 */
class WC_REST_Tombstones_Controller extends WC_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'tombstones';

	/**
	 * The WP_REST_Request object.
	 *
	 * @var array
	 */
	protected $request = array();

	/**
	 * Register the routes for tombstones.
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
			)
		);
	}

	/**
	 * Get the tombstone IDs.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$filters = array();

		if ( $request->get_param( 'modified_before' ) ) {
			$filters['modified_before'] = $request->get_param( 'modified_before' );
		}

		if ( $request->get_param( 'modified_after' ) ) {
			$filters['modified_after'] = $request->get_param( 'modified_after' );
		}

		return WC_Tombstones::ids( $filters );
	}

	/**
	 * Check current user can access tombstones.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}
