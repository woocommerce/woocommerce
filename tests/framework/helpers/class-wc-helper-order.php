<?php

/**
 * Class WC_Helper_Order.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Order {

	/**
	 * Delete a product.
	 *
	 * @param int $order_id ID of the order to delete.
	 */
	public static function delete_order( $order_id ) {

		$order = wc_get_order( $order_id );

		// Delete all products in the order
		foreach ( $order->get_items() as $item ) :
			WC_Helper_Product::delete_product( $item['product_id'] );
		endforeach;

		WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete the order post
		wp_delete_post( $order_id, true );
	}

	/**
	 * Create a order.
	 *
	 * @since 2.4
	 *
	 * @return WC_Order Order object.
	 */
	public static function create_order() {

		// Create product
		$product = WC_Helper_Product::create_simple_product();
		WC_Helper_Shipping::create_simple_flat_rate();

		$order_data = array(
			'status'        => 'pending',
			'customer_id'   => 1,
			'customer_note' => '',
			'total'         => '',
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // Required, else wc_create_order throws an exception
		$order 					= wc_create_order( $order_data );

		// Add order products
		$item_id = $order->add_product( $product, 4 );

		// Set billing address
		$billing_address = array(
			'country'    => 'US',
			'first_name' => 'Jeroen',
			'last_name'  => 'Sormani',
			'company'    => 'WooCompany',
			'address_1'  => 'WooAddress',
			'address_2'  => '',
			'postcode'   => '123456',
			'city'       => 'WooCity',
			'state'      => 'NY',
			'email'      => 'admin@example.org',
			'phone'      => '555-32123',
		);
		$order->set_address( $billing_address, 'billing' );

		// Add shipping costs
		$shipping_taxes = WC_Tax::calc_shipping_tax( '10', WC_Tax::get_shipping_tax_rates() );
		$order->add_shipping( new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', $shipping_taxes, 'flat_rate' ) );

		// Set payment gateway
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$order->set_payment_method( $payment_gateways['bacs'] );

		// Set totals
		$order->set_total( 10, 'shipping' );
		$order->set_total( 0, 'cart_discount' );
		$order->set_total( 0, 'cart_discount_tax' );
		$order->set_total( 0, 'tax' );
		$order->set_total( 0, 'shipping_tax' );
		$order->set_total( 40, 'total' ); // 4 x $10 simple helper product

		return wc_get_order( $order->id );
	}
}
