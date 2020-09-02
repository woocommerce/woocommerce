<?php
/**
 * Helper class which creates and syncs orders with the cart.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Utilities;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;

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
		if ( wc()->cart->is_empty() ) {
			throw new RouteException(
				'woocommerce_rest_cart_empty',
				__( 'Cannot create order from empty cart.', 'woocommerce' ),
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
		// Ensure cart is current.
		wc()->cart->calculate_shipping();
		wc()->cart->calculate_totals();

		// Update the current order to match the current cart.
		$this->update_line_items_from_cart( $order );
		$this->update_addresses_from_cart( $order );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		$order->set_customer_id( get_current_user_id() );
		$order->set_customer_ip_address( \WC_Geolocation::get_ip_address() );
		$order->set_customer_user_agent( wc_get_user_agent() );
		$order->update_meta_data( 'is_vat_exempt', wc()->cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no' );
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
					'billing_first_name'  => $order->get_billing_first_name(),
					'billing_last_name'   => $order->get_billing_last_name(),
					'billing_company'     => $order->get_billing_company(),
					'billing_address_1'   => $order->get_billing_address_1(),
					'billing_address_2'   => $order->get_billing_address_2(),
					'billing_city'        => $order->get_billing_city(),
					'billing_state'       => $order->get_billing_state(),
					'billing_postcode'    => $order->get_billing_postcode(),
					'billing_country'     => $order->get_billing_country(),
					'billing_email'       => $order->get_billing_email(),
					'billing_phone'       => $order->get_billing_phone(),
					'shipping_first_name' => $order->get_shipping_first_name(),
					'shipping_last_name'  => $order->get_shipping_last_name(),
					'shipping_company'    => $order->get_shipping_company(),
					'shipping_address_1'  => $order->get_shipping_address_1(),
					'shipping_address_2'  => $order->get_shipping_address_2(),
					'shipping_city'       => $order->get_shipping_city(),
					'shipping_state'      => $order->get_shipping_state(),
					'shipping_postcode'   => $order->get_shipping_postcode(),
					'shipping_country'    => $order->get_shipping_country(),
				]
			);
			$customer->save();
		};
	}

	/**
	 * Final validation ran before payment is taken.
	 *
	 * By this point we have an order populated with customer data and items.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function validate_order_before_payment( \WC_Order $order ) {
		$coupons = $order->get_coupon_codes();

		$coupon_errors = [];

		foreach ( $coupons as $coupon_code ) {
			$coupon = new \WC_Coupon( $coupon_code );

			try {
				$this->validate_coupon_email_restriction( $coupon, $order );
			} catch ( \Exception $error ) {
				$coupon_errors[ $coupon_code ] = $error->getMessage();
			}
			try {
				$this->validate_coupon_usage_limit( $coupon, $order );
			} catch ( \Exception $error ) {
				$coupon_errors[ $coupon_code ] = $error->getMessage();
			}
		}

		if ( $coupon_errors ) {
			// Remove all coupons that were not valid.
			foreach ( $coupon_errors as $coupon_code => $message ) {
				wc()->cart->remove_coupon( $coupon_code );
			}

			// Recalculate totals.
			wc()->cart->calculate_totals();

			// Re-sync order with cart.
			$this->update_order_from_cart( $order );

			// Return exception so customer can review before payment.
			throw new RouteException(
				'woocommerce_rest_cart_coupon_errors',
				sprintf(
					// Translators: %s Coupon codes.
					__( 'Invalid coupons were removed from the cart: "%s"', 'woocommerce' ),
					implode( '", "', array_keys( $coupon_errors ) )
				),
				409,
				[
					'removed_coupons' => $coupon_errors,
				]
			);
		}
	}

	/**
	 * Check email restrictions of a coupon against the order.
	 *
	 * @throws \Exception Exception if invalid data is detected.
	 *
	 * @param \WC_Coupon $coupon Coupon object applied to the cart.
	 * @param \WC_Order  $order Order object.
	 */
	protected function validate_coupon_email_restriction( \WC_Coupon $coupon, $order ) {
		$restrictions = $coupon->get_email_restrictions();

		if ( ! empty( $restrictions ) && $order->get_billing_email() && ! wc()->cart->is_coupon_emails_allowed( [ $order->get_billing_email() ], $restrictions ) ) {
			throw new \Exception( $coupon->get_coupon_error( \WC_Coupon::E_WC_COUPON_NOT_YOURS_REMOVED ) );
		}
	}

	/**
	 * Check usage restrictions of a coupon against the order.
	 *
	 * @throws \Exception Exception if invalid data is detected.
	 *
	 * @param \WC_Coupon $coupon Coupon object applied to the cart.
	 * @param \WC_Order  $order Order object.
	 */
	protected function validate_coupon_usage_limit( \WC_Coupon $coupon, $order ) {
		$coupon_usage_limit = $coupon->get_usage_limit_per_user();

		if ( $coupon_usage_limit > 0 ) {
			$data_store  = $coupon->get_data_store();
			$usage_count = $order->get_customer_id() ? $data_store->get_usage_by_user_id( $coupon, $order->get_customer_id() ) : $data_store->get_usage_by_email( $coupon, $order->get_billing_email() );

			if ( $usage_count >= $coupon_usage_limit ) {
				throw new \Exception( $coupon->get_coupon_error( \WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED ) );
			}
		}
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
	 * @param \WC_Order $order The order object to update.
	 */
	protected function update_line_items_from_cart( \WC_Order $order ) {
		$cart_controller = new CartController();
		$cart            = $cart_controller->get_cart_instance();
		$cart_hashes     = $cart_controller->get_cart_hashes();

		if ( $order->get_cart_hash() !== $cart_hashes['line_items'] ) {
			$order->set_cart_hash( $cart_hashes['line_items'] );
			$order->remove_order_items( 'line_item' );
			wc()->checkout->create_order_line_items( $order, $cart );
		}

		if ( $order->get_meta_data( '_shipping_hash' ) !== $cart_hashes['shipping'] ) {
			$order->update_meta_data( '_shipping_hash', $cart_hashes['shipping'] );
			$order->remove_order_items( 'shipping' );
			wc()->checkout->create_order_shipping_lines( $order, wc()->session->get( 'chosen_shipping_methods' ), wc()->shipping()->get_packages() );
		}

		if ( $order->get_meta_data( '_coupons_hash' ) !== $cart_hashes['coupons'] ) {
			$order->remove_order_items( 'coupon' );
			$order->update_meta_data( '_coupons_hash', $cart_hashes['coupons'] );
			wc()->checkout->create_order_coupon_lines( $order, $cart );
		}

		if ( $order->get_meta_data( '_fees_hash' ) !== $cart_hashes['fees'] ) {
			$order->update_meta_data( '_fees_hash', $cart_hashes['fees'] );
			$order->remove_order_items( 'fee' );
			wc()->checkout->create_order_fee_lines( $order, $cart );
		}

		if ( $order->get_meta_data( '_taxes_hash' ) !== $cart_hashes['taxes'] ) {
			$order->update_meta_data( '_taxes_hash', $cart_hashes['taxes'] );
			$order->remove_order_items( 'tax' );
			wc()->checkout->create_order_tax_lines( $order, $cart );
		}
	}

	/**
	 * Update address data from cart and/or customer session data.
	 *
	 * @param \WC_Order $order The order object to update.
	 */
	protected function update_addresses_from_cart( \WC_Order $order ) {
		$customer_billing = wc()->customer->get_billing();
		$customer_billing = array_combine(
			array_map(
				function( $key ) {
					return 'billing_' . $key;
				},
				array_keys( $customer_billing )
			),
			$customer_billing
		);
		$order->set_props( $customer_billing );

		$customer_shipping = wc()->customer->get_shipping();
		$customer_shipping = array_combine(
			array_map(
				function( $key ) {
					return 'shipping_' . $key;
				},
				array_keys( $customer_shipping )
			),
			$customer_shipping
		);
		$order->set_props( $customer_shipping );
	}
}
