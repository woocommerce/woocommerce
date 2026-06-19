<?php
/**
 * WooPaymentsAccountSessionRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments embedded account-session REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAccountSessionRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Embedded account session service.
	 *
	 * @var WooPaymentsEmbeddedAccountSessionService
	 */
	private WooPaymentsEmbeddedAccountSessionService $session_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter             $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsEmbeddedAccountSessionService $session_service Embedded account session service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsEmbeddedAccountSessionService $session_service ): void {
		$this->arbiter         = $arbiter;
		$this->session_service = $session_service;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register WooPayments-compatible account-session routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/accounts/session', $this->get_readable_route( 'create_embedded_account_session' ) );
	}

	/**
	 * Check route permissions.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Create an embedded account session.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_embedded_account_session( WP_REST_Request $request ) {
		unset( $request );

		try {
			return new WP_REST_Response( $this->session_service->create_session() );
		} catch ( Throwable $exception ) {
			unset( $exception );

			return new WP_Error(
				'woocommerce_woopayments_account_session_error',
				__( 'Unable to create the WooPayments account session.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get a readable route definition.
	 *
	 * @param string $callback Callback method.
	 * @return array<string,mixed>
	 */
	private function get_readable_route( string $callback ): array {
		return array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, $callback ),
			'permission_callback' => array( $this, 'check_permission' ),
		);
	}
}
