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

use Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;

/**
 * Helper for PayPal gateway.
 */
class WC_Gateway_Paypal_Helper {

	/**
	 * Check if the gateway should use Orders v2 API.
	 *
	 * @return bool
	 */
	public static function should_use_orders_v2() {
		/**
		 * Filters whether the gateway should use Orders v2 API.
		 *
		 * @param bool $use_orders_v2 Whether the gateway should use Orders v2 API.
		 *
		 * @since 10.1.0
		 */
		$use_orders_v2 = apply_filters(
			'woocommerce_paypal_use_orders_v2',
			self::is_orders_v2_migration_eligible() && self::is_tos_accepted()
		);

		// If the conditions are met, but there is an override to not use Orders v2,
		// respect the override.
		if ( ! $use_orders_v2 ) {
			return false;
		}

		// This is a hard requirement, as we need to be able to send authenticated requests to the proxy.
		$jetpack_connection_manager = new Jetpack_Connection_Manager( 'woocommerce' );
		$is_connected               = $jetpack_connection_manager->is_connected();

		if ( ! $is_connected ) {
			return false;
		}

		// This is another hard requirement, as we need the merchant to be onboarded to Transact
		// to be able to use the proxy.
		$settings = get_option( 'woocommerce_paypal_settings', array() );
		if ( empty( $settings['transact_merchant_public_id'] ) ) {
			return false;
		}

		return true;
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
		$is_test_mode = isset( $settings['testmode'] ) && 'yes' === $settings['testmode'];
		$api_username = $is_test_mode ? ( $settings['sandbox_api_username'] ?? null ) : ( $settings['api_username'] ?? null );
		$api_password = $is_test_mode ? ( $settings['sandbox_api_password'] ?? null ) : ( $settings['api_password'] ?? null );

		return empty( $api_username ) && empty( $api_password );
	}

	/**
	 * Check if the merchant has accepted the ToS.
	 *
	 * @return bool
	 */
	public static function is_tos_accepted() {
		$settings = get_option( 'woocommerce_paypal_settings', array() );
		return isset( $settings['is_tos_accepted'] ) && 'yes' === $settings['is_tos_accepted'];
	}
}
