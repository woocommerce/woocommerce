<?php
/**
 * PayPal Helper Class
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
        // phpcs:ignore Generic.Commenting.Todo.TaskFound
		// TODO: We expect this flag to be true if the merchant can be migrated,
		// i.e. does not need PayPal API keys, and they have accepted the ToS.

		$settings      = get_option( 'woocommerce_paypal_settings', array() );
		$use_orders_v2 = isset( $settings['use_orders_v2'] ) && 'yes' === $settings['use_orders_v2'];

		/**
		 * Filters whether the gateway should use Orders v2 API.
		 *
		 * @param bool $use_orders_v2 Whether the gateway should use Orders v2 API.
		 *
		 * @since 10.1.0
		 */
		return apply_filters(
			'woocommerce_paypal_use_orders_v2',
			$use_orders_v2
		);
	}
}