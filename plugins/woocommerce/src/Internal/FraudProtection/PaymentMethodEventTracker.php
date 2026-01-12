<?php
/**
 * PaymentMethodEventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks payment method events for fraud protection analysis.
 *
 * This class provides methods to track events for adding payment methods in My Account page
 * for fraud protection.
 * Event-specific data is passed to the dispatcher which handles session data collection internally.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class PaymentMethodEventTracker {

	/**
	 * Fraud protection dispatcher instance.
	 *
	 * @var FraudProtectionDispatcher
	 */
	private FraudProtectionDispatcher $dispatcher;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param FraudProtectionDispatcher $dispatcher The fraud protection dispatcher instance.
	 */
	final public function init( FraudProtectionDispatcher $dispatcher ): void {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Track add payment method page loaded event.
	 *
	 * Triggers fraud protection event dispatching when the add payment method page is initially loaded.
	 * This captures the initial session state before any user interactions.
	 *
	 * @internal
	 * @return void
	 */
	public function track_add_payment_method_page_loaded(): void {
		// Track the page load event. Session data will be collected by the dispatcher.
		$this->dispatcher->dispatch_event( 'add_payment_method_page_loaded', array() );
	}

	/**
	 * Track payment method added event.
	 *
	 * Triggers fraud protection event tracking when a payment method is added.
	 *
	 * @internal
	 *
	 * @param int               $token_id The newly created token ID.
	 * @param \WC_Payment_Token $token    The payment token object.
	 */
	public function track_payment_method_added( $token_id, $token ): void {
		$event_data = $this->build_payment_method_event_data( 'added', $token );

		// Trigger event dispatching.
		$this->dispatcher->dispatch_event( 'payment_method_added', $event_data );
	}

	/**
	 * Build payment method event-specific data.
	 *
	 * Extracts relevant information from the payment token object including
	 * token type, gateway ID, user ID, and card details for card tokens.
	 * This data will be merged with comprehensive session data during event tracking.
	 *
	 * @param string            $action Action type (added, updated, set_default, deleted, add_failed).
	 * @param \WC_Payment_Token $token  The payment token object.
	 * @return array Payment method event data.
	 */
	private function build_payment_method_event_data( string $action, \WC_Payment_Token $token ): array {
		$event_data = array(
			'action'     => $action,
			'token_id'   => $token->get_id(),
			'token_type' => $token->get_type(),
			'gateway_id' => $token->get_gateway_id(),
			'user_id'    => $token->get_user_id(),
			'is_default' => $token->is_default(),
		);

		// Add card-specific details if this is a credit card token.
		if ( $token instanceof \WC_Payment_Token_CC ) {
			$event_data['card_type']    = $token->get_card_type();
			$event_data['card_last4']   = $token->get_last4();
			$event_data['expiry_month'] = $token->get_expiry_month();
			$event_data['expiry_year']  = $token->get_expiry_year();
		}

		return $event_data;
	}
}
