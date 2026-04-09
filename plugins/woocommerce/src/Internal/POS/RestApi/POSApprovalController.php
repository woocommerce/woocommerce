<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Service\POSApprovalService;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST API controller for POS manager approval overrides.
 *
 * @since 10.8.0
 * @internal
 */
class POSApprovalController extends RestApiControllerBase implements RegisterHooksInterface {

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @var POSApprovalService
	 */
	private POSApprovalService $approval_service;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @param POSPinService      $pin_service      PIN service.
	 * @param POSApprovalService $approval_service Approval service.
	 *
	 * @internal
	 * @since 10.8.0
	 */
	final public function init( POSPinService $pin_service, POSApprovalService $approval_service ): void {
		$this->pin_service      = $pin_service;
		$this->approval_service = $approval_service;
	}

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @since 10.8.0
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'pos-approval';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @since 10.8.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/pos/auth/approve',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'approve_action' ),
					'permission_callback' => fn( $request ) => $this->check_permission(
						$request,
						'woocommerce_pos_access'
					),
					'args'                => array(
						'pin'             => array(
							'required' => true,
							'type'     => 'string',
						),
						'action'          => array(
							'required' => true,
							'type'     => 'string',
						),
						'context'         => array(
							'type'    => 'object',
							'default' => array(),
						),
						'idempotency_key' => array(
							'type' => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Validate approver PIN and create an approval token.
	 *
	 * @since 10.8.0
	 * @param WP_REST_Request $request The incoming request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	protected function approve_action( WP_REST_Request $request ) {
		$pin             = $request->get_param( 'pin' );
		$action          = $request->get_param( 'action' );
		$context         = $request->get_param( 'context' ) ?? array();
		$idempotency_key = $request->get_param( 'idempotency_key' );
		$logger          = wc_get_logger();
		$log_context     = array( 'source' => 'woocommerce-pos' );

		$user_id = $this->pin_service->lookup_user_by_pin( $pin );

		if ( ! $user_id ) {
			$logger->warning( 'Approval failed: no user found for provided PIN.', $log_context );
			return $this->pin_error();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! user_can( $user_id, 'woocommerce_approve_overrides' ) || ! user_can( $user_id, $action ) ) {
			$logger->warning(
				sprintf( 'Approval failed: user %d lacks required capabilities.', $user_id ),
				$log_context
			);
			return new WP_Error(
				'woocommerce_pos_approval_forbidden',
				__( 'The approver does not have permission for this action.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$token = $this->approval_service->create_approval( $user_id, $action, $context, $idempotency_key );

		$logger->info(
			sprintf( 'Approval granted by user %d for action %s.', $user_id, $action ),
			$log_context
		);

		return array(
			'approved'       => true,
			'approver_id'    => $user_id,
			'approver_name'  => $user->display_name,
			'approval_token' => $token,
			'expires_in'     => 300,
		);
	}

	/**
	 * Returns a generic WP_Error for PIN lookup failures (anti-enumeration).
	 *
	 * @since 10.8.0
	 * @return WP_Error
	 */
	private function pin_error(): WP_Error {
		return new WP_Error(
			'woocommerce_pos_invalid_pin',
			__( 'The provided PIN is not valid.', 'woocommerce' ),
			array( 'status' => 422 )
		);
	}
}
