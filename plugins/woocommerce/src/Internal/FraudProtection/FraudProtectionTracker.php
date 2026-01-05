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
	 * and triggers the fraud protection event tracking via the WordPress action hook.
	 *
	 * The EventTracker listens to this action and handles the complete tracking flow
	 * including feature flag checks, whitelist handling, data transmission, and
	 * session status updates.
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
			/**
			 * Hook: woocommerce_fraud_protection_track_event
			 *
			 * Triggers fraud protection event tracking. The EventTracker class listens
			 * to this action and orchestrates the complete tracking flow.
			 *
			 * @since 10.5.0
			 *
			 * @param string $event_type     Event type identifier.
			 * @param array  $collected_data Fully-collected event data including session context.
			 */
			do_action( 'woocommerce_fraud_protection_track_event', $event_type, $collected_data );

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
