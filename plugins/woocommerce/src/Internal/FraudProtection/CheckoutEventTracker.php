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
 * context. It implements batching to reduce API calls for rapid successive updates.
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
	 * Fraud protection controller instance.
	 *
	 * @var FraudProtectionController
	 */
	private FraudProtectionController $fraud_protection_controller;

	/**
	 * Batch interval in seconds for checkout events.
	 *
	 * This defines the minimum time between tracking events to reduce API calls
	 * when customers rapidly update checkout fields.
	 *
	 * @var int
	 */
	private const BATCH_INTERVAL_SECONDS = 15;

	/**
	 * Session key for storing last tracking timestamp.
	 *
	 * @var string
	 */
	private const SESSION_KEY_LAST_TRACK = 'fraud_protection_checkout_last_track';

	/**
	 * Session key for storing pending event data.
	 *
	 * @var string
	 */
	private const SESSION_KEY_PENDING_DATA = 'fraud_protection_checkout_pending_data';

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param FraudProtectionTracker    $tracker                     The fraud protection tracker instance.
	 * @param FraudProtectionController $fraud_protection_controller The fraud protection controller instance.
	 */
	final public function init(
		FraudProtectionTracker $tracker,
		FraudProtectionController $fraud_protection_controller
	): void {
		$this->tracker                     = $tracker;
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

		// Store API (block checkout): Track checkout field updates.
		add_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( $this, 'handle_store_api_checkout_update' ), 10, 2 );

		// WooCommerce AJAX: Handle payment method selection tracking.
		add_action( 'wc_ajax_fraud_protection_payment_method_selected', array( $this, 'ajax_handle_payment_method_selected' ) );

		// Flush any pending batched events at shutdown.
		add_action( 'shutdown', array( $this, 'flush_pending_events' ), 10, 0 );
	}

	/**
	 * Handle traditional checkout field update event.
	 *
	 * Triggered when checkout fields are updated via AJAX (woocommerce_update_order_review).
	 * Implements batching to reduce API calls for rapid successive updates.
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
		$this->track_event_with_batching( 'checkout_field_update', $event_data );
	}

	/**
	 * Handle Store API checkout update event.
	 *
	 * Triggered when checkout data is updated via the Store API (block checkout).
	 * Implements batching to reduce API calls for rapid successive updates.
	 *
	 * @internal
	 *
	 * @param \WC_Customer      $customer The customer object being updated.
	 * @param \WP_REST_Request  $request  The REST API request object.
	 * @return void
	 */
	public function handle_store_api_checkout_update( $customer, $request ): void {
		// Extract billing data from the request.
		$billing_address = $request->get_param( 'billing_address' );
		$email           = $billing_address['email'] ?? null;

		$event_data = $this->build_checkout_event_data(
			'store_api_update',
			array(
				'email'           => $email,
				'billing_address' => $billing_address,
			)
		);

		$this->track_event_with_batching( 'checkout_store_api_update', $event_data );
	}

	/**
	 * Handle AJAX payment method selection event.
	 *
	 * Triggered via WooCommerce AJAX when payment method is changed in checkout.
	 * This is called from JavaScript when the payment_method_selected event fires.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function ajax_handle_payment_method_selected(): void {
		// Get payment method from POST data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce AJAX endpoints don't require nonce for logged-out users.
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';

		if ( empty( $payment_method ) ) {
			wp_send_json_error( array( 'message' => 'Payment method is required.' ) );
			return;
		}

		// Track the payment method selection.
		$event_data = $this->build_checkout_event_data(
			'payment_method_selected',
			array( 'payment' => array( 'payment_method_type' => $payment_method ) )
		);

		$this->track_event_with_batching( 'checkout_payment_method_selected', $event_data );

		// Send success response.
		wp_send_json_success( array( 'message' => 'Payment method tracked.' ) );
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
			$payment_method_id   = sanitize_text_field( wp_unslash( $posted_data['payment']['payment_method_type'] ) );
			$payment_method_name = PaymentMethodHelper::get_payment_method_name( $payment_method_id );

			$payment_data['payment'] = array(
				'payment_method_type' => $payment_method_id,
				'payment_method_name' => $payment_method_name,
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
	 * Track event with batching to reduce API calls.
	 *
	 * Implements a batching mechanism that prevents tracking events more frequently
	 * than BATCH_INTERVAL_SECONDS. When rapid updates occur, only the most recent
	 * event data is retained and will be flushed at shutdown.
	 *
	 * @param string $event_type          Event type identifier (e.g., 'checkout_field_update').
	 * @param array  $event_specific_data Event-specific data to merge with session context.
	 * @return void
	 */
	private function track_event_with_batching( string $event_type, array $event_specific_data ): void {
		// Get last tracking timestamp from session.
		$last_track_time = $this->get_last_track_time();
		$current_time    = time();

		// Check if enough time has passed since last tracking.
		if ( $last_track_time && ( $current_time - $last_track_time ) < self::BATCH_INTERVAL_SECONDS ) {
			// Store the pending event data to be flushed later.
			$this->store_pending_event( $event_type, $event_specific_data );
			return;
		}

		// Track the event immediately.
		$this->tracker->track_event( $event_type, $event_specific_data );

		// Update last tracking timestamp.
		$this->update_last_track_time( $current_time );

		// Clear any pending event data since we just tracked.
		$this->clear_pending_event();
	}

	/**
	 * Flush any pending batched events.
	 *
	 * This method is called at shutdown to ensure that any batched events that
	 * haven't been sent yet are flushed before the request ends.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function flush_pending_events(): void {
		$pending = $this->get_pending_event();

		if ( ! $pending ) {
			return;
		}

		$event_type          = $pending['event_type'] ?? null;
		$event_specific_data = $pending['event_data'] ?? array();

		if ( $event_type ) {
			$this->tracker->track_event( $event_type, $event_specific_data );
			$this->update_last_track_time( time() );
			$this->clear_pending_event();
		}
	}

	/**
	 * Get last tracking timestamp from session.
	 *
	 * @return int|null Timestamp of last tracking, or null if not set.
	 */
	private function get_last_track_time(): ?int {
		if ( ! WC()->session instanceof \WC_Session ) {
			return null;
		}

		$time = WC()->session->get( self::SESSION_KEY_LAST_TRACK );
		return $time ? (int) $time : null;
	}

	/**
	 * Update last tracking timestamp in session.
	 *
	 * @param int $timestamp The timestamp to store.
	 * @return void
	 */
	private function update_last_track_time( int $timestamp ): void {
		if ( WC()->session instanceof \WC_Session ) {
			WC()->session->set( self::SESSION_KEY_LAST_TRACK, $timestamp );
		}
	}

	/**
	 * Store pending event data in session.
	 *
	 * @param string $event_type          Event type identifier.
	 * @param array  $event_specific_data Event-specific data.
	 * @return void
	 */
	private function store_pending_event( string $event_type, array $event_specific_data ): void {
		if ( ! WC()->session instanceof \WC_Session ) {
			return;
		}

		WC()->session->set(
			self::SESSION_KEY_PENDING_DATA,
			array(
				'event_type' => $event_type,
				'event_data' => $event_specific_data,
			)
		);
	}

	/**
	 * Get pending event data from session.
	 *
	 * @return array|null Pending event data, or null if none.
	 */
	private function get_pending_event(): ?array {
		if ( ! WC()->session instanceof \WC_Session ) {
			return null;
		}

		$pending = WC()->session->get( self::SESSION_KEY_PENDING_DATA );
		return is_array( $pending ) ? $pending : null;
	}

	/**
	 * Clear pending event data from session.
	 *
	 * @return void
	 */
	private function clear_pending_event(): void {
		if ( WC()->session instanceof \WC_Session ) {
			WC()->session->set( self::SESSION_KEY_PENDING_DATA, null );
		}
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
				$method_parts     = explode( ':', $method_id );
				$base_method_id   = $method_parts[0];
				$instance_id      = isset( $method_parts[1] ) ? $method_parts[1] : null;

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
					} elseif ( isset( $method->method_title ) ) {
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
