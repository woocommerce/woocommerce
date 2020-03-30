<?php
/**
 * Helper class which creates and syncs orders with the cart.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Routes\RouteException;

/**
 * OrderController class.
 */
class OrderController {

	/**
	 * Create order and set props based on global settings.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @return \WC_Order A new order object.
	 */
	public function create_order_from_cart() {
		if ( WC()->cart->is_empty() ) {
			throw new RouteException(
				'woocommerce_rest_cart_empty',
				__( 'Cannot create order from empty cart.', 'woo-gutenberg-products-block' ),
				400
			);
		}

		add_filter( 'woocommerce_default_order_status', array( $this, 'default_order_status' ) );

		$order = new \WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->set_created_via( 'store-api' );
		$this->update_order_from_cart( $order );

		remove_filter( 'woocommerce_default_order_status', array( $this, 'default_order_status' ) );

		return $order;
	}

	/**
	 * Update an order using data from the current cart.
	 *
	 * @param \WC_Order $order The order object to update.
	 */
	public function update_order_from_cart( \WC_Order $order ) {
		$this->update_line_items_from_cart( $order );
		$this->update_addresses_from_cart( $order );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		$order->set_customer_id( get_current_user_id() );
		$order->set_customer_ip_address( \WC_Geolocation::get_ip_address() );
		$order->set_customer_user_agent( wc_get_user_agent() );
		$order->update_meta_data( 'is_vat_exempt', WC()->cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no' );
		$order->calculate_totals();
	}

	/**
	 * Copies order data to customer object (not the session), so values persist for future checkouts.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function sync_customer_data_with_order( \WC_Order $order ) {
		if ( $order->get_customer_id() ) {
			$customer = new \WC_Customer( $order->get_customer_id() );
			$customer->set_props(
				[
					'billing_first_name' => $order->get_billing_first_name(),
					'billing_last_name'  => $order->get_billing_last_name(),
					'billing_company'    => $order->get_billing_company(),
					'billing_address_1'  => $order->get_billing_address_1(),
					'billing_address_2'  => $order->get_billing_address_2(),
					'billing_city'       => $order->get_billing_city(),
					'billing_state'      => $order->get_billing_state(),
					'billing_postcode'   => $order->get_billing_postcode(),
					'billing_country'    => $order->get_billing_country(),
					'billing_email'      => $order->get_billing_email(),
					'billing_phone'      => $order->get_billing_phone(),
					'shipping_address_1' => $order->get_shipping_address_1(),
					'shipping_address_2' => $order->get_shipping_address_2(),
					'shipping_city'      => $order->get_shipping_city(),
					'shipping_state'     => $order->get_shipping_state(),
					'shipping_postcode'  => $order->get_shipping_postcode(),
					'shipping_country'   => $order->get_shipping_country(),
				]
			);
			$customer->save();
		};
	}

	/**
	 * Changes default order status to draft for orders created via this API.
	 *
	 * @return string
	 */
	public function default_order_status() {
		return 'checkout-draft';
	}

	/**
	 * Create order line items.
	 *
	 * @internal Knowing if items changed between the order and cart can be complex. Line items are ok because there is a
	 * hash, but no hash exists for other line item types. Having a normalized set of data between cart and order, or
	 * additional hashes, would be useful in the future and to help refactor this code. In the meantime, we're relying
	 * on custom hashes in $this->draft_order to track if things changed.
	 *
	 * @param \WC_Order $order The order object to update.
	 */
	protected function update_line_items_from_cart( \WC_Order $order ) {
		$cart_controller = new CartController();
		$cart            = $cart_controller->get_cart_instance();
		$cart_hashes     = $cart_controller->get_cart_hashes();

		if ( $order->get_cart_hash() !== $cart_hashes['line_items'] ) {
			$order->remove_order_items( 'line_item' );
			$order->set_cart_hash( $cart_hashes['line_items'] );
			WC()->checkout->create_order_line_items( $order, $cart );
		}

		if ( $order->get_meta_data( 'shipping_hash' ) !== $cart_hashes['shipping'] ) {
			$order->remove_order_items( 'shipping' );
			$order->update_meta_data( 'shipping_hash', $cart_hashes['shipping'] );
			WC()->checkout->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping()->get_packages() );
		}

		if ( $order->get_meta_data( 'coupons_hash' ) !== $cart_hashes['coupons'] ) {
			$order->remove_order_items( 'coupon' );
			$order->update_meta_data( 'coupons_hash', $cart_hashes['coupons'] );
			WC()->checkout->create_order_coupon_lines( $order, $cart );
		}

		if ( $order->get_meta_data( 'fees_hash' ) !== $cart_hashes['fees'] ) {
			$order->remove_order_items( 'fee' );
			$order->update_meta_data( 'fees_hash', $cart_hashes['fees'] );
			WC()->checkout->create_order_fee_lines( $order, $cart );
		}

		if ( $order->get_meta_data( 'taxes_hash' ) !== $cart_hashes['taxes'] ) {
			$order->remove_order_items( 'tax' );
			$order->update_meta_data( 'taxes_hash', $cart_hashes['taxes'] );
			WC()->checkout->create_order_tax_lines( $order, $cart );
		}
	}

	/**
	 * Update address data from cart and/or customer session data.
	 *
	 * @param \WC_Order $order The order object to update.
	 */
	protected function update_addresses_from_cart( \WC_Order $order ) {
		$order->set_props( WC()->customer->get_billing() );
		$order->set_props( WC()->customer->get_shipping() );
	}
}
