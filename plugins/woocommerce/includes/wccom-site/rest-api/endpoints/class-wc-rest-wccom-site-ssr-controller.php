<?php
/**
 * WCCOM Site System Status Report REST API Controller
 *
 * Handles requests to /ssr.
 *
 * @package WooCommerce\WCCom\API
 * @since   7.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM System Status Report Controller Class.
 *
 * @extends WC_REST_WCCOM_Site_Controller
 */
class WC_REST_WCCOM_Site_SSR_Controller extends WC_REST_WCCOM_Site_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ssr';

	/**
	 * Register the routes for SSR Controller.
	 *
	 * @since 7.8.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_ssr_request' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);
	}

	/**
	 * Check whether user has permission to access controller's endpoints.
	 *
	 * @since 8.6.0
	 * @param WP_USER $user User object.
	 * @return bool
	 */
	public function user_has_permission( $user ) : bool {
		return user_can( $user, 'manage_woocommerce' );
	}

	/**
	 * Generate SSR data and submit it to WooCommmerce.com.
	 *
	 * @since  7.8.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function handle_ssr_request( $request ) {
		$ssr_controller = new WC_REST_System_Status_Controller();
		$data           = $ssr_controller->get_items( $request );
		$data           = $data->get_data();

		// Submit SSR data to Woo.com.
		$request = WC_Helper_API::post(
			'ssr',
			array(
				'body'          => wp_json_encode( array( 'data' => $data ) ),
				'authenticated' => true,
			)
		);

		$response_code = wp_remote_retrieve_response_code( $request );

		if ( 201 === $response_code ) {
			$response = rest_ensure_response(
				array(
					'success' => true,
					'message' => 'SSR data submitted successfully',
				)
			);
		} else {
			$response = rest_ensure_response(
				array(
					'success'       => false,
					'error_code'    => 'failed_submitting_ssr',
					'error_message' => "Submitting SSR data failed with response code: $response_code",
				)
			);
		}

		return $response;
	}
}
