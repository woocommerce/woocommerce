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
		add_action( 'woocommerce_delete_order', array( __CLASS__, 'handle_delete_order' ), 10, 1 );
		add_action( 'woocommerce_order_object_updated_props', array( __CLASS__, 'handle_product_purchase' ), 10, 2 );
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

	/**
	 * Handler for deleting an order, keep the purchased products table in sync.
	 *
	 * @param int $order_id
	 */
	public static function handle_delete_order( $order_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocommerce_purchased_products', array( 'order_id' => $order_id ), array( '%d' ) );
	}

	/**
	 * Handler for saving an order, keep the purchased products table in sync.
	 *
	 * @param WC_Order $order
	 * @param WC_Data_Store $order_data_store
	 */
	public static function handle_product_purchase( $order, $updated_props ) {
		// Every order is marked as paid at most once. Only when this happens we're updating the purchased products table.
		if ( ! in_array( 'date_paid', $updated_props ) ) {
			return;
		}

		self::mark_order_purchased( $order );
	}
}

WC_Purchased_Products_Helper::init();
