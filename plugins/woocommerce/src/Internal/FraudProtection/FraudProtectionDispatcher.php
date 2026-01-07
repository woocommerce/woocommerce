<?php
/**
 * FraudProtectionDispatcher class file.
 *
 * @package WooCommerce\Internal
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
	 * Fraud protection controller instance.
	 *
	 * @var FraudProtectionController
	 */
	private FraudProtectionController $fraud_protection_controller;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param ApiClient                 $api_client                  The API client instance.
	 * @param DecisionHandler           $decision_handler            The decision handler instance.
	 * @param FraudProtectionController $fraud_protection_controller The fraud protection controller instance.
	 */
	final public function init(
		ApiClient $api_client,
		DecisionHandler $decision_handler,
		FraudProtectionController $fraud_protection_controller
	): void {
		$this->api_client                  = $api_client;
		$this->decision_handler            = $decision_handler;
		$this->fraud_protection_controller = $fraud_protection_controller;
	}

	/**
	 * Dispatch fraud protection event with already-collected data.
	 *
	 * This method accepts fully-collected event data (including session context)
	 * and dispatches it to the fraud protection service. It orchestrates the
	 * following flow:
	 * 1. Check if feature is enabled (fail-open if not)
	 * 2. Apply extension data filter to allow custom data
	 * 3. Send event to API and get decision
	 * 4. Apply decision via DecisionHandler
	 *
	 * The method implements graceful degradation - any errors during tracking
	 * will be logged but will not break the functionality.
	 *
	 * @param string $event_type     Event type identifier (e.g., 'cart_item_added').
	 * @param array  $collected_data Fully-collected event data including session context.
	 * @return void
	 */
	public function dispatch_event( string $event_type, array $collected_data ): void {
		try {
			// Check if feature is enabled - fail-open if not.
			if ( ! $this->fraud_protection_controller->feature_is_enabled() ) {
				FraudProtectionController::log(
					'debug',
					sprintf(
						'Fraud protection event not dispatched (feature disabled): %s',
						$event_type
					),
					array( 'event_type' => $event_type )
				);
				return;
			}

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

			// Extract session data for API call.
			$session_data = $collected_data['session'] ?? array();

			// Send event to API and get decision.
			$decision = $this->api_client->send_event( $event_type, $collected_data );

			// Apply decision via DecisionHandler.
			$this->decision_handler->apply_decision( $decision, $session_data );
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
}
