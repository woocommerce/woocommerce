<?php
/**
 * CheckoutEventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks checkout events for fraud protection analysis.
 *
 * This class provides methods to track both WooCommerce Blocks (Store API) and traditional
 * shortcode checkout events for fraud protection event dispatching.
 * Event-specific data is passed to the dispatcher which handles session data collection internally.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CheckoutEventTracker {

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
	private SessionDataCollector $session_data_collector;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param FraudProtectionDispatcher $dispatcher                The fraud protection dispatcher instance.
	 * @param SessionDataCollector      $session_data_collector    The session data collector instance.
	 */
	final public function init( FraudProtectionDispatcher $dispatcher, SessionDataCollector $session_data_collector ): void {
		$this->dispatcher             = $dispatcher;
		$this->session_data_collector = $session_data_collector;
	}

	/**
	 * Track checkout page loaded event.
	 *
	 * Triggers fraud protection event dispatching when the checkout page is initially loaded.
	 * This captures the initial session state before any user interactions.
	 *
	 * @internal
	 * @return void
	 */
	public function track_checkout_page_loaded(): void {
		// Track the page load event. Session data will be collected by the dispatcher.
		$this->dispatcher->dispatch_event( 'checkout_page_loaded', array() );
	}

	/**
	 * Track Store API customer update event (WooCommerce Blocks checkout).
	 *
	 * Triggered when customer information is updated via the Store API endpoint
	 * /wc/store/v1/cart/update-customer during Blocks checkout flow.
	 *
	 * @internal
	 * @return void
	 */
	public function track_blocks_checkout_update(): void {
		// At this point we don't have any payment or shipping data, so we pass an empty array.
		$this->dispatcher->dispatch_event( 'checkout_update', array() );
	}

	/**
	 * Track shortcode checkout field update event.
	 *
	 * Triggered when checkout fields are updated via AJAX (woocommerce_update_order_review).
	 * Only dispatches event when billing or shipping country changes to reduce unnecessary API calls.
	 *
	 * @internal
	 *
	 * @param string $posted_data Serialized checkout form data.
	 * @return void
	 */
	public function track_shortcode_checkout_field_update( $posted_data ): void {
		// Parse the posted data to extract relevant fields.
		$data = array();
		if ( $posted_data ) {
			parse_str( $posted_data, $data );
		}

		// Get current customer countries using SessionDataCollector.
		$current_billing_country  = $this->session_data_collector->get_current_billing_country();
		$current_shipping_country = $this->session_data_collector->get_current_shipping_country();

		// Get posted countries.
		$posted_billing_country  = $data['billing_country'] ?? '';
		$posted_shipping_country = $data['shipping_country'] ?? '';

		// Check if billing country changed.
		$billing_changed = ! empty( $posted_billing_country ) && $posted_billing_country !== $current_billing_country;

		// Check if shipping country changed.
		$ship_to_different = ! empty( $data['ship_to_different_address'] );
		if ( $ship_to_different ) {
			// User wants different shipping address - check if shipping country changed.
			$shipping_changed = ! empty( $posted_shipping_country ) && $posted_shipping_country !== $current_shipping_country;
		} else {
			// User wants same address for billing and shipping.
			// If current shipping country exists and differs from billing country, it's a change.
			$effective_billing_country = ! empty( $posted_billing_country ) ? $posted_billing_country : $current_billing_country;
			$shipping_changed          = ! empty( $current_shipping_country ) && $current_shipping_country !== $effective_billing_country;
		}

		// Only dispatch if either country changed.
		if ( $billing_changed || $shipping_changed ) {
			$event_data = $this->format_checkout_event_data( 'field_update', $data );
			$this->dispatcher->dispatch_event( 'checkout_update', $event_data );
		}
	}

	/**
	 * Build checkout event-specific data.
	 *
	 * Prepares the checkout event data including action type and any changed fields.
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
