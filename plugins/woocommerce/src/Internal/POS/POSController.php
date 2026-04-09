<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Service\POSSessionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Application_Passwords;
use WP_Error;
use WP_User;

/**
 * Orchestrates POS services and registers the Action Scheduler cleanup job.
 *
 * @internal
 * @since 10.8.0
 */
class POSController implements RegisterHooksInterface {

	const CLEANUP_ACTION_HOOK = 'woocommerce_pos_cleanup_stale_sessions';
	const CLEANUP_GROUP       = 'woocommerce-pos';

	/**
	 * @var POSSessionService
	 */
	private POSSessionService $session_service;

	/**
	 * @var WP_Error|null
	 */
	private ?WP_Error $session_auth_error = null;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 * @since 10.8.0
	 * @param POSSessionService $session_service Session service instance.
	 */
	final public function init( POSSessionService $session_service ): void {
		$this->session_service = $session_service;
	}

	/**
	 * Register hooks and filters.
	 *
	 * @since 10.8.0
	 */
	public function register(): void {
		add_action( self::CLEANUP_ACTION_HOOK, array( $this, 'handle_cleanup' ) );
		add_action( 'init', array( $this, 'maybe_schedule_cleanup' ) );
		add_action( 'application_password_did_authenticate', array( $this, 'validate_pos_session' ), 10, 2 );
		add_filter( 'rest_authentication_errors', array( $this, 'enforce_pos_session_error' ), 99 );
	}

	/**
	 * Schedule the daily cleanup if not already scheduled.
	 *
	 * @since 10.8.0
	 */
	public function maybe_schedule_cleanup(): void {
		if ( as_has_scheduled_action( self::CLEANUP_ACTION_HOOK, null, self::CLEANUP_GROUP ) ) {
			return;
		}

		$midnight_tonight = strtotime( 'tomorrow midnight' );
		if ( false !== $midnight_tonight ) {
			as_schedule_recurring_action(
				$midnight_tonight,
				DAY_IN_SECONDS,
				self::CLEANUP_ACTION_HOOK,
				array(),
				self::CLEANUP_GROUP
			);
		}
	}

	/**
	 * Handle the cleanup action by delegating to the session service.
	 *
	 * @since 10.8.0
	 */
	public function handle_cleanup(): void {
		$this->session_service->cleanup_stale_sessions();
	}

	/**
	 * Validate POS session when a POS Application Password authenticates.
	 *
	 * Hooks into application_password_did_authenticate. If the Application Password
	 * name starts with "WooCommerce POS", checks session validity. If expired or idle,
	 * revokes the Application Password and stores an error for rest_authentication_errors.
	 * If valid, touches the session to update last_active.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_User $user    The authenticated user.
	 * @param array   $app_password The Application Password record.
	 */
	public function validate_pos_session( WP_User $user, array $app_password ): void {
		if ( ! str_starts_with( $app_password['name'], POSSessionService::APP_PASSWORD_PREFIX ) ) {
			return;
		}

		if ( ! $this->session_service->is_session_valid( $user->ID ) ) {
			WP_Application_Passwords::delete_application_password( $user->ID, $app_password['uuid'] );

			$this->session_auth_error = new WP_Error(
				'woocommerce_pos_session_expired',
				__( 'Your POS session has expired. Please log in again.', 'woocommerce' ),
				array( 'status' => 401 )
			);
			return;
		}

		$this->session_service->touch_session( $user->ID );
	}

	/**
	 * Return the stored session auth error via the rest_authentication_errors filter.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_Error|null|true $error Existing authentication error.
	 * @return WP_Error|null|true
	 */
	public function enforce_pos_session_error( $error ) {
		if ( null !== $this->session_auth_error ) {
			return $this->session_auth_error;
		}

		return $error;
	}
}
