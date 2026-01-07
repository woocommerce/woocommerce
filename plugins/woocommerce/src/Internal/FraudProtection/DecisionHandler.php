<?php
/**
 * DecisionHandler class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Handles fraud protection decision application.
 *
 * This class is responsible for:
 * - Applying extension override filters for whitelisting
 * - Coordinating with SessionClearanceManager to apply decisions
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class DecisionHandler {

	/**
	 * Session clearance manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private SessionClearanceManager $session_manager;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param SessionClearanceManager $session_manager The session clearance manager instance.
	 */
	final public function init( SessionClearanceManager $session_manager ): void {
		$this->session_manager = $session_manager;
	}

	/**
	 * Apply a fraud protection decision.
	 *
	 * This method processes a decision from the API, applies any override filters,
	 * validates the result, and updates the session status accordingly.
	 *
	 * The input decision is expected to be pre-validated by ApiClient.
	 *
	 * The decision flow:
	 * 1. Apply the `woocommerce_fraud_protection_decision` filter for overrides
	 * 2. Validate the filtered decision (third-party filters may return invalid values)
	 * 3. Update session status via SessionClearanceManager
	 *
	 * @since 10.5.0
	 *
	 * @param string               $decision     The decision from the API (allow, block).
	 * @param array<string, mixed> $session_data The session data that was sent to the API.
	 * @return string The final applied decision after any filter overrides.
	 */
	public function apply_decision( string $decision, array $session_data ): string {
		// Validate input decision and fail open if invalid.
		if ( ! $this->is_valid_decision( $decision ) ) {
			FraudProtectionController::log(
				'warning',
				sprintf( 'Invalid decision "%s" received. Defaulting to "allow".', $decision ),
				array( 'session_data' => $session_data )
			);
			$decision = ApiClient::DECISION_ALLOW;
		}

		$original_decision = $decision;

		/**
		 * Filters the fraud protection decision before it is applied.
		 *
		 * This filter allows extensions to override fraud protection decisions
		 * to implement custom whitelisting logic. Common use cases:
		 * - Whitelist specific users (e.g., admins, trusted customers)
		 * - Whitelist specific conditions (e.g., certain IP ranges, logged-in users)
		 * - Integrate with external fraud detection services
		 *
		 * Note: This filter can only change the decision to ApiClient::VALID_DECISIONS.
		 * Any other value will be rejected and the original decision will be used.
		 *
		 * @since 10.5.0
		 *
		 * @param string               $decision     The decision from the API (allow, block).
		 * @param array<string, mixed> $session_data The session data that was analyzed.
		 */
		$decision = apply_filters( 'woocommerce_fraud_protection_decision', $decision, $session_data );

		// Validate filtered decision (third-party filters may return invalid values).
		if ( ! $this->is_valid_decision( $decision ) ) {
			FraudProtectionController::log(
				'warning',
				sprintf( 'Filter `woocommerce_fraud_protection_decision` returned invalid decision "%s". Using original decision "%s".', $decision, $original_decision ),
				array(
					'original_decision' => $original_decision,
					'filtered_decision' => $decision,
					'session_data'      => $session_data,
				)
			);
			$decision = $original_decision;
		}

		// Log if decision was overridden.
		if ( $decision !== $original_decision ) {
			FraudProtectionController::log(
				'info',
				sprintf( 'Decision overridden by filter `woocommerce_fraud_protection_decision`: "%s" -> "%s"', $original_decision, $decision ),
				array(
					'original_decision' => $original_decision,
					'final_decision'    => $decision,
					'session_data'      => $session_data,
				)
			);
		}

		// Apply the decision to the session.
		$this->update_session_status( $decision );

		return $decision;
	}

	/**
	 * Check if a decision value is valid.
	 *
	 * @param mixed $decision The decision to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private function is_valid_decision( $decision ): bool {
		if ( ! is_string( $decision ) ) {
			return false;
		}
		return in_array( $decision, ApiClient::VALID_DECISIONS, true );
	}

	/**
	 * Update the session status based on the decision.
	 *
	 * @param string $decision The validated decision to apply.
	 * @return void
	 */
	private function update_session_status( string $decision ): void {
		switch ( $decision ) {
			case ApiClient::DECISION_ALLOW:
				$this->session_manager->allow_session();
				break;

			case ApiClient::DECISION_BLOCK:
				$this->session_manager->block_session();
				break;
		}
	}
}
