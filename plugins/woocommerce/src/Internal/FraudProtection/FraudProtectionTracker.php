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
 * It orchestrates the event tracking by collecting comprehensive session data
 * via SessionDataCollector and logging events for the fraud protection service.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class FraudProtectionTracker {

	/**
	 * Session data collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private SessionDataCollector $data_collector;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param SessionDataCollector $data_collector The session data collector instance.
	 */
	final public function init( SessionDataCollector $data_collector ): void {
		$this->data_collector = $data_collector;
	}

	/**
	 * Track fraud protection event with comprehensive session context.
	 *
	 * This method orchestrates the event tracking by:
	 * 1. Collecting comprehensive session data via SessionDataCollector
	 * 2. Merging with event-specific data
	 * 3. Logging the event (will call EventTracker/API client once available)
	 *
	 * The method implements graceful degradation - any errors during tracking
	 * will be logged but will not break the functionality.
	 *
	 * @param string $event_type          Event type identifier (e.g., 'cart_item_added').
	 * @param array  $event_specific_data Event-specific data to merge with session context.
	 * @return void
	 */
	public function track_event( string $event_type, array $event_specific_data ): void {
		try {
			// Collect comprehensive session data.
			$session_data = $this->data_collector->collect( $event_type, $event_specific_data );

			// phpcs:ignore Generic.Commenting.Todo.TaskFound
			// TODO: Once EventTracker/API client is implemented (WOOSUBS-1249), call it here:
			// $event_tracker = wc_get_container()->get( EventTracker::class );
			// $event_tracker->track( $event_type, $session_data );
			//
			// For now, log the event for debugging and verification.
			FraudProtectionController::log(
				'info',
				sprintf(
					'Fraud protection event tracked: %s | Session ID: %s',
					$event_type,
					$session_data['session']['session_id'] ?? 'N/A'
				),
				array(
					'event_type'   => $event_type,
					'event_data'   => $event_specific_data,
					'session_data' => $session_data,
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
