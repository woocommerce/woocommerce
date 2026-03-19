<?php
/**
 * PayPal Helper Class
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper instead. This class will be removed in 11.0.0.
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Gateways\PayPal\Helper as PayPalHelper;

if ( ! class_exists( 'WC_Gateway_Paypal_Constants' ) ) {
	require_once __DIR__ . '/class-wc-gateway-paypal-constants.php';
}

/**
 * Helper for PayPal gateway.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper instead. This class will be removed in 11.0.0.
 */
class WC_Gateway_Paypal_Helper {
	/**
	 * Check if the PayPal gateway is enabled.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper::is_paypal_gateway_available() instead.
	 * @return bool
	 */
	public static function is_paypal_gateway_available() {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Helper::is_paypal_gateway_available()' );
		return PayPalHelper::is_paypal_gateway_available();
	}

	/**
	 * Check if the merchant is eligible for migration from WPS to PPCP.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper::is_orders_v2_migration_eligible() instead.
	 * @return bool
	 */
	public static function is_orders_v2_migration_eligible() {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Helper::is_orders_v2_migration_eligible()' );
		return PayPalHelper::is_orders_v2_migration_eligible();
	}

	/**
	 * Get the WC order from the PayPal custom ID.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper::get_wc_order_from_paypal_custom_id() instead.
	 * @param string $custom_id The custom ID string from the PayPal order.
	 * @return WC_Order|null
	 */
	public static function get_wc_order_from_paypal_custom_id( $custom_id ) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Helper::get_wc_order_from_paypal_custom_id()' );
		if ( ! is_string( $custom_id ) || '' === $custom_id ) {
			return null;
		}

		return PayPalHelper::get_wc_order_from_paypal_custom_id( (string) $custom_id );
	}

	/**
	 * Remove PII (Personally Identifiable Information) from data for logging.
	 *
	 * This function recursively traverses the data array and redacts sensitive information
	 * while preserving the structure for debugging purposes.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper::redact_data() instead.
	 * @param mixed $data The data to remove PII from (array, string, or other types).
	 * @return mixed The data with PII redacted.
	 */
	public static function redact_data( $data ) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Helper::redact_data()' );
		return PayPalHelper::redact_data( $data );
	}

	/**
	 * Mask email address before @ keeping the full domain.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper::mask_email() instead.
	 * @param string $email The email address to mask.
	 * @return string The masked email address or original input if invalid.
	 */
	public static function mask_email( $email ) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Helper::mask_email()' );
		return PayPalHelper::mask_email( (string) $email );
	}

	/**
	 * Update the addresses in the order.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Helper::update_addresses_in_order() instead.
	 * @param WC_Order|null $order The order object.
	 * @param array         $paypal_order_details The PayPal order details.
	 * @return void
	 */
	public static function update_addresses_in_order( ?WC_Order $order, array $paypal_order_details ): void {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Helper::update_addresses_in_order()' );
		PayPalHelper::update_addresses_in_order( $order, $paypal_order_details );
	}
}
