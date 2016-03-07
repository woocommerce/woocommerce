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
		$locations          = apply_filters( 'woocommerce_settings_locations', array() );
		$defaults           = array(
			'id'            => null,
			'type'          => 'page',
			'label'         => null,
			'description'   => '',
		);
		$filtered_locations = array();
		foreach ( $locations as $location ) {
			$location = wp_parse_args( $location, $defaults );
			$location_valid = true;
			if ( is_null( $location['id'] ) || is_null( $location['label'] ) || is_null( $location['type'] ) ) { // id, label, and  type are required fields
				$location_valid = false;
			} else if ( ! empty( $request['type'] ) ) {
				if ( in_array( $request['type'], $this->get_location_types() ) && $request['type'] !== $location['type'] ) {
					$location_valid = false;
				}
			}

			if ( $location_valid ) {
				$filtered_locations[] = array_intersect_key(
					$location,
					array_flip( array_filter( array_keys( $location ), array( $this, 'filter_location_keys' ) ) )
				);
			}
		}
		$response = rest_ensure_response( $filtered_locations );
		return $response;
	}

	/**
	 * Callback for Allowed keys for each location response.
	 * @since 2.7.0
	 * @param  string $key Key to check
	 * @return boolean
	 */
	public function filter_location_keys( $key ) {
		return in_array( $key, array( 'id', 'type', 'label', 'description' ) );
	}

	/**
	 * Get supported query parameters for locations.
	 * @since 2.7.0
	 * @return array
	 */
	public function get_locations_params() {
		$query_params = array();

		$query_params['type'] = array(
			'description'        => __( 'Limit result set to setting locations of a specific type.', 'woocommerce' ),
			'type'               => 'string'
		);

		return $query_params;
	}

	/**
	 * Get the locations chema, conforming to JSON Schema.
	 * @since 2.7.0
	 * @return array
	 */
	public function get_locations_schema() {
		/*$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'locations',
			'type'                 => 'object',
			'properties'           => 
		);*/
	}

	/**
	 * Returns a list of allowed setting location types.
	 * @since 2.7.0
	 * @return array
	 */
	protected function get_location_types() {
		return apply_filters( 'woocommerce_settings_location_types', array( 'page', 'metabox', 'shipping-zone' ) );
	}

}
