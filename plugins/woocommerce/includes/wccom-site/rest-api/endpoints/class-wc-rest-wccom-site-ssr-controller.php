<?php
/**
 * WCCOM Site System Status Report REST API Controller
 *
 * Handles requests to /ssr.
 *
 * @package WooCommerce\WCCom\API
 * @since   7.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM System Status Report Controller Class.
 *
 * @extends WC_REST_Controller
 */
class WC_REST_WCCOM_Site_SSR_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wccom-site/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ssr';

	/**
	 * Register the routes for WCCCOM Installer Controller.
	 *
	 * @since 3.7.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ssr' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);
	}

	/**
	 * Check permissions.
	 *
	 * Please note that access to this endpoint is also governed by the WC_WCCOM_Site::authenticate_wccom() method.
	 *
	 * @since 7.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user ) || ( $current_user instanceof WP_User && ! $current_user->exists() ) ) {
			return apply_filters(
				WC_WCCOM_Site::AUTH_ERROR_FILTER_NAME,
				new WP_Error(
					WC_REST_WCCOM_Site_Installer_Errors::NOT_AUTHENTICATED_CODE,
					WC_REST_WCCOM_Site_Installer_Errors::NOT_AUTHENTICATED_MESSAGE,
					array( 'status' => WC_REST_WCCOM_Site_Installer_Errors::NOT_AUTHENTICATED_HTTP_CODE )
				)
			);
		}

		if ( ! user_can( $current_user, 'install_plugins' ) || ! user_can( $current_user, 'install_themes' ) ) {
			return new WP_Error(
				WC_REST_WCCOM_Site_Installer_Errors::NO_PERMISSION_CODE,
				WC_REST_WCCOM_Site_Installer_Errors::NO_PERMISSION_MESSAGE,
				array( 'status' => WC_REST_WCCOM_Site_Installer_Errors::NO_PERMISSION_HTTP_CODE )
			);
		}

		return true;
	}

	/**
	 * Get SSR data.
	 *
	 * @since 7.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_ssr( $request ) {
		$ssr_controller = new WC_REST_System_Status_Controller();
		return $ssr_controller->get_items( $request );
	}
}
