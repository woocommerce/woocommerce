<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

use WC_Order;
use Automattic\WooCommerce\Enums\OrderStatus;
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
	 * Check if a country code is supported by PayPal.
	 *
	 * @param string $country_code Country code.
	 * @return bool True if the country is supported by PayPal, false otherwise.
	 */
	public static function is_country_supported_by_paypal( string $country_code ): bool {
		return array_key_exists( $country_code, PayPalConstants::SUPPORTED_COUNTRIES );
	}

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

		$confirmed = 'yes' === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, true );
		if ( ! self::validate_shipping_address( $order, $paypal_order_details, $confirmed ) ) {
			return;
		}

		// Bail early if '_paypal_addresses_updated' is 'yes', meaning the addresses update already have been successful.
		if ( $confirmed ) {
			return;
		}

		$context = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ADDRESS_CONTEXT, true );
		if ( ! empty( $context['callback_amount'] ) ) {
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

			$context['expected_address'] = $shipping_address;
			$context['shipping_address'] = $order->get_address( 'shipping' );
			$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ADDRESS_CONTEXT, $context );
		}

		// Update contact details, preserving the billing location used to calculate taxes.
		$full_name = $paypal_order_details['payer']['name'] ?? array();
		$email     = $paypal_order_details['payer']['email_address'] ?? '';
		if ( ! empty( $full_name ) ) {
			$order->set_billing_first_name( $full_name['given_name'] ?? '' );
			$order->set_billing_last_name( $full_name['surname'] ?? '' );
			$order->set_billing_email( $email );
		}

		// Orders created without a billing location, such as from product page buttons, take it from the payer.
		$billing_address = $paypal_order_details['payer']['address'] ?? array();
		if ( ! empty( $billing_address ) && ! $order->get_billing_country() ) {
			$order->set_billing_country( $billing_address['country_code'] ?? '' );
			$order->set_billing_postcode( $billing_address['postal_code'] ?? '' );
			$order->set_billing_state( $billing_address['admin_area_1'] ?? '' );
			$order->set_billing_city( $billing_address['admin_area_2'] ?? '' );
			$order->set_billing_address_1( $billing_address['address_line_1'] ?? '' );
			$order->set_billing_address_2( $billing_address['address_line_2'] ?? '' );
		}

		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, 'yes' );
		$order->save();
		self::complete_order_after_address_confirmation( $order );
	}

	/**
	 * Mark a captured order paid after its approved address is saved.
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	private static function complete_order_after_address_confirmation( WC_Order $order ): void {
		if ( 'yes' === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_AWAITING_ADDRESS, true )
			&& PayPalConstants::STATUS_COMPLETED === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true )
			&& $order->get_transaction_id()
			&& $order->has_status( OrderStatus::ON_HOLD ) ) {
			$order->delete_meta_data( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_AWAITING_ADDRESS );
			$order->save();
			$order->payment_complete( $order->get_transaction_id() );
		}
	}

	/**
	 * Check the final destination against the address used to price this attempt.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $details The PayPal order details.
	 * @param bool     $confirmed Whether an earlier approval already confirmed this attempt's addresses.
	 * @return bool Whether the destination is safe to use.
	 */
	private static function validate_shipping_address( WC_Order $order, array $details, bool $confirmed = false ): bool {
		if ( 'yes' === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ADDRESS_REVIEW, true ) ) {
			return false;
		}

		$context = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ADDRESS_CONTEXT, true );
		if ( PayPalConstants::SHIPPING_NO_SHIPPING === ( $context['shipping_preference'] ?? '' ) ) {
			return true;
		}
		if ( ! $context && ( $confirmed || ! $order->needs_shipping() ) ) {
			return true;
		}

		$address = $details['purchase_units'][0]['shipping']['address'] ?? array();
		$fields  = array(
			'country_code'   => 'country',
			'admin_area_1'   => 'state',
			'admin_area_2'   => 'city',
			'postal_code'    => 'postcode',
			'address_line_1' => 'address_1',
			'address_line_2' => 'address_2',
		);

		// Legacy attempts have no reliable settings snapshot. Only an unchanged destination is safe.
		$expected_address = $context['expected_address'] ?? array();
		$priced_address   = $context['shipping_address'] ?? $order->get_address( 'shipping' );
		$address_valid    = ! empty( $address );
		foreach ( $fields as $provider_field => $order_field ) {
			$expected = $priced_address[ $order_field ] ?? '';
			if ( $context && 'billing' !== ( $context['address_source'] ?? '' ) ) {
				$expected = $expected_address[ $provider_field ] ?? '';
			}
			// Callbacks supply only the pricing location. Street details arrive with approval.
			if ( ! empty( $context['callback_amount'] ) && ! array_key_exists( $provider_field, $expected_address ) ) {
				continue;
			}
			$address_valid = $address_valid && self::normalize_address_field( $address[ $provider_field ] ?? '' ) === self::normalize_address_field( $expected );
			// Once confirmed, order edits are the merchant's. Only a different PayPal destination needs review.
			if ( ! $confirmed ) {
				$address_valid = $address_valid && self::normalize_address_field( $order->{"get_shipping_{$order_field}"}() ) === self::normalize_address_field( $priced_address[ $order_field ] ?? '' );
			}
		}

		$currency_valid = true;
		$amount_valid   = true;
		if ( ! empty( $context['callback_amount'] ) ) {
			$amount         = $details['purchase_units'][0]['amount'] ?? array();
			$currency_valid = ( $amount['currency_code'] ?? '' ) === $context['callback_amount']['currency_code'];
			$amount_valid   = isset( $amount['value'] ) && is_string( $amount['value'] ) && wc_format_decimal( $amount['value'], wc_get_price_decimals() ) === $context['callback_amount']['value'];
			$amount_valid   = $amount_valid && ( $confirmed || wc_format_decimal( $order->get_total(), wc_get_price_decimals() ) === $context['callback_amount']['value'] );
		}

		if ( $address_valid && $currency_valid && $amount_valid ) {
			return true;
		}

		$differences = array();
		if ( ! $address_valid ) {
			$differences[] = __( 'shipping address', 'woocommerce' );
		}
		if ( ! $currency_valid ) {
			$differences[] = __( 'payment currency', 'woocommerce' );
		}
		if ( ! $amount_valid ) {
			$differences[] = __( 'payment amount', 'woocommerce' );
		}
		$note = sprintf(
			/* translators: %s: comma-separated list of mismatched checkout details. */
			__( 'PayPal checkout details differ from this order: %s. Review the payment and delivery address before fulfilling.', 'woocommerce' ),
			implode( ', ', $differences )
		);
		if ( PayPalConstants::STATUS_COMPLETED === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) ) {
			$note .= ' ' . __( 'PayPal reported this payment as captured. This hold does not issue a refund; verify the payment in PayPal.', 'woocommerce' );
		} else {
			$note .= ' ' . __( 'WooCommerce will skip automatic capture while this order needs review. Verify the payment status in PayPal before fulfilling.', 'woocommerce' );
		}
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ADDRESS_REVIEW, 'yes' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, '' );
		if ( $order->has_status( OrderStatus::ON_HOLD ) ) {
			$order->add_order_note( $note );
		} else {
			$order->update_status( OrderStatus::ON_HOLD, $note );
		}
		$order->save();

		return false;
	}

	/**
	 * Normalize harmless case and surrounding whitespace differences in addresses.
	 *
	 * @param mixed $value Address field.
	 * @return string Normalized value.
	 */
	private static function normalize_address_field( $value ): string {
		return is_string( $value ) ? strtoupper( trim( $value ) ) : '';
	}
}
