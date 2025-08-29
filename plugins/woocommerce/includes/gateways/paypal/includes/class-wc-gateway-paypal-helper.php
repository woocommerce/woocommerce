<?php
/**
 * PayPal Helper Class
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

use Automattic\WooCommerce\Utilities\OrderUtil;

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

	/**
	 * Feature flag for Orders v2.
	 *
	 * @return bool
	 */
	public static function is_orders_v2_feature_flag_enabled() {
		return false;
	}

	/**
	 * Get the order by PayPal order ID.
	 *
	 * @param string $paypal_order_id The PayPal order ID.
	 * @return WC_Order|null The order object, or null if not found.
	 */
	public static function get_order_by_paypal_order_id( $paypal_order_id ) {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$args = array(
				'limit'      => 1,
				'meta_query' => array(
					array(
						'key'   => '_paypal_order_id',
						'value' => $paypal_order_id,
					),
				),
			);

			$orders = wc_get_orders( $args );
			if ( ! empty( $orders ) ) {
				return $orders[0];
			}

			return null;
		}

		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT DISTINCT ID FROM $wpdb->posts as posts LEFT JOIN $wpdb->postmeta as meta ON posts.ID = meta.post_id WHERE meta.meta_value = %s AND meta.meta_key = %s",
				$paypal_order_id,
				'_paypal_order_id'
			)
		);

		if ( ! empty( $order_id ) ) {
			return wc_get_order( $order_id );
		}

		return null;
	}
}
