<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\POS\Service\POSRateLimitService;
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

	use POSRequestTrait;

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @var POSSessionService
	 */
	private POSSessionService $session_service;

	/**
	 * @var POSRateLimitService
	 */
	private POSRateLimitService $rate_limit_service;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @param POSPinService       $pin_service        PIN service.
	 * @param POSSessionService   $session_service    Session service.
	 * @param POSRateLimitService $rate_limit_service Rate limit service.
	 *
	 * @internal
	 * @since 10.8.0
	 */
	final public function init(
		POSPinService $pin_service,
		POSSessionService $session_service,
		POSRateLimitService $rate_limit_service
	): void {
		$this->pin_service        = $pin_service;
		$this->session_service    = $session_service;
		$this->rate_limit_service = $rate_limit_service;
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

		register_rest_route(
			$this->route_namespace,
			'/pos/auth/pin/verify',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'verify_pin' ),
					'permission_callback' => fn( $request ) => $this->check_permission(
						$request,
						'woocommerce_pos_access'
					),
					'args'                => array(
						'pin' => array(
							'required' => true,
							'type'     => 'string',
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
		$start_time = microtime( true );

		try {
			return $this->do_validate_pin( $request );
		} finally {
			$this->pad_response_time( $start_time );
		}
	}

	/**
	 * Internal PIN validation logic.
	 *
	 * @since 10.8.0
	 * @param WP_REST_Request $request The incoming request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	private function do_validate_pin( WP_REST_Request $request ) {
		$client_ip   = $this->get_client_ip();
		$rate_check  = $this->rate_limit_service->check_rate_limit( $client_ip );

		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		$pin         = $request->get_param( 'pin' );
		$register_id = $request->get_param( 'register_id' );
		$logger      = wc_get_logger();
		$log_context = array( 'source' => 'woocommerce-pos' );

		if ( ! $this->pin_service->validate_pin_format( $pin ) ) {
			$logger->warning( 'PIN authentication failed: invalid PIN format.', $log_context );
			$this->rate_limit_service->record_failure( $client_ip );
			return $this->pin_error();
		}

		$user_id = $this->pin_service->lookup_user_by_pin( $pin );

		if ( ! $user_id ) {
			$logger->warning( 'PIN authentication failed: no user found for provided PIN.', $log_context );
			$this->rate_limit_service->record_failure( $client_ip );
			return $this->pin_error();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! $user->has_cap( 'woocommerce_pos_access' ) ) {
			$logger->warning(
				sprintf( 'PIN authentication failed: user %d lacks woocommerce_pos_access.', $user_id ),
				$log_context
			);
			$this->rate_limit_service->record_failure( $client_ip );
			return $this->pin_error();
		}

		if ( ! wp_is_application_passwords_available() ) {
			throw new \Exception(
				esc_html__(
					'Application Passwords are not available on this site. POS user switching requires Application Passwords to be enabled.',
					'woocommerce'
				),
				501
			);
		}

		$session = $this->session_service->create_session( $user_id, $register_id );

		if ( is_wp_error( $session ) ) {
			throw new \Exception( $session->get_error_message() );
		}
		/** @var array{password: string, uuid: string, expires: int} $session */

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
	 * Verify PIN and return user capabilities without creating a session.
	 *
	 * @since 10.8.0
	 * @param WP_REST_Request $request The incoming request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	protected function verify_pin( WP_REST_Request $request ) {
		$start_time = microtime( true );

		try {
			return $this->do_verify_pin( $request );
		} finally {
			$this->pad_response_time( $start_time );
		}
	}

	/**
	 * Internal PIN verification logic.
	 *
	 * Returns user identity and capabilities without creating an application
	 * password session. Used for lightweight capability checks such as
	 * verifying whether a user has permission to access POS settings.
	 *
	 * @since 10.8.0
	 * @param WP_REST_Request $request The incoming request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	private function do_verify_pin( WP_REST_Request $request ) {
		$client_ip  = $this->get_client_ip();
		$rate_check = $this->rate_limit_service->check_rate_limit( $client_ip );

		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		$pin         = $request->get_param( 'pin' );
		$logger      = wc_get_logger();
		$log_context = array( 'source' => 'woocommerce-pos' );

		if ( ! $this->pin_service->validate_pin_format( $pin ) ) {
			$logger->warning( 'PIN verification failed: invalid PIN format.', $log_context );
			$this->rate_limit_service->record_failure( $client_ip );
			return $this->pin_error();
		}

		$user_id = $this->pin_service->lookup_user_by_pin( $pin );

		if ( ! $user_id ) {
			$logger->warning( 'PIN verification failed: no user found for provided PIN.', $log_context );
			$this->rate_limit_service->record_failure( $client_ip );
			return $this->pin_error();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! $user->has_cap( 'woocommerce_pos_access' ) ) {
			$logger->warning(
				sprintf( 'PIN verification failed: user %d lacks woocommerce_pos_access.', $user_id ),
				$log_context
			);
			$this->rate_limit_service->record_failure( $client_ip );
			return $this->pin_error();
		}

		$all_caps = $user->allcaps;
		$woo_caps = array();
		foreach ( $all_caps as $cap => $granted ) {
			if ( $granted && ( str_starts_with( $cap, 'woocommerce_' ) || 'manage_woocommerce' === $cap ) ) {
				$woo_caps[ $cap ] = true;
			}
		}

		$logger->info(
			sprintf( 'PIN verification succeeded for user %d.', $user_id ),
			$log_context
		);

		return array(
			'user_id'      => $user_id,
			'user_login'   => $user->user_login,
			'display_name' => $user->display_name,
			'role'         => reset( $user->roles ),
			'capabilities' => $woo_caps,
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
