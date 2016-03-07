<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings Controller.
 * Handles requests to the /settings endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @version  2.7.0
 * @since    2.7.0
 */
class WC_Rest_Settings_Controller extends WP_Rest_Controller {

	/**
	 * Route base.
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register routes.
	 * @since 2.7.0
	 */
	public function register_routes() {
		register_rest_route( WC_API::REST_API_NAMESPACE, '/' . $this->rest_base . '/locations', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_locations' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_locations_params(),
			),
			'schema' => array( $this, 'get_locations_schema' ),
		) );

	}

	/**
	 * Makes sure the current user has access to the settings APIs.
	 * @since 2.7.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| /settings/locations
	|--------------------------------------------------------------------------
	| Returns a list of "settings" locations so all settings for a particular page
	| or location can be properly loaded.
	*/

	/**
	 * Get all settings locations.
	 * @since 2.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_locations( $request ) {
		$response = rest_ensure_response( array() );
		return $response;
	}

	/**
	 * Get supported query parameters for locations.
	 * @since 2.7.0
	 * @return array
	 */
	public function get_locations_params() {
		$query_params = parent::get_collection_params();
		$query_params['context']['default'] = 'view';
		return $query_params;
	}

	/**
	 * Get the locations chema, conforming to JSON Schema
	 * @since 2.7.0
	 * @return array
	 */
	public function get_locations_schema() {

	}

}
