<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST controller for POS PIN management and status.
 *
 * @since 10.8.0
 */
class POSPinManageController extends RestApiControllerBase implements RegisterHooksInterface {

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * Initialize the class instance.
	 *
	 * @since 10.8.0
	 *
	 * @param POSPinService $pin_service The PIN service.
	 *
	 * @internal
	 */
	final public function init( POSPinService $pin_service ): void {
		$this->pin_service = $pin_service;
	}

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @since 10.8.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'pos-pin-manage';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @since 10.8.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/pos/auth/pin/manage',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'manage_pin' ),
					'permission_callback' => fn( $request ) => $this->check_manage_permission( $request ),
					'args'                => $this->get_manage_args(),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/pos/auth/pin/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_status' ),
					'permission_callback' => fn( $request ) => $this->check_status_permission( $request ),
				),
			)
		);
	}

	/**
	 * Permission callback for the manage PIN endpoint.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 */
	private function check_manage_permission( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();
		if ( 0 === $current_user_id ) {
			return new WP_Error(
				'woocommerce_rest_cannot_create',
				__( 'Sorry, you cannot create resources.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		$target_user_id = $request->get_param( 'user_id' );

		if ( empty( $target_user_id ) || (int) $target_user_id === $current_user_id ) {
			return $this->check_permission( $request, 'woocommerce_pos_access' );
		}

		return $this->check_permission( $request, 'woocommerce_manage_pos_staff' );
	}

	/**
	 * Permission callback for the status endpoint.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 */
	private function check_status_permission( WP_REST_Request $request ) {
		return $this->check_permission( $request, 'woocommerce_manage_pos_staff' );
	}

	/**
	 * Handle the POST /pos/auth/pin/manage request.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	protected function manage_pin( WP_REST_Request $request ) {
		$target_user_id  = $request->get_param( 'user_id' );
		$current_user_id = get_current_user_id();
		$is_self_update  = empty( $target_user_id ) || (int) $target_user_id === $current_user_id;

		if ( empty( $target_user_id ) ) {
			$target_user_id = $current_user_id;
		}
		$target_user_id = (int) $target_user_id;

		if ( ! user_can( $target_user_id, 'woocommerce_pos_access' ) ) {
			return new WP_Error(
				'woocommerce_rest_invalid_user',
				__( 'Target user does not have POS access.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$action = $request->get_param( 'action' );
		$logger = wc_get_logger();

		if ( 'delete' === $action ) {
			$this->pin_service->delete_pin( $target_user_id );
			$logger->info(
				sprintf( 'POS PIN deleted for user %d by user %d.', $target_user_id, $current_user_id ),
				array( 'source' => 'woocommerce-pos' )
			);
			return array( 'success' => true );
		}

		$pin = $request->get_param( 'pin' );
		if ( empty( $pin ) ) {
			return new WP_Error(
				'woocommerce_rest_missing_pin',
				__( 'A PIN is required for this action.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $is_self_update && $this->pin_service->has_pin( $target_user_id ) ) {
			$current_pin = $request->get_param( 'current_pin' );
			if ( empty( $current_pin ) ) {
				return new WP_Error(
					'woocommerce_rest_missing_current_pin',
					__( 'Current PIN is required to update your own PIN.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$hash = get_user_meta( $target_user_id, POSPinService::PIN_HASH_META_KEY, true );
			if ( ! $hash || ! $this->pin_service->verify_pin( $current_pin, $hash ) ) {
				return new WP_Error(
					'woocommerce_rest_invalid_current_pin',
					__( 'The current PIN is incorrect.', 'woocommerce' ),
					array( 'status' => 403 )
				);
			}
		}

		$result = $this->pin_service->set_pin( $target_user_id, $pin );

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array( 'status' => 422 )
			);
		}

		$logger->info(
			sprintf( 'POS PIN set for user %d by user %d.', $target_user_id, $current_user_id ),
			array( 'source' => 'woocommerce-pos' )
		);

		return array( 'success' => true );
	}

	/**
	 * Handle the GET /pos/auth/pin/status request.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array
	 */
	protected function get_status( WP_REST_Request $request ) {
		unset( $request );

		$users = get_users(
			array(
				'meta_key'     => '',
				'meta_value'   => '',
				'meta_compare' => '',
				'role__in'     => array( 'pos_cashier', 'pos_manager', 'administrator', 'shop_manager' ),
			)
		);

		$result = array();
		foreach ( $users as $user ) {
			if ( ! user_can( $user->ID, 'woocommerce_pos_access' ) ) {
				continue;
			}

			$roles = (array) $user->roles;
			$role  = ! empty( $roles ) ? reset( $roles ) : '';

			$result[] = array(
				'user_id'      => $user->ID,
				'display_name' => $user->display_name,
				'role'         => $role,
				'has_pin'      => $this->pin_service->has_pin( $user->ID ),
			);
		}

		return array( 'users' => $result );
	}

	/**
	 * Get the accepted arguments for the manage PIN endpoint.
	 *
	 * @since 10.8.0
	 *
	 * @return array
	 */
	private function get_manage_args(): array {
		return array(
			'user_id' => array(
				'description'       => __( 'Target user ID. Defaults to current user.', 'woocommerce' ),
				'type'              => 'integer',
				'required'          => false,
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'absint',
			),
			'action'  => array(
				'description'       => __( 'Action to perform: set or delete.', 'woocommerce' ),
				'type'              => 'string',
				'enum'              => array( 'set', 'delete' ),
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'pin'         => array(
				'description'       => __( 'The PIN to set. Required for set action.', 'woocommerce' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'current_pin' => array(
				'description'       => __( 'Current PIN. Required when updating your own PIN.', 'woocommerce' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
