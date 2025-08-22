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
}
