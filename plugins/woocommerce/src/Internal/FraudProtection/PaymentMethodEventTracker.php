<?php
/**
 * PaymentMethodEventTracker class file.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks payment method events for fraud protection analysis.
 *
 * This class hooks into WooCommerce payment method events in My Account
 * (add, update, set default, delete, add failed) and triggers comprehensive event
 * tracking with full session context. It orchestrates the event tracking by collecting
 * session data and preparing it for the fraud protection service.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class PaymentMethodEventTracker implements RegisterHooksInterface {

	/**
	 * Fraud protection dispatcher instance.
	 *
	 * @var FraudProtectionDispatcher
	 */
	private FraudProtectionDispatcher $dispatcher;

	/**
	 * Session data collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private SessionDataCollector $data_collector;

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
	 * @param FraudProtectionDispatcher $dispatcher                     The fraud protection dispatcher instance.
	 * @param SessionDataCollector      $data_collector              The session data collector instance.
	 * @param FraudProtectionController $fraud_protection_controller The fraud protection controller instance.
	 */
	final public function init(
		FraudProtectionDispatcher $dispatcher,
		SessionDataCollector $data_collector,
		FraudProtectionController $fraud_protection_controller
	): void {
		$this->dispatcher                  = $dispatcher;
		$this->data_collector              = $data_collector;
		$this->fraud_protection_controller = $fraud_protection_controller;
	}

	/**
	 * Register payment method event hooks.
	 *
	 * Hooks into WooCommerce payment token actions to track fraud protection events.
	 * Only registers hooks if the fraud protection feature is enabled.
	 */
	public function register(): void {
		// Only register hooks if fraud protection is enabled.
		if ( ! $this->fraud_protection_controller->feature_is_enabled() ) {
			return;
		}

		add_action( 'woocommerce_new_payment_token', array( $this, 'handle_payment_method_added' ), 10, 2 );
		add_action( 'woocommerce_payment_token_updated', array( $this, 'handle_payment_method_updated' ), 10, 1 );
		add_action( 'woocommerce_payment_token_set_default', array( $this, 'handle_payment_method_set_default' ), 10, 2 );
		add_action( 'woocommerce_payment_token_deleted', array( $this, 'handle_payment_method_deleted' ), 10, 2 );
	}

	/**
	 * Handle payment method added event.
	 *
	 * Triggers fraud protection event tracking when a payment method is added.
	 *
	 * @internal
	 *
	 * @param int               $token_id The newly created token ID.
	 * @param \WC_Payment_Token $token    The payment token object.
	 */
	public function handle_payment_method_added( $token_id, $token ): void {
		$event_data = $this->build_payment_method_event_data( 'added', $token );

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'payment_method_added', $event_data );
			$this->dispatcher->dispatch_event( 'payment_method_added', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for payment method event: %s | Error: %s',
					'payment_method_added',
					$e->getMessage()
				),
				array(
					'event_type' => 'payment_method_added',
					'exception'  => $e,
				)
			);
		}
	}

	/**
	 * Handle payment method updated event.
	 *
	 * Triggers fraud protection event tracking when a payment method is updated.
	 *
	 * @internal
	 *
	 * @param int $token_id The ID of the updated token.
	 */
	public function handle_payment_method_updated( $token_id ): void {
		// Get the token object to extract details.
		$token = \WC_Payment_Tokens::get( $token_id );

		if ( ! $token instanceof \WC_Payment_Token ) {
			return;
		}

		$event_data = $this->build_payment_method_event_data( 'updated', $token );

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'payment_method_updated', $event_data );
			$this->dispatcher->dispatch_event( 'payment_method_updated', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for payment method event: %s | Error: %s',
					'payment_method_updated',
					$e->getMessage()
				),
				array(
					'event_type' => 'payment_method_updated',
					'exception'  => $e,
				)
			);
		}
	}

	/**
	 * Handle payment method set as default event.
	 *
	 * Triggers fraud protection event tracking when a payment method is set as default.
	 *
	 * @internal
	 *
	 * @param int               $token_id The ID of the token being set as default.
	 * @param \WC_Payment_Token $token    The payment token object.
	 */
	public function handle_payment_method_set_default( $token_id, $token ): void {
		$event_data = $this->build_payment_method_event_data( 'set_default', $token );

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'payment_method_set_default', $event_data );
			$this->dispatcher->dispatch_event( 'payment_method_set_default', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for payment method event: %s | Error: %s',
					'payment_method_set_default',
					$e->getMessage()
				),
				array(
					'event_type' => 'payment_method_set_default',
					'exception'  => $e,
				)
			);
		}
	}

	/**
	 * Handle payment method deleted event.
	 *
	 * Triggers fraud protection event tracking when a payment method is deleted.
	 *
	 * @internal
	 *
	 * @param int               $token_id The ID of the deleted token.
	 * @param \WC_Payment_Token $token    The payment token object.
	 */
	public function handle_payment_method_deleted( $token_id, $token ): void {
		$event_data = $this->build_payment_method_event_data( 'deleted', $token );

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'payment_method_deleted', $event_data );
			$this->dispatcher->dispatch_event( 'payment_method_deleted', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for payment method event: %s | Error: %s',
					'payment_method_deleted',
					$e->getMessage()
				),
				array(
					'event_type' => 'payment_method_deleted',
					'exception'  => $e,
				)
			);
		}
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
