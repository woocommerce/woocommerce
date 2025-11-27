<?php
/**
 * ClearanceRestController class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API controller for fraud protection session clearance.
 *
 * @since 10.4.0
 */
class ClearanceRestController extends WP_REST_Controller {

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
	protected $rest_base = 'fraud-protection/clearance';

	/**
	 * Session clearance manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private $session_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Get dependencies from container.
		$this->session_manager = wc_get_container()->get( SessionClearanceManager::class );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_clearance_action' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_endpoint_args(),
				),
			)
		);
	}

	/**
	 * Get endpoint arguments.
	 *
	 * @return array Endpoint arguments.
	 */
	private function get_endpoint_args(): array {
		// ! These arguments for demo purposes only. We allow to control the session clearance from the frontend.
		//  TODO: Remove this before merging!!!
		return array(
			'action' => array(
				'required'          => true,
				'type'              => 'string',
				'enum'              => array( 'allow', 'block' ),
				'description'       => __( 'Action to perform: allow or block', 'woocommerce' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Check permission for the request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if permission granted.
	 */
	public function check_permission( $request ): bool {
		// Anyone can access this endpoint (it's for their own session).
		return true;
	}

	/**
	 * Handle clearance action.
	 *
	 * @internal
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_clearance_action( $request ) {
		$action = $request->get_param( 'action' );

		if ( 'allow' === $action ) {
			$this->session_manager->allow_session();
			$message = __( 'Session has been allowed.', 'woocommerce' );
			$status  = SessionClearanceManager::STATUS_ALLOWED;
		} elseif ( 'block' === $action ) {
			$this->session_manager->block_session();
			$message = __( 'Session has been blocked.', 'woocommerce' );
			$status  = SessionClearanceManager::STATUS_BLOCKED;
		} else {
			return new WP_Error(
				'invalid_action',
				__( 'Invalid action provided.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'status'  => $status,
				'message' => $message,
			),
			200
		);
	}
}
