<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Data Store: Stored in CPT.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Data_Store_CPT extends Abstract_WC_Order_Data_Store_CPT implements WC_Object_Data_Store, WC_Order_Data_Store_Interface {

	/**
	 * Method to create a new order in the database.
	 * @param WC_Order $order
	 */
	public function create( &$order ) {
		$order->set_order_key( 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
		parent::create( $order );
		do_action( 'woocommerce_new_order', $order->get_id() );
	}

	/**
	 * Read order data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Order
	 * @param object $post_object
	 * @since 2.7.0
	 */
	protected function read_order_data( &$order, $post_object ) {
		parent::read_order_data( $order, $post_object );
		$id = $order->get_id();

		$order->set_props( array(
			'order_key'            => get_post_meta( $id, '_order_key', true ),
			'customer_id'          => get_post_meta( $id, '_customer_user', true ),
			'billing_first_name'   => get_post_meta( $id, '_billing_first_name', true ),
			'billing_last_name'    => get_post_meta( $id, '_billing_last_name', true ),
			'billing_company'      => get_post_meta( $id, '_billing_company', true ),
			'billing_address_1'    => get_post_meta( $id, '_billing_address_1', true ),
			'billing_address_2'    => get_post_meta( $id, '_billing_address_2', true ),
			'billing_city'         => get_post_meta( $id, '_billing_city', true ),
			'billing_state'        => get_post_meta( $id, '_billing_state', true ),
			'billing_postcode'     => get_post_meta( $id, '_billing_postcode', true ),
			'billing_country'      => get_post_meta( $id, '_billing_country', true ),
			'billing_email'        => get_post_meta( $id, '_billing_email', true ),
			'billing_phone'        => get_post_meta( $id, '_billing_phone', true ),
			'shipping_first_name'  => get_post_meta( $id, '_shipping_first_name', true ),
			'shipping_last_name'   => get_post_meta( $id, '_shipping_last_name', true ),
			'shipping_company'     => get_post_meta( $id, '_shipping_company', true ),
			'shipping_address_1'   => get_post_meta( $id, '_shipping_address_1', true ),
			'shipping_address_2'   => get_post_meta( $id, '_shipping_address_2', true ),
			'shipping_city'        => get_post_meta( $id, '_shipping_city', true ),
			'shipping_state'       => get_post_meta( $id, '_shipping_state', true ),
			'shipping_postcode'    => get_post_meta( $id, '_shipping_postcode', true ),
			'shipping_country'     => get_post_meta( $id, '_shipping_country', true ),
			'payment_method'       => get_post_meta( $id, '_payment_method', true ),
			'payment_method_title' => get_post_meta( $id, '_payment_method_title', true ),
			'transaction_id'       => get_post_meta( $id, '_transaction_id', true ),
			'customer_ip_address'  => get_post_meta( $id, '_customer_ip_address', true ),
			'customer_user_agent'  => get_post_meta( $id, '_customer_user_agent', true ),
			'created_via'          => get_post_meta( $id, '_created_via', true ),
			'customer_note'        => get_post_meta( $id, '_customer_note', true ),
			'date_completed'       => get_post_meta( $id, '_completed_date', true ),
			'date_paid'            => get_post_meta( $id, '_paid_date', true ),
			'cart_hash'            => get_post_meta( $id, '_cart_hash', true ),
			'customer_note'        => $post_object->post_excerpt,
		) );
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param WC_Order
	 * @since 2.7.0
	 */
	protected function update_post_meta( &$order ) {
		$updated_props     = array();
		$changed_props     = $order->get_changes();
		$meta_key_to_props = array(
			'_order_key'            => 'order_key',
			'_customer_user'        => 'customer_user',
			'_payment_method'       => 'payment_method',
			'_payment_method_title' => 'payment_method_title',
			'_transaction_id'       => 'transaction_id',
			'_customer_ip_address'  => 'customer_ip_address',
			'_customer_user_agent'  => 'customer_user_agent',
			'_created_via'          => 'created_via',
			'_customer_note'        => 'customer_note',
			'_date_completed'       => 'date_completed',
			'_date_paid'            => 'date_paid',
			'_cart_hash'            => 'cart_hash',
		);

		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( ! array_key_exists( $prop, $changed_props ) ) {
				continue;
			}
			$value = $order->{"get_$prop"}( 'edit' );

			if ( '' !== $value ? update_post_meta( $order->get_id(), $meta_key, $value ) : delete_post_meta( $order->get_id(), $meta_key ) ) {
				$updated_props[] = $prop;
			}
		}

		$billing_address_props = array(
			'_billing_first_name' => 'billing_first_name',
			'_billing_last_name'  => 'billing_last_name',
			'_billing_company'    => 'billing_company',
			'_billing_address_1'  => 'billing_address_1',
			'_billing_address_2'  => 'billing_address_2',
			'_billing_city'       => 'billing_city',
			'_billing_state'      => 'billing_state',
			'_billing_postcode'   => 'billing_postcode',
			'_billing_country'    => 'billing_country',
			'_billing_email'      => 'billing_email',
			'_billing_phone'      => 'billing_phone',
		);

		foreach ( $billing_address_props as $meta_key => $prop ) {
			$prop_key = substr( $prop, 8 );
			if ( ! isset( $changed_props['billing'] ) || ! array_key_exists( $prop_key, $changed_props['billing'] ) ) {
				continue;
			}
			$value = $order->{"get_$prop"}( 'edit' );

			if ( '' !== $value ? update_post_meta( $order->get_id(), $meta_key, $value ) : delete_post_meta( $order->get_id(), $meta_key ) ) {
				$updated_props[] = $prop;
			}
		}

		$shipping_address_props = array(
			'_shipping_first_name' => 'shipping_first_name',
			'_shipping_last_name'  => 'shipping_last_name',
			'_shipping_company'    => 'shipping_company',
			'_shipping_address_1'  => 'shipping_address_1',
			'_shipping_address_2'  => 'shipping_address_2',
			'_shipping_city'       => 'shipping_city',
			'_shipping_state'      => 'shipping_state',
			'_shipping_postcode'   => 'shipping_postcode',
			'_shipping_country'    => 'shipping_country',
		);

		foreach ( $shipping_address_props as $meta_key => $prop ) {
			$prop_key = substr( $prop, 9 );
			if ( ! isset( $changed_props['shipping'] ) || ! array_key_exists( $prop_key, $changed_props['shipping'] ) ) {
				continue;
			}
			$value = $order->{"get_$prop"}( 'edit' );

			if ( '' !== $value ? update_post_meta( $order->get_id(), $meta_key, $value ) : delete_post_meta( $order->get_id(), $meta_key ) ) {
				$updated_props[] = $prop;
			}
		}

		parent::update_post_meta( $order );

		// If customer changed, update any downloadable permissions.
		if ( in_array( 'customer_user', $updated_props ) || in_array( 'billing_email', $updated_props ) ) {
			global $wpdb;

			$wpdb->update( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
				array(
					'user_id'    => $order->get_customer_id(),
					'user_email' => $order->get_billing_email(),
				),
				array(
					'order_id'   => $order->get_id(),
				),
				array(
					'%d',
					'%s',
				),
				array(
					'%d',
				)
			);
		}
	}

	/**
	 * Get amount already refunded.
	 *
	 * @param  WC_Order
	 * @return string
	 */
	public function get_total_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( postmeta.meta_value )
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			WHERE postmeta.meta_key = '_refund_amount'
			AND postmeta.post_id = posts.ID
		", $order->get_id() ) );

		return $total;
	}

	/**
	 * Get the total tax refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_tax_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( order_itemmeta.meta_value )
			FROM {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ( order_items.order_id = posts.ID AND order_items.order_item_type = 'tax' )
			WHERE order_itemmeta.order_item_id = order_items.order_item_id
			AND order_itemmeta.meta_key IN ('tax_amount', 'shipping_tax_amount')
		", $order->get_id() ) );

		return abs( $total );
	}

	/**
	 * Get the total shipping refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_shipping_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( order_itemmeta.meta_value )
			FROM {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ( order_items.order_id = posts.ID AND order_items.order_item_type = 'shipping' )
			WHERE order_itemmeta.order_item_id = order_items.order_item_id
			AND order_itemmeta.meta_key IN ('cost')
		", $order->get_id() ) );

		return abs( $total );
	}
}
