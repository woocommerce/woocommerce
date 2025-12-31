<?php
/**
 * FraudProtectionTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized fraud protection event tracker.
 *
 * This class provides a unified interface for tracking fraud protection events.
 * It logs events for the fraud protection service using already-collected data.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class FraudProtectionTracker {

	/**
	 * Track fraud protection event with already-collected data.
	 *
	 * This method accepts fully-collected event data (including session context)
	 * and logs it for the fraud protection service.
	 *
	 * The method implements graceful degradation - any errors during tracking
	 * will be logged but will not break the functionality.
	 *
	 * @param string $event_type     Event type identifier (e.g., 'cart_item_added').
	 * @param array  $collected_data Fully-collected event data including session context.
	 * @return void
	 */
	public function track_event( string $event_type, array $collected_data ): void {
		try {
			// phpcs:ignore Generic.Commenting.Todo.TaskFound
			// TODO: Once EventTracker/API client is implemented (WOOSUBS-1249), call it here:
			// $event_tracker = wc_get_container()->get( EventTracker::class );
			// $event_tracker->track( $event_type, $collected_data );
			//
			// For now, log the event for debugging and verification.
			FraudProtectionController::log(
				'info',
				sprintf(
					'Fraud protection event tracked: %s | Session ID: %s',
					$event_type,
					$collected_data['session']['session_id'] ?? 'N/A'
				),
				array(
					'event_type'     => $event_type,
					'collected_data' => $collected_data,
				)
			);
		} catch ( \Exception $e ) {
			// Gracefully handle errors - fraud protection should never break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to track fraud protection event: %s | Error: %s',
					$event_type,
					$e->getMessage()
				),
				array(
					'event_type' => $event_type,
					'exception'  => $e,
				)
			);
		}
	}
}
