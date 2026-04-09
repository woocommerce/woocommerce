<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Service\POSSessionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

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
}
