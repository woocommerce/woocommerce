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
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'handle_checkout_field_update' ), 10, 1 );
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
		$collected_data = $this->data_collector->collect( 'checkout_blocks_address_update', array() );
		$this->dispatcher->dispatch_event( 'checkout_blocks_address_update', $collected_data );
	}

	/**
	 * Get payment data structure for fraud protection analysis.
	 *
	 * Returns payment data structure with all 11 supported fields. Currently populates
	 * payment_gateway_name and payment_method_type when available from the chosen payment
	 * method. Other fields are initialized with null values.
	 *
	 * @since 10.5.0
	 *
	 * @param array $event_data Event-specific data that may contain payment information.
	 * @return array Payment data array with 11 keys.
	 */
	private function get_payment_data( array $event_data = array() ): array {
		$payment_data = array(
			'payment_gateway_name'      => null,
			'payment_method_type'       => null,
			'card_bin'                  => null,
			'card_last4'                => null,
			'card_brand'                => null,
			'payer_id'                  => null,
			'outcome'                   => null,
			'decline_reason'            => null,
			'avs_result'                => null,
			'cvc_result'                => null,
			'tokenized_card_identifier' => null,
		);

		try {
			if ( ! empty( $event_data['payment'] ) ) {
				return array_merge( $payment_data, $event_data['payment'] );
			}

			// Try to get chosen payment method from session.
			$chosen_payment_method = $this->get_chosen_payment_method();
			if ( $chosen_payment_method ) {
				$payment_data['payment_gateway_name'] = \sanitize_text_field( $chosen_payment_method );
				$payment_data['payment_method_type']  = \sanitize_text_field( $chosen_payment_method );
			}

			return $payment_data;
		} catch ( \Exception $e ) {
			// Graceful degradation.
			return $payment_data;
		}
	}

	/**
	 * Handle traditional checkout field update event.
	 *
	 * Triggered when checkout fields are updated via AJAX (woocommerce_update_order_review).
	 *
	 * @internal
	 *
	 * @param string $posted_data Serialized checkout form data.
	 * @return void
	 */
	public function handle_checkout_field_update( $posted_data ): void {
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
	 * Track shipping rate selection from Store API if fraud protection is enabled.
	 *
	 * This is called directly from CartSelectShippingRate endpoint to track
	 * shipping method changes in Blocks checkout.
	 *
	 * @param string|null      $package_id The package ID being updated (null for all packages).
	 * @param string           $rate_id The chosen rate ID.
	 * @param \WP_REST_Request $request REST request object.
	 * @return void
	 */
	public function handle_shipping_rate_selection( $package_id, string $rate_id, $request ): void {
		// Build event data with the shipping rate information.
		$collected_event_data = array(
			'shipping_method' => array( $rate_id ),
			'package_id'      => $package_id,
		);

		// Build and dispatch the event.
		$event_data = $this->format_checkout_event_data( 'shipping_rate_select', $collected_event_data );
		$this->dispatcher->dispatch_event( 'checkout_blocks_shipping_rate_select', $event_data );
	}

	/**
	 * Build checkout event-specific data.
	 *
	 * Prepares the checkout event data including action type and any changed fields.
	 * This data will be merged with comprehensive session data during event tracking.
	 *
	 * @param string $action                   Action type (field_update, store_api_update).
	 * @param array  $collected_event_data              Posted form data or event context (may include session data).
	 * @param bool   $include_payment_shipping Whether to include payment method and shipping methods.
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
			$this->extract_shipping_methods( $collected_event_data ),
		);

		return $event_data;
	}

	/**
	 * Populate posted data with payment and shipping methods from session.
	 *
	 * Used for Blocks checkout where payment/shipping methods are stored in session
	 * but not included in the Store API customer update request.
	 *
	 * @return array
	 */
	private function get_payment_and_shipping_methods_from_session_data(): array {
		$session_data = array();
		// Bail if WooCommerce or session is not available.
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return array();
		}

		// Get chosen payment method from session.
		$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );
		if ( $chosen_payment_method ) {
			// Format it the same way as traditional checkout posts it.
			$session_data['payment'] = array(
				'payment_method_type' => $chosen_payment_method,
			);
		}

		// Get chosen shipping methods from session.
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		if ( ! empty( $chosen_shipping_methods ) && is_array( $chosen_shipping_methods ) ) {
			$session_data['shipping_method'] = $chosen_shipping_methods;
		}

		return $session_data;
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

		if ( ! empty( $posted_data['payment']['payment_method_type'] ) ) {
			$payment_gateway_id   = sanitize_text_field( wp_unslash( $posted_data['payment']['payment_method_type'] ) );
			$payment_gateway_name = WC()->payment_gateways()->get_payment_gateway_name_by_id( $payment_gateway_id );

			$payment_data['payment'] = array(
				'payment_gateway_type' => $payment_gateway_id,
				'payment_gateway_name' => $payment_gateway_name,
			);
		}

		return $payment_data;
	}

	/**
	 * Extract and convert shipping method IDs to readable names.
	 *
	 * @param array $posted_data Posted form data.
	 * @return array Shipping method data wrapped in 'shipping_methods' key.
	 */
	private function extract_shipping_methods( array $posted_data ): array {
		$shipping_method_data = array();

		if ( ! empty( $posted_data['shipping_method'] ) ) {
			$shipping_method_ids = $posted_data['shipping_method'];

			$shipping_methods = $this->get_shipping_method_names( $shipping_method_ids );
			if ( ! empty( $shipping_methods ) ) {
				$shipping_method_data['shipping_methods'] = $shipping_methods;
			}
		}

		return $shipping_method_data;
	}

	/**
	 * Get readable shipping method names from shipping method IDs.
	 *
	 * Converts shipping method IDs (e.g., "flat_rate:1", "free_shipping:2")
	 * to their human-readable labels by loading the shipping method instances.
	 *
	 * @param array $shipping_method_ids Array of shipping method IDs.
	 * @return array Associative array mapping shipping method IDs to their names.
	 */
	private function get_shipping_method_names( array $shipping_method_ids ): array {
		$shipping_method_map = array();

		try {
			// Get WooCommerce shipping instance.
			$shipping = WC()->shipping();
			if ( ! $shipping ) {
				return $shipping_method_map;
			}

			// Get all available shipping methods.
			$shipping_methods = $shipping->get_shipping_methods();

			foreach ( $shipping_method_ids as $method_id ) {
				if ( ! is_string( $method_id ) ) {
					continue;
				}

				// Sanitize the method ID.
				$method_id = sanitize_text_field( $method_id );

				// Shipping method IDs can be in format "method_id:instance_id".
				// Extract the base method ID.
				$method_parts   = explode( ':', $method_id );
				$base_method_id = $method_parts[0];
				$instance_id    = isset( $method_parts[1] ) ? $method_parts[1] : null;

				// Try to get the method label.
				$method_label = null;

				// If we have an instance ID, try to get the specific instance label.
				if ( $instance_id && WC()->session instanceof \WC_Session ) {
					// Get chosen shipping methods from session or packages.
					$packages = WC()->shipping()->get_packages();

					foreach ( $packages as $package ) {
						if ( isset( $package['rates'][ $method_id ] ) ) {
							$rate         = $package['rates'][ $method_id ];
							$method_label = $rate->get_label();
							break;
						}
					}
				}

				// Fallback to base method title if no instance label found.
				if ( ! $method_label && isset( $shipping_methods[ $base_method_id ] ) ) {
					$method = $shipping_methods[ $base_method_id ];
					if ( method_exists( $method, 'get_method_title' ) ) {
						$method_label = $method->get_method_title();
					} elseif ( property_exists( $method, 'method_title' ) ) {
						$method_label = $method->method_title;
					}
				}

				// Use the method ID as fallback if no label found.
				if ( ! $method_label ) {
					$method_label = $method_id;
				}

				$shipping_method_map[ $method_id ] = $method_label;
			}
		} catch ( \Exception $e ) {
			// Gracefully handle errors - return what we have so far.
			FraudProtectionController::log(
				'warning',
				sprintf(
					'Failed to get shipping method names: %s',
					$e->getMessage()
				),
				array(
					'shipping_method_ids' => $shipping_method_ids,
					'exception'           => $e,
				)
			);
		}

		return $shipping_method_map;
	}
}
