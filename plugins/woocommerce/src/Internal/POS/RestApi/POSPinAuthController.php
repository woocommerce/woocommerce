<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\POS\Service\POSSessionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST API controller for POS PIN-based authentication.
 *
 * @since 10.8.0
 * @internal
 */
class POSPinAuthController extends RestApiControllerBase implements RegisterHooksInterface {

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @var POSSessionService
	 */
	private POSSessionService $session_service;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @param POSPinService     $pin_service     PIN service.
	 * @param POSSessionService $session_service Session service.
	 *
	 * @internal
	 * @since 10.8.0
	 */
	final public function init( POSPinService $pin_service, POSSessionService $session_service ): void {
		$this->pin_service     = $pin_service;
		$this->session_service = $session_service;
	}

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @since 10.8.0
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'pos-auth';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @since 10.8.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/pos/auth/pin',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'validate_pin' ),
					'permission_callback' => fn( $request ) => $this->check_permission(
						$request,
						'woocommerce_pos_access'
					),
					'args'                => array(
						'pin'         => array(
							'required' => true,
							'type'     => 'string',
						),
						'register_id' => array(
							'type'    => 'string',
							'default' => 'default',
						),
					),
				),
			)
		);
	}

	/**
	 * Validate PIN and create a session.
	 *
	 * @since 10.8.0
	 * @param WP_REST_Request $request The incoming request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	protected function validate_pin( WP_REST_Request $request ) {
		$pin         = $request->get_param( 'pin' );
		$register_id = $request->get_param( 'register_id' );
		$logger      = wc_get_logger();
		$log_context = array( 'source' => 'woocommerce-pos' );

		if ( ! $this->pin_service->validate_pin_format( $pin ) || $this->pin_service->is_pin_blocked( $pin ) ) {
			$logger->warning( 'PIN authentication failed: invalid format or blocked PIN.', $log_context );
			return $this->pin_error();
		}

		$user_id = $this->pin_service->lookup_user_by_pin( $pin );

		if ( ! $user_id ) {
			$logger->warning( 'PIN authentication failed: no user found for provided PIN.', $log_context );
			return $this->pin_error();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! $user->has_cap( 'woocommerce_pos_access' ) ) {
			$logger->warning(
				sprintf( 'PIN authentication failed: user %d lacks woocommerce_pos_access.', $user_id ),
				$log_context
			);
			return $this->pin_error();
		}

		$session = $this->session_service->create_session( $user_id, $register_id );

		if ( is_wp_error( $session ) ) {
			throw new \Exception( $session->get_error_message() );
		}

		$all_caps = $user->allcaps;
		$woo_caps = array();
		foreach ( $all_caps as $cap => $granted ) {
			if ( $granted && str_starts_with( $cap, 'woocommerce_' ) ) {
				$woo_caps[ $cap ] = true;
			}
		}

		$idle_timeout = (int) apply_filters(
			'woocommerce_pos_idle_timeout',
			POSSessionService::DEFAULT_IDLE_TIMEOUT
		);

		$logger->info(
			sprintf( 'PIN authentication succeeded for user %d on register %s.', $user_id, $register_id ),
			$log_context
		);

		return array(
			'user_id'                   => $user_id,
			'user_login'                => $user->user_login,
			'display_name'              => $user->display_name,
			'role'                      => reset( $user->roles ),
			'capabilities'              => $woo_caps,
			'application_password'      => $session['password'],
			'application_password_uuid' => $session['uuid'],
			'session_expires'           => gmdate( 'c', $session['expires'] ),
			'idle_timeout_seconds'      => $idle_timeout,
		);
	}

	/**
	 * Returns a generic WP_Error for all PIN authentication failures.
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
