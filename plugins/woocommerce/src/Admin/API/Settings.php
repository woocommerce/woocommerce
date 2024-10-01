<?php
/**
 * REST API Settings Controller
 *
 * Handles requests to save Settings.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Controller.
 *
 * @extends WC_REST_Data_Controller
 */
class Settings extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		error_log( 'register_routes for settings' );	
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base, 
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to update settings.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		return true;
		// return current_user_can( 'manage_options' );
	}

	/**
	 * Save settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function save_settings( $request ) {
		return new \WP_REST_Response( array( 'status' => 'success' ) );
	}
}