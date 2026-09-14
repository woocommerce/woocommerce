<?php
/**
 * REST Controller for API Version 4
 *
 * This is a completely independent base controller for WooCommerce API v4.
 * Unlike previous versions, this does not inherit from v3, v2, or v1 controllers.
 *
 * @class   WC_REST_V4_Controller
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce REST API Version 4 Base Controller
 *
 * @package WooCommerce\RestApi
 * @extends WP_REST_Controller
 * @version 4.0.0
 */
abstract class WC_REST_V4_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Check permissions for a given request.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @param string          $permission The permission to check for.
	 * @return bool|WP_Error
	 */
	protected function check_permissions( $request, $permission = 'read' ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', $permission ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_' . $permission,
				__( 'Sorry, you are not allowed to perform this action.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get the default REST API version.
	 *
	 * @return string
	 */
	protected function get_api_version() {
		return 'v4';
	}

	/**
	 * Prepare a response for inserting into a collection.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @return array Response data.
	 */
	public function prepare_response_for_collection( $response ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		$data   = (array) $response->get_data();
		$server = rest_get_server();
		$links  = $server->get_compact_response_links( $response );

		if ( ! empty( $links ) ) {
			$data['_links'] = $links;
		}

		return $data;
	}

	/**
	 * Get the base schema for the API.
	 *
	 * @return array
	 */
	protected function get_base_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'base',
			'type'       => 'object',
			'properties' => array(),
		);
	}
}
