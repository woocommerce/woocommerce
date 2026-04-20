<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Service\POSApprovalService;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\POS\Service\POSRateLimitService;
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

	use POSRequestTrait;

	/**
	 * Actions that can be approved through manager override.
	 *
	 * @var string[]
	 */
	private const APPROVABLE_ACTIONS = array(
		'refund_shop_orders',
		'void_shop_orders',
		'publish_shop_coupons',
		'apply_discounts',
		'override_prices',
	);

	/**
	 * Actions that require an order ID in context.
	 *
	 * @var string[]
	 */
	private const ORDER_SCOPED_ACTIONS = array(
		'refund_shop_orders',
		'void_shop_orders',
		'apply_discounts',
		'override_prices',
	);

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @var POSApprovalService
	 */
	private POSApprovalService $approval_service;

	/**
	 * @var POSRateLimitService
	 */
	private POSRateLimitService $rate_limit_service;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @param POSPinService       $pin_service        PIN service.
	 * @param POSApprovalService  $approval_service   Approval service.
	 * @param POSRateLimitService $rate_limit_service Rate limit service.
	 *
	 * @internal
	 * @since 10.8.0
	 */
	final public function init(
		POSPinService $pin_service,
		POSApprovalService $approval_service,
		POSRateLimitService $rate_limit_service
	): void {
		$this->pin_service        = $pin_service;
		$this->approval_service   = $approval_service;
		$this->rate_limit_service = $rate_limit_service;
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
						'view_pos'
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
		$start_time = microtime( true );

		try {
			return $this->do_approve_action( $request );
		} finally {
			$this->pad_response_time( $start_time );
		}
	}

	/**
	 * Internal approval logic.
	 *
	 * @since 10.8.0
	 * @param WP_REST_Request $request The incoming request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	private function do_approve_action( WP_REST_Request $request ) {
		$client_ip   = $this->get_client_ip();
		$rate_check  = $this->rate_limit_service->check_rate_limit( $client_ip );

		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		$pin         = $request->get_param( 'pin' );
		$action      = $request->get_param( 'action' );
		$context     = $request->get_param( 'context' ) ?? array();
		$logger      = wc_get_logger();
		$log_context = array( 'source' => 'woocommerce-pos' );

		if ( ! in_array( $action, self::APPROVABLE_ACTIONS, true ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_action',
				__( 'The requested approval action is not supported.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order_id = isset( $context['order_id'] ) ? absint( $context['order_id'] ) : 0;
		if ( 0 === $order_id && in_array( $action, self::ORDER_SCOPED_ACTIONS, true ) ) {
			return new WP_Error(
				'woocommerce_pos_missing_order_context',
				__( 'An order ID is required for this approval.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $order_id > 0 ) {
			$context['order_id'] = $order_id;
		}

		$user_id = $this->pin_service->lookup_user_by_pin( $pin );

		if ( ! $user_id ) {
			$logger->warning( 'Approval failed: no user found for provided PIN.', $log_context );
			$this->rate_limit_service->record_failure( $client_ip );
			return $this->pin_error();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! user_can( $user_id, $action ) ) {
			$user_label = $user ? "{$user->user_login} (ID {$user_id})" : "ID {$user_id}";
			$logger->warning(
				sprintf( 'Approval failed: user %s lacks required capabilities.', $user_label ),
				$log_context
			);
			return new WP_Error(
				'woocommerce_pos_approval_forbidden',
				__( 'The approver does not have permission for this action.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$token = $this->approval_service->create_approval( $user_id, $action, $context );

		$logger->info(
			sprintf( 'Approval granted by user %s (ID %d) for action %s.', $user->user_login, $user_id, $action ),
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
