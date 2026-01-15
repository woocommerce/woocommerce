<?php
/**
 * WC Admin Order Trait
 *
 * WC Admin Order Trait class that houses shared functionality across order and refund classes.
 */

namespace Automattic\WooCommerce\Admin\Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * OrderTraits class.
 */
trait OrderTraits {
	/**
	 * Calculate shipping amount for line item/product as a total shipping amount ratio based on quantity.
	 *
	 * @param WC_Order_Item $item              Line item from order.
	 * @param int           $order_items_count (optional) The number of order items in an order. This could be the remaining items left to refund.
	 * @param float         $shipping_amount   (optional) The shipping fee amount in an order. This could be the remaining shipping amount left to refund.
	 *
	 * @return float|int
	 */
	public function get_item_shipping_amount( $item, $order_items_count = null, $shipping_amount = null ) {
		// Shipping amount loosely based on woocommerce code in includes/admin/meta-boxes/views/html-order-item(s).php
		// distributed simply based on number of line items.
		$product_qty = $item->get_quantity( 'edit' );

		// Use the passed order_items_count if provided, otherwise get the total number of items in the order.
		// This is useful when calculating refunds for partial items in an order.
		// For example, if 2 items are refunded from an order with 4 items. The remaining 2 items should have the shipping fee of the refunded items distributed to them.
		$order_items = null !== $order_items_count ? $order_items_count : $this->get_item_count();

		if ( 0 === $order_items ) {
			return 0;
		}

		// Use the passed shipping_amount if provided, otherwise get the total shipping amount in the order.
		// This is useful when calculating refunds for partial shipping in an order.
		// For example, if $10 shipping is refunded from an order with $30 shipping, the remaining $20 should be distributed to the remaining items.
		$total_shipping_amount = null !== $shipping_amount ? $shipping_amount : (float) $this->get_shipping_total();

		return $total_shipping_amount / $order_items * $product_qty;
	}

	/**
	 * Calculate shipping tax amount for line item/product as a total shipping tax amount ratio based on quantity.
	 *
	 * Loosely based on code in includes/admin/meta-boxes/views/html-order-item(s).php.
	 *
	 * @todo If WC is currently not tax enabled, but it was before (or vice versa), would this work correctly?
	 *
	 * @param WC_Order_Item $item Line item from order.
	 * @param int           $order_items_count   (optional) The number of order items in an order. This could be the remaining items left to refund.
	 * @param float         $shipping_tax_amount (optional) The shipping tax amount in an order. This could be the remaining shipping tax amount left to refund.
	 *
	 * @return float|int
	 */
	public function get_item_shipping_tax_amount( $item, $order_items_count = null, $shipping_tax_amount = null ) {
		// Use the passed order_items_count if provided, otherwise get the total number of items in the order.
		// This is useful when calculating refunds for partial items in an order.
		// For example, if 2 items are refunded from an order with 4 items. The remaining 2 items should have the shipping tax of the refunded items distributed to them.
		$order_items = null !== $order_items_count ? $order_items_count : $this->get_item_count();

		if ( 0 === $order_items ) {
			return 0;
		}

		// Use the passed shipping_tax_amount if provided, otherwise initialize it to 0 and calculate the total shipping tax amount in the order.
		// This is useful when calculating refunds for partial shipping tax in an order.
		// For example, if $1 shipping tax is refunded from an order with $3 shipping tax, the remaining $2 should be distributed to the remaining items.
		$total_shipping_tax_amount = $shipping_tax_amount ? $shipping_tax_amount : 0;

		if ( null === $shipping_tax_amount ) {
			$order_taxes         = $this->get_taxes();
			$line_items_shipping = $this->get_items( 'shipping' );
			foreach ( $line_items_shipping as $item_id => $shipping_item ) {
				$tax_data = $shipping_item->get_taxes();
				if ( $tax_data ) {
					foreach ( $order_taxes as $tax_item ) {
						$tax_item_id                = $tax_item->get_rate_id();
						$tax_item_total             = isset( $tax_data['total'][ $tax_item_id ] ) ? (float) $tax_data['total'][ $tax_item_id ] : 0;
						$total_shipping_tax_amount += $tax_item_total;
					}
				}
			}
		}

		$product_qty = $item->get_quantity( 'edit' );

		return $total_shipping_tax_amount / $order_items * $product_qty;
	}

	/**
	 * Calculates coupon amount for specified line item/product.
	 *
	 * Coupon calculation based on woocommerce code in includes/admin/meta-boxes/views/html-order-item.php.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return float
	 */
	public function get_item_coupon_amount( $item ) {
		return floatval( $item->get_subtotal( 'edit' ) - $item->get_total( 'edit' ) );
	}

	/**
	 * Calculate cart tax amount for line item/product.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return float
	 */
	public function get_item_cart_tax_amount( $item ) {
		$order_taxes     = $this->get_taxes();
		$tax_data        = $item->get_taxes();
		$cart_tax_amount = 0.0;

		foreach ( $order_taxes as $tax_item ) {
			$tax_item_id      = $tax_item->get_rate_id();
			$cart_tax_amount += isset( $tax_data['total'][ $tax_item_id ] ) ? (float) $tax_data['total'][ $tax_item_id ] : 0;
		}

		return $cart_tax_amount;
	}
}
