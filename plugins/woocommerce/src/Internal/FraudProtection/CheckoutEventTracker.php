<?php
/**
 * CheckoutEventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks checkout events for fraud protection analysis.
 *
 * This class hooks into both WooCommerce Blocks (Store API) and traditional
 * shortcode checkout events, triggering comprehensive event tracking with
 * full session context for fraud protection analysis.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CheckoutEventTracker implements RegisterHooksInterface {

	/**
	 * Fraud protection dispatcher instance.
	 *
	 * @var FraudProtectionDispatcher
	 */
	private FraudProtectionDispatcher $dispatcher;

	/**
	 * Fraud protection controller instance.
	 *
	 * @var FraudProtectionController
	 */
	private FraudProtectionController $fraud_protection_controller;

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
	 * @param FraudProtectionDispatcher $dispatcher The fraud protection dispatcher instance.
	 * @param FraudProtectionController $fraud_protection_controller The fraud protection controller instance.
	 * @param SessionDataCollector      $data_collector The session data collector instance.
	 */
	final public function init(
		FraudProtectionDispatcher $dispatcher,
		FraudProtectionController $fraud_protection_controller,
		SessionDataCollector $data_collector
	): void {
		$this->dispatcher                  = $dispatcher;
		$this->fraud_protection_controller = $fraud_protection_controller;
		$this->data_collector              = $data_collector;
	}

	/**
	 * Register checkout event hooks.
	 *
	 * Hooks into both WooCommerce Blocks (Store API) and traditional checkout
	 * actions to track fraud protection events. Only registers hooks if the
	 * fraud protection feature is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		// Only register hooks if fraud protection is enabled.
		if ( ! $this->fraud_protection_controller->feature_is_enabled() ) {
			return;
		}

		// Shortcode checkout: Track when checkout fields are updated.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'handle_shortcode_checkout_field_update' ), 10, 1 );
	}

	/**
	 * Handle Store API customer update event (WooCommerce Blocks checkout).
	 *
	 * Triggered when customer information is updated via the Store API endpoint
	 * /wc/store/v1/cart/update-customer during Blocks checkout flow.
	 *
	 * @internal
	 * @return void
	 */
	public function track_blocks_checkout_update(): void {
		// The data collector already has up to date customer data, so we don't need to pass it as a parameter.
		// At this point we don't have any payment or shipping data, so we don't need to pass it as a parameter either.
		$collected_data = $this->data_collector->collect( 'checkout_update', array() );
		$this->dispatcher->dispatch_event( 'checkout_update', $collected_data );
	}

	/**
	 * Handle shortcode checkout field update event.
	 *
	 * Triggered when checkout fields are updated via AJAX (woocommerce_update_order_review).
	 *
	 * @internal
	 *
	 * @param string $posted_data Serialized checkout form data.
	 * @return void
	 */
	public function handle_shortcode_checkout_field_update( $posted_data ): void {
		// Parse the posted data to extract relevant fields.
		$data = array();
		if ( $posted_data ) {
			parse_str( $posted_data, $data );
		}

		// Build and dispatch the event (traditional checkout includes payment/shipping methods).
		$event_data = $this->format_checkout_event_data( 'field_update', $data );
		$this->dispatcher->dispatch_event( 'checkout_field_update', $event_data );
	}

	/**
	 * Build checkout event-specific data.
	 *
	 * Prepares the checkout event data including action type and any changed fields.
	 * This data will be merged with comprehensive session data during event tracking.
	 *
	 * @param string $action Action type (field_update, store_api_update).
	 * @param array  $collected_event_data Posted form data or event context (may include session data).
	 * @return array Checkout event data.
	 */
	private function format_checkout_event_data( string $action, array $collected_event_data ): array {
		$event_data = array( 'action' => $action );

		// Extract and merge all checkout field groups.
		$event_data = array_merge(
			$event_data,
			$this->extract_billing_fields( $collected_event_data ),
			$this->extract_shipping_fields( $collected_event_data ),
			$this->extract_payment_method( $collected_event_data ),
		);

		return $event_data;
	}

	/**
	 * Extract billing fields from posted data.
	 *
	 * @param array $posted_data Posted form data.
	 * @return array Billing fields.
	 */
	private function extract_billing_fields( array $posted_data ): array {
		$field_map = array(
			'billing_email'      => 'sanitize_email',
			'billing_first_name' => 'sanitize_text_field',
			'billing_last_name'  => 'sanitize_text_field',
			'billing_country'    => 'sanitize_text_field',
			'billing_address_1'  => 'sanitize_text_field',
			'billing_address_2'  => 'sanitize_text_field',
			'billing_city'       => 'sanitize_text_field',
			'billing_state'      => 'sanitize_text_field',
			'billing_postcode'   => 'sanitize_text_field',
			'billing_phone'      => 'sanitize_text_field',
		);

		$extracted_fields = $this->extract_fields_by_map( $field_map, $posted_data );

		// Store API uses 'email' instead of 'billing_email'.
		if ( empty( $extracted_fields['billing_email'] ) && ! empty( $posted_data['email'] ) ) {
			$extracted_fields['email'] = sanitize_email( $posted_data['email'] );
		}

		return $extracted_fields;
	}

	/**
	 * Extract shipping fields from posted data.
	 *
	 * @param array $posted_data Posted form data.
	 * @return array Shipping fields.
	 */
	private function extract_shipping_fields( array $posted_data ): array {
		if ( ! isset( $posted_data['ship_to_different_address'] ) || ! $posted_data['ship_to_different_address'] ) {
			return array();
		}

		$field_map = array(
			'shipping_first_name' => 'sanitize_text_field',
			'shipping_last_name'  => 'sanitize_text_field',
			'shipping_country'    => 'sanitize_text_field',
			'shipping_address_1'  => 'sanitize_text_field',
			'shipping_address_2'  => 'sanitize_text_field',
			'shipping_city'       => 'sanitize_text_field',
			'shipping_state'      => 'sanitize_text_field',
			'shipping_postcode'   => 'sanitize_text_field',
		);

		return $this->extract_fields_by_map( $field_map, $posted_data );
	}

	/**
	 * Extract and sanitize fields from posted data using a field map.
	 *
	 * Generic extraction method that iterates through a field map and extracts
	 * non-empty fields from posted data, applying the appropriate sanitization
	 * function to each field.
	 *
	 * @param array $field_map    Map of field names to sanitization functions.
	 * @param array $posted_data  Posted form data.
	 * @return array Extracted and sanitized fields.
	 */
	private function extract_fields_by_map( array $field_map, array $posted_data ): array {
		$extracted_fields = array();

		foreach ( $field_map as $field_name => $sanitize_function ) {
			if ( ! empty( $posted_data[ $field_name ] ) ) {
				$extracted_fields[ $field_name ] = $sanitize_function( wp_unslash( $posted_data[ $field_name ] ) );
			}
		}

		return $extracted_fields;
	}

	/**
	 * Extract payment method data from posted data.
	 *
	 * Extracts payment method ID and retrieves the readable gateway name.
	 *
	 * @param array $posted_data Posted form data.
	 * @return array Payment method data with ID and name, or empty array if not found.
	 */
	private function extract_payment_method( array $posted_data ): array {
		$payment_data = array();

		if ( ! empty( $posted_data['payment_method'] ) ) {
			$payment_gateway_name = WC()->payment_gateways()->get_payment_gateway_name_by_id( $posted_data['payment_method'] );

			$payment_data['payment'] = array(
				'payment_gateway_type' => $posted_data['payment_method'],
				'payment_gateway_name' => $payment_gateway_name,
			);
		}

		return $payment_data;
	}
}
