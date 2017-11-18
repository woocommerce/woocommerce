<?php
/**
 * WooCommerce order product lookup class.
 *
 * @author   Automattic
 * @package  WooCommerce
 * @since    3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * WC_Order_Product_Lookup_Helper class.
 *
 * @class     WC_Order_Product_Lookup_Helper
 * @version   3.3.0
 * @package   WooCommerce/Classes
 * @category  Class
 * @author    WooThemes
 */
class WC_Order_Product_Lookup_Helper {

	/**
	 * Hook in methods.
	 *
	 * @since 3.3.0
	 */
	public static function init() {
		add_action( 'woocommerce_delete_order', array( __CLASS__, 'handle_delete_order' ), 10, 1 );
		add_action( 'woocommerce_delete_product', array( __CLASS__, 'handle_delete_product' ), 10, 1 );
		add_action( 'woocommerce_delete_product_variation', array( __CLASS__, 'handle_delete_product' ), 10, 1 );
		add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'handle_new_order' ), 10, 1 );
		add_action( 'woocommerce_update_order', array( __CLASS__, 'handle_update_order' ), 10, 1 );
	}

	/**
	 * Handler for deleting an order, keep the lookup table in sync.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @since 3.3.0
	 */
	public static function handle_delete_order( $order_id ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'woocommerce_order_product_lookup',
			array(
				'order_id' => $order_id,
			),
			array(
				'%d',
			)
		); // WPCS: cache ok.
	}

	/**
	 * Handler for deleting a product, keep the lookup table in sync.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @since 3.3.0
	 */
	public static function handle_delete_product( $product_id ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'woocommerce_order_product_lookup',
			array(
				'product_id' => $product_id,
			),
			array(
				'%d',
			)
		); // WPCS: cache ok.
	}

	/**
	 * Handler for saving an order, keep the lookup table in sync.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @since 3.3.0
	 */
	public static function handle_new_order( $order ) {
		global $wpdb;

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
	 * Handler for updating an order, keep the lookup table in sync.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @since 3.3.0
	 */
	public static function handle_update_order( $order_id ) {
		// Make sure we delete the old order from the lookup database, as it may have its line items updated.
		self::handle_delete_order( $order_id );

		$order = wc_get_order( $order_id );

		if ( is_a( $order, 'WC_Order' ) ) {
			self::handle_new_order( $order );
		}
	}

	/**
	 * Check if a product is purchased, given either email or user id.
	 *
	 * @param  int        $product_id       Product ID.
	 * @param  string|int $email_or_user_id Either email or user id.
	 * @return bool       Whether product is purchased or not.
	 *
	 * @since 3.3.0
	 */
	public static function is_product_purchased( $product_id, $email_or_user_id ) {
		global $wpdb;

		if ( is_int( $email_or_user_id ) ) {
			$orders = $wpdb->get_results( $wpdb->prepare( "
				SELECT order_id
				FROM {$wpdb->prefix}woocommerce_order_product_lookup
				WHERE ( user_id = %d ) AND product_id = %d
				LIMIT 1
			", absint( $email_or_user_id ), absint( $product_id ) ) ); // WPCS: cache ok.
		} else {
			$orders = $wpdb->get_results( $wpdb->prepare( "
				SELECT order_id
				FROM {$wpdb->prefix}woocommerce_order_product_lookup
				WHERE ( user_email = %s ) AND product_id = %d
				LIMIT 1
			", esc_sql( $email_or_user_id ), absint( $product_id ) ) ); // WPCS: cache ok.
		}

		foreach ( $orders as $order ) {
			$order = wc_get_order( $order->order_id );

			if ( is_callable( array( $order, 'get_date_paid' ) ) ) {
				$date_paid = $order->get_date_paid();

				// Every order is marked as paid at most once.
				if ( ! empty( $date_paid ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get last order for a given user id.
	 *
	 * @param  int      $user_id User ID.
	 * @return WC_Order Order object.
	 *
	 * @since 3.3.0
	 */
	public static function get_last_order( $user_id ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "
			SELECT order_id
			FROM {$wpdb->prefix}woocommerce_order_product_lookup
			WHERE ( user_id = %d )
			ORDER BY order_id DESC
			LIMIT 1
		", absint( $user_id ) ) ); // WPCS: cache ok.

		return wc_get_order( $id );
	}
}

WC_Order_Product_Lookup_Helper::init();
