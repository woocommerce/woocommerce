<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Product_Purchases_Helper class.
 *
 * @class     WC_Product_Purchases_Helper
 * @version   3.3.0
 * @package   WooCommerce/Classes
 * @category  Class
 * @author    WooThemes
 */
class WC_Product_Purchases_Helper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'woocommerce_delete_order', array( __CLASS__, 'handle_delete_order' ), 10, 1 );
		add_action( 'woocommerce_delete_product', array( __CLASS__, 'handle_delete_product' ), 10, 1 );
		add_action( 'woocommerce_delete_product_variation', array( __CLASS__, 'handle_delete_product' ), 10, 1 );
		add_action( 'woocommerce_order_object_updated_props', array( __CLASS__, 'handle_product_purchase' ), 10, 2 );
	}

	/**
	 * Handler for deleting an order, keep the purchased products table in sync.
	 *
	 * @param int $order_id
	 */
	public static function handle_delete_order( $order_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocommerce_order_product_lookup', array( 'order_id' => $order_id ), array( '%d' ) );
	}

	/**
	 * Handler for deleting a product, keep the purchased products table in sync.
	 *
	 * @param int $product_id
	 */
	public static function handle_delete_product( $product_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocommerce_order_product_lookup', array( 'product_id' => $product_id ), array( '%d' ) );
	}

	/**
	 * Handler for saving an order, keep the purchased products table in sync.
	 *
	 * @param WC_Order $order
	 * @param array    $updated_props
	 */
	public static function handle_product_purchase( $order, $updated_props ) {
		global $wpdb;

		// Every order is marked as paid at most once.
		// Only when this happens we're updating the purchased products table.
		$date_paid = $order->get_date_paid();

		if ( ! in_array( 'date_paid', $updated_props ) || empty( $date_paid ) ) {
			return;
		}

		$order_items = $order->get_items();

		foreach ( $order_items as $item_id => $item ) {
			$item_product_id = $item->get_product_id();

			// Skip if this line item doesn't have a product id associated.
			if ( empty( $item_product_id ) ) {
				continue;
			}

			$data = array(
				'user_email'           => $order->get_billing_email(),
				'order_id'             => $order->get_id(),
				'user_id'              => $order->get_customer_id(),
				'product_id'           => $item_product_id,
				'parent_order_item_id' => $item_id,
			);

			$wpdb->insert(
				$wpdb->prefix . 'woocommerce_order_product_lookup',
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

	/**
	 * Check if a product is purchased, given either email or user id.
	 *
	 * @param int        $product_id
	 * @param string|int $email_or_user_id
	 */
	public static function is_product_purchased( $product_id, $email_or_user_id ) {
		global $wpdb;

		if ( is_int( $email_or_user_id ) ) {
			$product_purchases = $wpdb->get_row( $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}woocommerce_order_product_lookup
				WHERE ( user_id = %d ) AND product_id = %d
				LIMIT 1
			", absint( $email_or_user_id ), absint( $product_id ) ) );
		} else {
			$product_purchases = $wpdb->get_row( $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}woocommerce_order_product_lookup
				WHERE ( user_email = %s ) AND product_id = %d
				LIMIT 1
			", esc_sql( $email_or_user_id ), absint( $product_id ) ) );
		}

		return ! empty( $product_purchases );
	}
}

WC_Product_Purchases_Helper::init();
