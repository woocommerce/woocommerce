<?php
/**
 * FraudProtectionDispatcher class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized fraud protection event dispatcher.
 *
 * This class provides a unified interface for dispatching fraud protection events.
 * It coordinates data collection and transmission for fraud protection events by
 * orchestrating ApiClient and DecisionHandler components.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class FraudProtectionDispatcher {

	/**
	 * API client instance.
	 *
	 * @var ApiClient
	 */
	private ApiClient $api_client;

	/**
	 * Decision handler instance.
	 *
	 * @var DecisionHandler
	 */
	private DecisionHandler $decision_handler;

	/**
	 * Session data collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private SessionDataCollector $data_collector;

	/**
	 * Session clearance manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private SessionClearanceManager $session_manager;

	/**
	 * Initialize with dependencies.
	 *
	 * Note: FraudProtectionController is NOT injected here to avoid circular dependency.
	 * It is fetched lazily in dispatch_event() via get_fraud_protection_controller().
	 *
	 * @internal
	 *
	 * @param ApiClient               $api_client       The API client instance.
	 * @param DecisionHandler         $decision_handler The decision handler instance.
	 * @param SessionDataCollector    $data_collector   The session data collector instance.
	 * @param SessionClearanceManager $session_manager  The session clearance manager instance.
	 */
	final public function init(
		ApiClient $api_client,
		DecisionHandler $decision_handler,
		SessionDataCollector $data_collector,
		SessionClearanceManager $session_manager
	): void {
		$this->api_client       = $api_client;
		$this->decision_handler = $decision_handler;
		$this->data_collector   = $data_collector;
		$this->session_manager  = $session_manager;
	}

	/**
	 * Dispatch fraud protection event.
	 *
	 * This method collects session data and either queues it for later or sends it
	 * to the fraud protection service immediately, depending on the event type.
	 *
	 * Event flow:
	 * - Non-checkout events: Collected and queued in session (no API call)
	 * - Checkout events: Collected, combined with queued events, sent to API
	 *
	 * The method implements graceful degradation - any errors during tracking
	 * will be logged but will not break the functionality.
	 *
	 * Note: This method assumes the fraud protection feature is already enabled.
	 * The feature check is done by FraudProtectionController before registering
	 * event trackers that call this dispatcher.
	 *
	 * @param string $event_type Event type identifier (e.g., 'cart_item_added', 'checkout').
	 * @param array  $event_data Optional event-specific data to include with session data.
	 * @return void
	 */
	public function dispatch_event( string $event_type, array $event_data = array() ): void {
		try {
			// Collect comprehensive session data.
			$collected_data = $this->data_collector->collect( $event_type, $event_data );

			/**
			 * Filters the fraud protection event data before sending to the API.
			 *
			 * This filter allows extensions to modify or add custom data to fraud protection
			 * events. Common use cases include:
			 * - Adding custom payment gateway data
			 * - Adding subscription-specific context
			 * - Adding custom risk signals
			 *
			 * @since 10.5.0
			 *
			 * @param array  $collected_data Fully-collected event data including session context.
			 * @param string $event_type     Event type identifier (e.g., 'cart_item_added').
			 */
			$collected_data = apply_filters( 'woocommerce_fraud_protection_event_data', $collected_data, $event_type );

			// For checkout events: send all queued events + this one to the API.
			if ( 'checkout' === $event_type ) {
				$this->send_checkout_with_events( $collected_data );
				return;
			}

			// For other events: queue in session for later sending with checkout.
			$this->session_manager->queue_event( $event_type, $collected_data );

			FraudProtectionController::log(
				'debug',
				sprintf( 'Event queued in session: %s', $event_type ),
				array( 'event_type' => $event_type )
			);
		} catch ( \Exception $e ) {
			// Gracefully handle errors - fraud protection should never break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to dispatch fraud protection event: %s | Error: %s',
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

	/**
	 * Send checkout event with all previously queued events.
	 *
	 * This method retrieves all queued events from the session, sends them
	 * along with the checkout event to the API, and clears the queue.
	 *
	 * @param array $checkout_data The collected checkout event data.
	 * @return void
	 */
	private function send_checkout_with_events( array $checkout_data ): void {
		// Get all queued events from the session.
		$prior_events = $this->session_manager->get_event_queue();

		FraudProtectionController::log(
			'debug',
			sprintf( 'Sending checkout event with %d prior events', count( $prior_events ) ),
			array( 'prior_events_count' => count( $prior_events ) )
		);

		// Send event to API with prior events and get decision.
		$decision = $this->api_client->send_event( 'checkout', $checkout_data, $prior_events );

		// Clear the event queue after successful send.
		$this->session_manager->clear_event_queue();

		// Apply decision via DecisionHandler.
		$this->decision_handler->apply_decision( $decision, $checkout_data );
	}
}
