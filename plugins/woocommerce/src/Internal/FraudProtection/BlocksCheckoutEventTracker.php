<?php
/**
 * BlocksCheckoutEventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks Blocks checkout events for fraud protection analysis.
 *
 * This class hooks into WooCommerce Blocks Store API events (customer updates)
 * and triggers comprehensive event tracking with full session context.
 * It implements batching to reduce API calls for rapid successive updates.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class BlocksCheckoutEventTracker implements RegisterHooksInterface {
	/**
	 * Fraud protection controller instance.
	 *
	 * @var FraudProtectionController
	 */
	private FraudProtectionController $fraud_protection_controller;

	/**
	 * Fraud protection tracker instance.
	 *
	 * @var FraudProtectionTracker
	 */
	private FraudProtectionTracker $tracker;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param FraudProtectionTracker    $tracker The fraud protection tracker instance.
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
	 * Register Blocks checkout event hooks.
	 *
	 * Hooks into WooCommerce Store API actions to track fraud protection events.
	 * Only registers hooks if the fraud protection feature is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		// Only register hooks if fraud protection is enabled.
		if ( ! $this->fraud_protection_controller->feature_is_enabled() ) {
			return;
		}

		// WooCommerce Blocks (Store API): Track when customer data is updated in Blocks checkout.
		add_action( 'woocommerce_store_api_cart_update_customer_from_request', array( $this, 'handle_store_api_customer_update' ), 10, 2 );

		// Add script parameters for payment method tracking.
		add_action( 'wp_enqueue_scripts', array( $this, 'add_script_params' ) );
	}

	/**
	 * Add fraud protection parameters to the checkout blocks script.
	 *
	 * @return void
	 */
	public function add_script_params(): void {
		// Only add params on checkout page.
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}

		// Only add params if fraud protection is enabled.
		if ( ! $this->fraud_protection_controller->feature_is_enabled() ) {
			return;
		}

		// Add script parameters to the existing blocks-checkout script.
		wp_localize_script(
			'wc-checkout-block-frontend',
			'wc_fraud_protection_blocks_params',
			array(
				'enabled' => true,
			)
		);
	}

	/**
	 * Handle Store API customer update event (WooCommerce Blocks checkout).
	 *
	 * Triggered when customer information is updated via the Store API endpoint
	 * /wc/store/v1/cart/update-customer during Blocks checkout flow.
	 *
	 * @internal
	 *
	 * @param \WC_Customer     $customer Customer object being updated.
	 * @param \WP_REST_Request $request  REST request object containing customer data.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return void
	 */
	public function handle_store_api_customer_update( $customer, $request ): void {
		// Extract customer data from the REST request.
		$billing_address  = $request->get_param( 'billing_address' ) ?? array();
		$shipping_address = $request->get_param( 'shipping_address' ) ?? array();

		// Build posted data array in the format expected by build_checkout_event_data.
		$posted_data = array();

		// Extract billing fields.
		if ( ! empty( $billing_address ) ) {
			$posted_data['billing_first_name'] = $billing_address['first_name'] ?? '';
			$posted_data['billing_last_name']  = $billing_address['last_name'] ?? '';
			$posted_data['billing_address_1']  = $billing_address['address_1'] ?? '';
			$posted_data['billing_address_2']  = $billing_address['address_2'] ?? '';
			$posted_data['billing_city']       = $billing_address['city'] ?? '';
			$posted_data['billing_state']      = $billing_address['state'] ?? '';
			$posted_data['billing_postcode']   = $billing_address['postcode'] ?? '';
			$posted_data['billing_country']    = $billing_address['country'] ?? '';
			$posted_data['billing_phone']      = $billing_address['phone'] ?? '';
			$posted_data['billing_email']      = $billing_address['email'] ?? '';
		}

		// Extract shipping fields if present.
		if ( ! empty( $shipping_address ) ) {
			$posted_data['ship_to_different_address'] = true;
			$posted_data['shipping_first_name']       = $shipping_address['first_name'] ?? '';
			$posted_data['shipping_last_name']        = $shipping_address['last_name'] ?? '';
			$posted_data['shipping_address_1']        = $shipping_address['address_1'] ?? '';
			$posted_data['shipping_address_2']        = $shipping_address['address_2'] ?? '';
			$posted_data['shipping_city']             = $shipping_address['city'] ?? '';
			$posted_data['shipping_state']            = $shipping_address['state'] ?? '';
			$posted_data['shipping_postcode']         = $shipping_address['postcode'] ?? '';
			$posted_data['shipping_country']          = $shipping_address['country'] ?? '';
		}

		// Build and schedule the event.
		$event_data = $this->build_checkout_event_data( 'store_api_update', $posted_data );
		$this->tracker->track_event( 'checkout_blocks_customer_update', $event_data );
	}

	/**
	 * Build checkout event-specific data.
	 *
	 * Prepares the checkout event data including action type and any changed fields.
	 * This data will be merged with comprehensive session data during event tracking.
	 *
	 * @param string $action      Action type (store_api_update).
	 * @param array  $posted_data Posted form data or event context.
	 * @return array Checkout event data.
	 */
	private function build_checkout_event_data( string $action, array $posted_data ): array {
		$event_data = array( 'action' => $action );

		// Extract and merge all checkout field groups.
		$event_data = array_merge(
			$event_data,
			$this->extract_billing_fields( $posted_data ),
			$this->extract_shipping_fields( $posted_data )
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

		return $this->extract_fields_by_map( $field_map, $posted_data );
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
}
