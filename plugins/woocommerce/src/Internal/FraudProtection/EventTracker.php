<?php
/**
 * EventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates fraud protection event tracking and decision handling.
 *
 * This class coordinates data collection and transmission for fraud protection events.
 * It performs feature flag checks, whitelist handling, and applies extension data filters
 * before sending events to the fraud protection service.
 *
 * Extensions can trigger event tracking by calling:
 * ```php
 * do_action( 'woocommerce_fraud_protection_track_event', 'event_type', array( 'event_data' => 'value' ) );
 * ```
 *
 * The EventTracker implements a fail-safe pattern: if tracking fails for any reason,
 * it degrades gracefully by allowing the session to continue without blocking functionality.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class EventTracker implements RegisterHooksInterface {

	/**
	 * Fraud Protection Controller instance.
	 *
	 * @var FraudProtectionController
	 */
	private FraudProtectionController $controller;

	/**
	 * API Client instance.
	 *
	 * @var ApiClient
	 */
	private ApiClient $api_client;

	/**
	 * Session Clearance Manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private SessionClearanceManager $session_manager;

	/**
	 * Session Data Collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private SessionDataCollector $data_collector;

	/**
	 * Register hooks for fraud protection event tracking.
	 *
	 * @since 10.5.0
	 */
	public function register(): void {
		/**
		 * Hook: woocommerce_fraud_protection_track_event
		 *
		 * Allows extensions to trigger fraud protection event tracking.
		 *
		 * Usage:
		 * do_action( 'woocommerce_fraud_protection_track_event', $event_type, $event_data );
		 *
		 * @since 10.5.0
		 *
		 * @param string $event_type Event type identifier (e.g., 'cart_item_added', 'checkout_initiated').
		 * @param array  $event_data Optional event-specific data to include in tracking.
		 */
		add_action( 'woocommerce_fraud_protection_track_event', array( $this, 'on_track_event' ), 10, 2 );
	}

	/**
	 * Initialize the instance with dependencies.
	 *
	 * @internal
	 *
	 * @param FraudProtectionController $controller      The fraud protection controller instance.
	 * @param ApiClient                 $api_client      The API client instance.
	 * @param SessionClearanceManager   $session_manager The session clearance manager instance.
	 * @param SessionDataCollector      $data_collector  The session data collector instance.
	 */
	final public function init(
		FraudProtectionController $controller,
		ApiClient $api_client,
		SessionClearanceManager $session_manager,
		SessionDataCollector $data_collector
	): void {
		$this->controller      = $controller;
		$this->api_client      = $api_client;
		$this->session_manager = $session_manager;
		$this->data_collector  = $data_collector;
	}

	/**
	 * Handle fraud protection event tracking via hook.
	 *
	 * This is the hook callback that processes event tracking requests
	 * from extensions via do_action().
	 *
	 * @internal
	 *
	 * @since 10.5.0
	 *
	 * @param string $event_type Event type identifier.
	 * @param array  $event_data Optional event-specific data.
	 * @return void
	 */
	public function on_track_event( string $event_type, array $event_data = array() ): void {
		// Track the event and discard the verdict (extensions can check session status separately if needed).
		$this->track_event( $event_type, $event_data );
	}

	/**
	 * Track a fraud protection event and update session status based on verdict.
	 *
	 * This method orchestrates the complete event tracking flow:
	 * 1. Checks if tracking should proceed (feature flag, whitelist)
	 * 2. Collects comprehensive session data
	 * 3. Applies extension filters to allow customization
	 * 4. Sends event to fraud protection service
	 * 5. Updates session status based on verdict received
	 *
	 * Implements graceful degradation: any errors during tracking will be logged
	 * but will not break functionality.
	 *
	 * @since 10.5.0
	 *
	 * @param string $event_type Event type identifier (e.g., 'cart_item_added', 'checkout_initiated').
	 * @param array  $event_data Optional event-specific data to include in tracking.
	 * @return string Verdict received from fraud protection service ('allow', 'block', or 'challenge').
	 */
	public function track_event( string $event_type, array $event_data = array() ): string {
		try {
			// 1. Check feature flag - if disabled, allow session and skip tracking.
			if ( ! $this->controller->feature_is_enabled() ) {
				FraudProtectionController::log( 'debug', 'Fraud protection feature is disabled. Allowing session without tracking.' );
				return ApiClient::DECISION_ALLOW;
			}

			// 2. Check whitelist status - if session is already allowed, skip tracking.
			if ( $this->is_session_whitelisted() ) {
				$session_id = $this->session_manager->get_session_id();
				FraudProtectionController::log(
					'info',
					sprintf( 'Session is whitelisted (Session ID: %s). Allowing without re-checking.', $session_id )
				);
				return ApiClient::DECISION_ALLOW;
			}

			// 3. Collect comprehensive session data.
			$collected_data = $this->data_collector->collect( $event_type, $event_data );

			// 4. Apply extension filters to allow customization.
			$collected_data = $this->apply_extension_filters( $event_type, $collected_data );

			// 5. Flatten data structure for API compatibility (WPCOM expects flat structure).
			$flattened_data = $this->flatten_collected_data( $collected_data );

			// 6. Track event with fraud protection service.
			$verdict = $this->api_client->track_event( $event_type, $flattened_data );

			// 7. Update session status based on verdict.
			$this->update_session_status( $verdict );

			return $verdict;

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

			// On error, allow the session to continue.
			return ApiClient::DECISION_ALLOW;
		}
	}

	/**
	 * Check if the current session is whitelisted.
	 *
	 * A session is considered whitelisted if it has already been explicitly
	 * allowed by the fraud protection system. Whitelisted sessions bypass
	 * further fraud protection checks.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if session is whitelisted, false otherwise.
	 */
	private function is_session_whitelisted(): bool {
		// Session is whitelisted if it's already marked as allowed.
		return $this->session_manager->is_session_allowed();
	}

	/**
	 * Apply extension filters to collected event data.
	 *
	 * This allows payment gateways and other extensions to modify or enhance
	 * the event data before it's sent to the fraud protection service.
	 *
	 * Two filters are available:
	 * 1. Generic filter for all event types
	 * 2. Event-specific filter for targeted customization
	 *
	 * @since 10.5.0
	 *
	 * @param string $event_type     The event type being tracked.
	 * @param array  $collected_data The collected event data.
	 * @return array Modified event data after applying filters.
	 */
	private function apply_extension_filters( string $event_type, array $collected_data ): array {
		/**
		 * Filters fraud protection event data before transmission.
		 *
		 * Allows extensions to modify or enhance event data for fraud analysis.
		 * This is a generic filter that applies to all event types.
		 *
		 * @since 10.5.0
		 *
		 * @param array  $collected_data The collected event data.
		 * @param string $event_type     The event type being tracked.
		 * @param EventTracker $tracker  The EventTracker instance for additional context.
		 *
		 * @return array Modified event data.
		 */
		$collected_data = apply_filters(
			'woocommerce_fraud_protection_event_data',
			$collected_data,
			$event_type,
			$this
		);

		/**
		 * Filters fraud protection event data for a specific event type.
		 *
		 * This is a dynamic filter that includes the event type in the hook name,
		 * allowing targeted customization without checking the event type in the callback.
		 *
		 * Example: 'woocommerce_fraud_protection_event_data_cart_item_added'
		 *
		 * @since 10.5.0
		 *
		 * @param array        $collected_data The collected event data.
		 * @param EventTracker $tracker        The EventTracker instance for additional context.
		 *
		 * @return array Modified event data.
		 */
		$collected_data = apply_filters(
			"woocommerce_fraud_protection_event_data_{$event_type}",
			$collected_data,
			$this
		);

		return $collected_data;
	}

	/**
	 * Flatten nested collected data structure for API compatibility.
	 *
	 * The SessionDataCollector returns data in a nested structure, but the
	 * WPCOM fraud protection endpoint expects a flat structure. This method
	 * flattens the data while preserving all relevant fields.
	 *
	 * @since 10.5.0
	 *
	 * @param array $collected_data The nested collected data from SessionDataCollector.
	 * @return array Flattened data structure ready for API transmission.
	 */
	private function flatten_collected_data( array $collected_data ): array {
		$session  = $collected_data['session'] ?? array();
		$customer = $collected_data['customer'] ?? array();
		$order    = $collected_data['order'] ?? array();
		$payment  = $collected_data['payment'] ?? array();

		// Build flattened structure matching WPCOM endpoint schema.
		return array(
			// Session identification.
			'session_id'                => $session['session_id'] ?? null,
			'ip_address'                => $session['ip_address'] ?? null,
			'email'                     => $session['email'] ?? $customer['billing_email'] ?? null,
			'email_normalized'          => $this->normalize_email( $session['email'] ?? $customer['billing_email'] ?? null ),
			'ja3_hash'                  => $session['ja3_hash'] ?? null,
			'user_agent'                => $session['user_agent'] ?? null,

			// Customer data.
			'customer_id'               => $order['customer_id'] ?? 'guest',
			'billing_country'           => $collected_data['billing_address']['country'] ?? null,

			// Order data.
			'cart_hash'                 => $order['cart_hash'] ?? null,

			// Payment data.
			'payment_method_type'       => $payment['payment_method_type'] ?? null,
			'card_bin'                  => $payment['card_bin'] ?? null,
			'card_last4'                => $payment['card_last4'] ?? null,
			'card_brand'                => $payment['card_brand'] ?? null,
			'payer_id'                  => $payment['payer_id'] ?? null,
			'outcome'                   => $payment['outcome'] ?? null,
			'decline_reason'            => $payment['decline_reason'] ?? null,
			'avs_result'                => $payment['avs_result'] ?? null,
			'cvc_result'                => $payment['cvc_result'] ?? null,
			'tokenized_card_identifier' => $payment['tokenized_card_identifier'] ?? null,
		);
	}

	/**
	 * Normalize email address for fraud detection.
	 *
	 * Normalization rules:
	 * - Convert to lowercase
	 * - Remove +alias suffixes (e.g., user+test@example.com -> user@example.com)
	 * - Trim whitespace
	 *
	 * @since 10.5.0
	 *
	 * @param string|null $email The email address to normalize.
	 * @return string|null Normalized email or null if input is null.
	 */
	private function normalize_email( ?string $email ): ?string {
		if ( ! $email ) {
			return null;
		}

		// Convert to lowercase and trim.
		$email = strtolower( trim( $email ) );

		// Remove +alias suffix if present.
		if ( strpos( $email, '+' ) !== false ) {
			$parts = explode( '@', $email, 2 );
			if ( count( $parts ) === 2 ) {
				$local_part = explode( '+', $parts[0], 2 )[0];
				$email      = $local_part . '@' . $parts[1];
			}
		}

		return $email;
	}

	/**
	 * Update session status based on fraud protection verdict.
	 *
	 * Maps API verdicts to session clearance states:
	 * - 'allow'     -> Session allowed
	 * - 'challenge' -> Session pending (challenge required)
	 * - 'block'     -> Session blocked
	 *
	 * @since 10.5.0
	 *
	 * @param string $verdict The verdict received from fraud protection service.
	 * @return void
	 */
	private function update_session_status( string $verdict ): void {
		switch ( $verdict ) {
			case ApiClient::DECISION_ALLOW:
				$this->session_manager->allow_session();
				break;
			case ApiClient::DECISION_CHALLENGE:
				$this->session_manager->challenge_session();
				break;
			case ApiClient::DECISION_BLOCK:
				$this->session_manager->block_session();
				break;
			default:
				// Unknown verdict - log and allow session (fail-safe).
				FraudProtectionController::log(
					'warning',
					sprintf( 'Unknown fraud protection verdict: %s. Allowing session.', $verdict )
				);
				$this->session_manager->allow_session();
				break;
		}
	}
}
