<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

use WC_Order;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

defined( 'ABSPATH' ) || exit;

/**
 * PayPal Helper Class
 *
 * Helper methods for PayPal gateway operations including order validation,
 * data redaction, and address updates.
 *
 * @since 10.5.0
 */
class Helper {
	/**
	 * Check if the PayPal gateway is enabled.
	 *
	 * @return bool
	 */
	public static function is_paypal_gateway_available(): bool {
		$settings    = get_option( 'woocommerce_paypal_settings', array() );
		$enabled     = isset( $settings['enabled'] ) && 'yes' === $settings['enabled'];
		$should_load = isset( $settings['_should_load'] ) && 'yes' === $settings['_should_load'];
		return $enabled && $should_load;
	}

	/**
	 * Check if the merchant is eligible for migration from WPS to PPCP.
	 *
	 * @return bool
	 */
	public static function is_orders_v2_migration_eligible(): bool {
		$settings = get_option( 'woocommerce_paypal_settings', array() );

		// If API keys are set, the merchant is not eligible for migration
		// as they may be using features that cannot be seamlessly migrated.
		$is_test_mode  = isset( $settings['testmode'] ) && 'yes' === $settings['testmode'];
		$api_username  = $is_test_mode ? ( $settings['sandbox_api_username'] ?? null ) : ( $settings['api_username'] ?? null );
		$api_password  = $is_test_mode ? ( $settings['sandbox_api_password'] ?? null ) : ( $settings['api_password'] ?? null );
		$api_signature = $is_test_mode ? ( $settings['sandbox_api_signature'] ?? null ) : ( $settings['api_signature'] ?? null );

		return empty( $api_username ) && empty( $api_password ) && empty( $api_signature );
	}

	/**
	 * Get the WC order from the PayPal custom ID.
	 *
	 * @param string $custom_id The custom ID string from the PayPal order.
	 * @return WC_Order|null
	 */
	public static function get_wc_order_from_paypal_custom_id( string $custom_id ): ?WC_Order {
		if ( '' === $custom_id ) {
			return null;
		}

		$data = json_decode( $custom_id, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		$order_id = $data['order_id'] ?? null;
		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return null;
		}

		// Validate the order key.
		$order_key = $data['order_key'] ?? null;
		if ( $order_key !== $order->get_order_key() ) {
			return null;
		}

		return $order;
	}

	/**
	 * Remove PII (Personally Identifiable Information) from data for logging.
	 *
	 * This function recursively traverses the data array and redacts sensitive information
	 * while preserving the structure for debugging purposes.
	 *
	 * @param mixed $data The data to remove PII from (array, string, or other types).
	 * @return mixed The data with PII redacted.
	 */
	public static function redact_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$redacted_data = array();

		foreach ( $data as $key => $value ) {
			// Skip redacting the payee information as it belongs to the store merchant.
			if ( 'payee' === $key ) {
				$redacted_data[ $key ] = $value;
				continue;
			}
			// Mask the email address.
			if ( 'email_address' === $key || 'email' === $key ) {
				$redacted_data[ $key ] = self::mask_email( (string) $value );
				continue;
			}

			if ( is_array( $value ) ) {
				$redacted_data[ $key ] = self::redact_data( $value );
			} elseif ( in_array( $key, Constants::FIELDS_TO_REDACT, true ) ) {
				$redacted_data[ $key ] = '[redacted]';
			} else {
				// Keep non-PII data as is.
				$redacted_data[ $key ] = $value;
			}
		}

		return $redacted_data;
	}

	/**
	 * Mask email address before @ keeping the full domain.
	 *
	 * @param string $email The email address to mask.
	 * @return string The masked email address or original input if invalid.
	 */
	public static function mask_email( string $email ): string {
		if ( empty( $email ) ) {
			return $email;
		}

		$parts = explode( '@', $email, 2 );
		if ( count( $parts ) !== 2 || empty( $parts[0] ) || empty( $parts[1] ) ) {
			return $email;
		}
		list( $local, $domain ) = $parts;

		if ( strlen( $local ) <= 3 ) {
			$masked_local = str_repeat( '*', strlen( $local ) );
		} else {
			$masked_local = substr( $local, 0, 2 )
						. str_repeat( '*', max( 1, strlen( $local ) - 3 ) )
						. substr( $local, -1 );
		}

		return $masked_local . '@' . $domain;
	}

	/**
	 * Update the addresses in the order.
	 *
	 * @param WC_Order|null $order The order object.
	 * @param array         $paypal_order_details The PayPal order details.
	 * @return void
	 */
	public static function update_addresses_in_order( ?WC_Order $order, array $paypal_order_details ): void {
		if ( empty( $order ) || empty( $paypal_order_details ) ) {
			return;
		}

		// Bail early if '_paypal_addresses_updated' is 'yes', meaning the addresses update already have been successful.
		if ( 'yes' === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, true ) ) {
			return;
		}

		// Update the shipping information.
		$full_name = $paypal_order_details['purchase_units'][0]['shipping']['name']['full_name'] ?? '';
		if ( ! empty( $full_name ) ) {
			$name_parts             = explode( ' ', $full_name, 2 );
			$approximate_first_name = $name_parts[0] ?? '';
			$approximate_last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';
			$order->set_shipping_first_name( $approximate_first_name );
			$order->set_shipping_last_name( $approximate_last_name );
		}

		$shipping_address = $paypal_order_details['purchase_units'][0]['shipping']['address'] ?? array();
		if ( ! empty( $shipping_address ) ) {
			$order->set_shipping_country( $shipping_address['country_code'] ?? '' );
			$order->set_shipping_postcode( $shipping_address['postal_code'] ?? '' );
			$order->set_shipping_state( $shipping_address['admin_area_1'] ?? '' );
			$order->set_shipping_city( $shipping_address['admin_area_2'] ?? '' );
			$order->set_shipping_address_1( $shipping_address['address_line_1'] ?? '' );
			$order->set_shipping_address_2( $shipping_address['address_line_2'] ?? '' );
		}

		// Update the billing information.
		$full_name = $paypal_order_details['payer']['name'] ?? array();
		$email     = $paypal_order_details['payer']['email_address'] ?? '';
		if ( ! empty( $full_name ) ) {
			$order->set_billing_first_name( $full_name['given_name'] ?? '' );
			$order->set_billing_last_name( $full_name['surname'] ?? '' );
			$order->set_billing_email( $email );
		}

		$billing_address = $paypal_order_details['payer']['address'] ?? array();
		if ( ! empty( $billing_address ) ) {
			$order->set_billing_country( $billing_address['country_code'] ?? '' );
			$order->set_billing_postcode( $billing_address['postal_code'] ?? '' );
			$order->set_billing_state( $billing_address['admin_area_1'] ?? '' );
			$order->set_billing_city( $billing_address['admin_area_2'] ?? '' );
			$order->set_billing_address_1( $billing_address['address_line_1'] ?? '' );
			$order->set_billing_address_2( $billing_address['address_line_2'] ?? '' );
		}

		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, 'yes' );
		$order->save();
	}
}
