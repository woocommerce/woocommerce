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
	 * Batch interval in seconds for checkout events.
	 *
	 * This defines how long to wait after the last event before tracking.
	 * Each new event resets this timer (debouncing).
	 *
	 * @var int
	 */
	private const BATCH_INTERVAL_SECONDS = 15;

	/**
	 * Action hook name for scheduled event tracking.
	 *
	 * @var string
	 */
	private const SCHEDULED_ACTION_HOOK = 'woocommerce_fraud_protection_track_checkout_event';

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

		// WooCommerce AJAX: Handle payment method selection tracking.
		add_action( 'wc_ajax_fraud_protection_payment_method_selected', array( $this, 'ajax_handle_payment_method_selected' ) );

		// Scheduled action to track pending events after debounce interval.
		add_action( self::SCHEDULED_ACTION_HOOK, array( $this, 'process_scheduled_tracking' ), 10, 1 );
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
		$this->schedule_tracking( 'checkout_field_update', $event_data );
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

		$this->schedule_tracking( 'checkout_payment_method_selected', $event_data );
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
	 * Schedule a tracking action to run after the debounce interval.
	 *
	 * Collects comprehensive session data and schedules it for tracking.
	 * Cancels any existing scheduled action for this session before scheduling a new one.
	 *
	 * @param string $event_type          Event type identifier.
	 * @param array  $event_specific_data Event-specific data to merge with session context.
	 * @return void
	 */
	private function schedule_tracking( string $event_type, array $event_specific_data ): void {
		$timestamp = time();
		// Get session ID to use as a unique identifier for this customer's actions.
		$session_id = WC()->session instanceof \WC_Session ? WC()->session->get_customer_id() : null;

		if ( ! $session_id ) {
			// Can't schedule without a session ID.
			return;
		}

		// Collect comprehensive session data NOW (while session is available).
		try {
			$collected_data = $this->data_collector->collect( $event_type, $event_specific_data );
		} catch ( \Exception $e ) {
			// If collection fails, log and abort scheduling.
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

		// Cancel any existing scheduled action for this session first.
		$this->cancel_scheduled_tracking( $session_id, $event_type );

		// Schedule action to run after the debounce interval.
		// Pass the COLLECTED data with the action so it's available when it runs.
		$run_time = $timestamp + self::BATCH_INTERVAL_SECONDS;

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				$run_time,
				self::SCHEDULED_ACTION_HOOK,
				array(
					'session_id'     => $session_id,
					'event_type'     => $event_type,
					'collected_data' => $collected_data,
					'timestamp'      => $timestamp,
				),
				'woocommerce-fraud-protection'
			);
		}
	}

	/**
	 * Cancel scheduled tracking actions for a specific session and event type.
	 *
	 * Uses custom SQL query with JSON_EXTRACT on extended_args to find actions
	 * matching session_id and event_type. This is necessary because our collected_data
	 * is too large and gets stored in extended_args, and Action Scheduler's query
	 * builder doesn't support partial matching on extended_args.
	 *
	 * @param string|null $session_id Optional session ID. If not provided, uses current session.
	 * @param string $event_type Event type to cancel.
	 * @return void
	 */
	private function cancel_scheduled_tracking( ?string $session_id = null, string $event_type = '' ): void {
		if ( null === $session_id ) {
			$session_id = WC()->session instanceof \WC_Session ? WC()->session->get_customer_id() : null;
		}

		if ( ! $session_id ) {
			return;
		}

		global $wpdb;

		// Use custom SQL with JSON_EXTRACT on extended_args column.
		// Action Scheduler's query builder doesn't support partial matching on extended_args.
		if ( class_exists( 'ActionScheduler' ) && \ActionScheduler::is_initialized( __FUNCTION__ ) ) {
			// Query for ALL pending actions matching session_id and event_type.
			$sql = $wpdb->prepare(
				"SELECT a.action_id
				FROM {$wpdb->actionscheduler_actions} a
				LEFT JOIN {$wpdb->actionscheduler_groups} g ON g.group_id = a.group_id
				WHERE a.hook = %s
				AND g.slug = %s
				AND a.status = %s
				AND a.extended_args IS NOT NULL
				AND JSON_EXTRACT(a.extended_args, '$.session_id') = %s
				AND JSON_EXTRACT(a.extended_args, '$.event_type') = %s
				ORDER BY a.scheduled_date_gmt ASC",
				self::SCHEDULED_ACTION_HOOK,
				'woocommerce-fraud-protection',
				\ActionScheduler_Store::STATUS_PENDING,
				$session_id,
				$event_type
			);

			$action_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Cancel all found actions.
			foreach ( $action_ids as $action_id ) {
				try {
					\ActionScheduler::store()->cancel_action( (int) $action_id );
				} catch ( \Exception $e ) {
					// Log but continue - action might have been cancelled by another process.
					FraudProtectionController::log(
						'warning',
						sprintf( 'Failed to cancel scheduled action %d: %s', $action_id, $e->getMessage() )
					);
				}
			}
		}
	}

	/**
	 * Process scheduled tracking action.
	 *
	 * Called by Action Scheduler after the debounce interval has passed.
	 * Receives fully-collected event data as arguments, so it doesn't depend on session availability.
	 *
	 * @internal
	 *
	 * @param array $args Action arguments containing session_id, event_type, collected_data, and timestamp.
	 * @return void
	 */
	public function process_scheduled_tracking( array $args ): void {
		$event_type     = $args['event_type'] ?? null;
		$collected_data = $args['collected_data'] ?? array();
		$timestamp      = $args['timestamp'] ?? null;

		// Validate required parameters.
		if ( ! $event_type || ! $timestamp || empty( $collected_data ) ) {
			return;
		}

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
