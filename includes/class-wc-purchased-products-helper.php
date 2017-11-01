<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Purchased_Products_Helper class.
 *
 * @class     WC_Purchased_Products_Helper
 * @version   3.3.0
 * @package   WooCommerce/Classes
 * @category  Class
 * @author    WooThemes
 */
class WC_Purchased_Products_Helper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
	}

	/**
	 * Marks all products in an existing order as purchased in the purchased table.
	 *
	 * @param WC_Order $order
	 */
	public static function mark_order_purchased( $order ) {
		global $wpdb;

		$order_items = $order->get_items();

		foreach ( $order_items as $item_id => $item ) {
			$item_product_id = $item->get_product_id();

			if ( ! empty( $item_product_id ) ) {
				$data = array(
					'user_email'           => $order->get_billing_email(),
					'order_id'             => $order->get_id(),
					'user_id'              => $order->get_customer_id(),
					'product_id'           => $item_product_id,
					'parent_order_item_id' => $item_id,
				);

				$wpdb->insert(
					$wpdb->prefix . 'woocommerce_purchased_products',
					$data,
					array(
						'%s',
						'%d',
						'%d',
						'%d',
						'%d',
					)
				);
			}
		}
	}

}

WC_Purchased_Products_Helper::init();
