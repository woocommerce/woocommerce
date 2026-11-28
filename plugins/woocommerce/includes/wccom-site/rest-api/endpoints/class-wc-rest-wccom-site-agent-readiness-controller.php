<?php
/**
 * WCCOM Site Agent Readiness REST API Controller
 *
 * Handles requests to /agent-readiness.
 *
 * @package WooCommerce\WCCom\API
 * @since   11.5.0
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\AgentReadiness\ReportAbility;

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM Agent Readiness Controller Class.
 *
 * @extends WC_REST_WCCOM_Site_Controller
 */
class WC_REST_WCCOM_Site_Agent_Readiness_Controller extends WC_REST_WCCOM_Site_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'agent-readiness';

	/**
	 * Register the routes for the Agent Readiness Controller.
	 *
	 * @since 11.5.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_report_request' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);
	}

	/**
	 * Check whether user has permission to access controller's endpoints.
	 *
	 * @since 11.5.0
	 * @param WP_USER $user User object.
	 * @return bool
	 */
	public function user_has_permission( $user ): bool {
		return user_can( $user, 'manage_woocommerce' );
	}

	/**
	 * The store's last agent readiness report, as the woocommerce/get-agent-readiness ability returns it.
	 *
	 * @since  11.5.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function handle_report_request( $request ) {
		unset( $request );
		return $this->get_response(
			array(
				'success' => true,
				'data'    => ReportAbility::execute( array() ),
			)
		);
	}
}
