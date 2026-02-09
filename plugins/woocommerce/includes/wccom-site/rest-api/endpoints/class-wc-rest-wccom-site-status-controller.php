<?php
/**
 * WCCOM Site Status REST API Controller
 *
 * Handle requests to /status.
 *
 * @package WooCommerce\WCCom\API
 * @since   8.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM Site Status Controller Class.
 *
 * @extends WC_REST_WCCOM_Site_Status_Controller
 */
class WC_REST_WCCOM_Site_Status_Controller extends WC_REST_WCCOM_Site_Controller {


	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'status';

	/**
	 * Register the routes for Site Status Controller.
	 *
	 * @since 8.7.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_status_request' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);
	}

	/**
	 * Check whether user has permission to access controller's endpoints.
	 *
	 * @since 8.7.0
	 * @param WP_USER $user User object.
	 * @return bool
	 */
	public function user_has_permission( $user ): bool {
		return user_can( $user, 'install_plugins' ) && user_can( $user, 'activate_plugins' );
	}

	/**
	 * Get the status details of the site.
	 *
	 * @since  8.7.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function handle_status_request( $request ) {

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'wc_version'                   => WC()->version,
					'woo_update_manager_installed' => WC_Woo_Update_Manager_Plugin::is_plugin_installed(),
					'woo_update_manager_active'    => WC_Woo_Update_Manager_Plugin::is_plugin_active(),
				),
			)
		);
	}
}
