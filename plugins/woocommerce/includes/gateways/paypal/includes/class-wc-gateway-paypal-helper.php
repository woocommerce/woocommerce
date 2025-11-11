<?php
/**
 * PayPal Helper Class
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Paypal_Constants' ) ) {
	require_once __DIR__ . '/class-wc-gateway-paypal-constants.php';
}

/**
 * Helper for PayPal gateway.
 */
class WC_Gateway_Paypal_Helper {
	/**
	 * Check if the PayPal gateway is enabled.
	 *
	 * @return bool
	 */
	public static function is_paypal_gateway_available() {
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
	public static function is_orders_v2_migration_eligible() {
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
	public static function get_wc_order_from_paypal_custom_id( $custom_id ) {
		if ( ! is_string( $custom_id ) || '' === $custom_id ) {
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
		if ( ! $order ) {
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
				$redacted_data[ $key ] = self::mask_email( $value );
				continue;
			}

			if ( is_array( $value ) ) {
				$redacted_data[ $key ] = self::redact_data( $value );
			} elseif ( in_array( $key, WC_Gateway_Paypal_Constants::FIELDS_TO_REDACT, true ) ) {
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
	public static function mask_email( $email ) {
		if ( ! is_string( $email ) || empty( $email ) ) {
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
}
