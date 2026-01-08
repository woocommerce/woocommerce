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
 * This class hooks into WooCommerce checkout events (billing/email changes,
 * payment selection) and triggers comprehensive event tracking with full session
 * context.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CheckoutEventTracker implements RegisterHooksInterface {

	/**
	 * Fraud protection tracker instance.
	 *
	 * @var FraudProtectionTracker
	 */
	private FraudProtectionTracker $tracker;

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
	 * @param FraudProtectionTracker    $tracker                     The fraud protection tracker instance.
	 * @param SessionDataCollector      $data_collector              The session data collector instance.
	 * @param FraudProtectionController $fraud_protection_controller The fraud protection controller instance.
	 */
	final public function init(
		FraudProtectionTracker $tracker,
		SessionDataCollector $data_collector,
		FraudProtectionController $fraud_protection_controller
	): void {
		$this->tracker                     = $tracker;
		$this->data_collector              = $data_collector;
		$this->fraud_protection_controller = $fraud_protection_controller;
	}

	/**
	 * Register checkout event hooks.
	 *
	 * Hooks into WooCommerce checkout actions to track fraud protection events.
	 * Only registers hooks if the fraud protection feature is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		// Only register hooks if fraud protection is enabled.
		if ( ! $this->fraud_protection_controller->feature_is_enabled() ) {
			return;
		}

		// Traditional checkout: Track when checkout fields are updated.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'handle_checkout_field_update' ), 10, 1 );
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

		$event_data = $this->build_checkout_event_data( 'field_update', $data );
		$this->track_checkout_event( 'checkout_field_update', $event_data );
	}

	/**
	 * Build checkout event-specific data.
	 *
	 * Prepares the checkout event data including action type and any changed fields.
	 * This data will be merged with comprehensive session data during event tracking.
	 *
	 * @param string $action      Action type (field_update, payment_method_selected, store_api_update).
	 * @param array  $posted_data Posted form data or event context.
	 * @return array Checkout event data.
	 */
	private function build_checkout_event_data( string $action, array $posted_data ): array {
		$event_data = array( 'action' => $action );

		// Extract and merge all checkout field groups.
		$event_data = array_merge(
			$event_data,
			$this->extract_billing_fields( $posted_data ),
			$this->extract_shipping_fields( $posted_data ),
			$this->extract_payment_method( $posted_data ),
			$this->extract_shipping_methods( $posted_data )
		);

		return $event_data;
	}

	/**
	 * Extract payment method data from posted data.
	 *
	 * Extracts payment method ID and retrieves the readable gateway name.
	 *
	 * @param array $posted_data Posted form data.
	 * @return array Payment method data with ID and name, or empty array if not found.
	 */
	private static function extract_payment_method( array $posted_data ): array {
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
	 * Track a checkout event immediately.
	 *
	 * Collects comprehensive session data and tracks the event immediately.
	 *
	 * @param string $event_type          Event type identifier.
	 * @param array  $event_specific_data Event-specific data to merge with session context.
	 * @return void
	 */
	private function track_checkout_event( string $event_type, array $event_specific_data ): void {
		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( $event_type, $event_specific_data );
		} catch ( \Exception $e ) {
			// If collection fails, log and abort tracking.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for checkout event: %s | Error: %s',
					$event_type,
					$e->getMessage()
				),
				array(
					'event_type' => $event_type,
					'exception'  => $e,
				)
			);
			return;
		}

		// Track the event immediately.
		$this->tracker->track_event( $event_type, $collected_data );
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
