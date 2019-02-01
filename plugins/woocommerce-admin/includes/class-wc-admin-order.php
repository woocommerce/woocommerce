<?php
/**
 * WC Admin Order
 *
 * WC Admin Order class that adds some functionality on top of general WooCommerce WC_Order.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Order class.
 */
class WC_Admin_Order extends WC_Order {

	/**
	 * Holds refund amounts and quantities for the order.
	 *
	 * @var void|array
	 */
	protected $refunded_line_items;

	/**
	 * Add filter(s) required to hook WC_Admin_Order class to substitute WC_Order.
	 */
	public static function add_filters() {
		add_filter( 'woocommerce_order_class', array( __CLASS__, 'order_class_name' ), 10, 3 );
	}

	/**
	 * Filter function to swap class WC_Order for WC_Admin_Order in cases when it's suitable.
	 *
	 * @param string $classname Name of the class to be created.
	 * @param string $order_type Type of order object to be created.
	 * @param number $order_id Order id to create.
	 *
	 * @return string
	 */
	public static function order_class_name( $classname, $order_type, $order_id ) {
		if ( 'WC_Order' === $classname ) {
			return 'WC_Admin_Order';
		} else {
			return $classname;
		}
	}

	/**
	 * Calculate shipping amount for line item/product as a total shipping amount ratio based on quantity.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return float|int
	 */
	public function get_item_shipping_amount( $item ) {
		// Shipping amount loosely based on woocommerce code in includes/admin/meta-boxes/views/html-order-item(s).php
		// distributed simply based on number of line items.
		$quantity_refunded = $this->get_item_quantity_refunded( $item );
		$product_qty       = $item->get_quantity( 'edit' ) - $quantity_refunded;

		$order_items = $this->get_item_count();
		if ( 0 === $order_items ) {
			return 0;
		}

		$refunded = $this->get_total_shipping_refunded();
		if ( $refunded > 0 ) {
			$total_shipping_amount = $this->get_shipping_total() - $refunded;
		} else {
			$total_shipping_amount = $this->get_shipping_total();
		}

		return $total_shipping_amount / $order_items * $product_qty;
	}

	/**
	 * Save refund amounts and quantities for the order in an array for later use in calculations.
	 */
	protected function set_order_refund_items() {
		if ( ! isset( $this->refunded_line_items ) ) {
			$refunds             = $this->get_refunds();
			$refunded_line_items = array();
			foreach ( $refunds as $refund ) {
				foreach ( $refund->get_items() as $refunded_item ) {
					$line_item_id = wc_get_order_item_meta( $refunded_item->get_id(), '_refunded_item_id', true );
					if ( ! isset( $refunded_line_items[ $line_item_id ] ) ) {
						$refunded_line_items[ $line_item_id ]['quantity'] = 0;
						$refunded_line_items[ $line_item_id ]['subtotal'] = 0;
					}
					$refunded_line_items[ $line_item_id ]['quantity'] += absint( $refunded_item['quantity'] );
					$refunded_line_items[ $line_item_id ]['subtotal'] += abs( $refunded_item['subtotal'] );
				}
			}
			$this->refunded_line_items = $refunded_line_items;
		}
	}

	/**
	 * Get quantity refunded for the line item.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return int
	 */
	public function get_item_quantity_refunded( $item ) {
		$this->set_order_refund_items();
		$order_item_id = $item->get_id();

		return isset( $this->refunded_line_items[ $order_item_id ] ) ? $this->refunded_line_items[ $order_item_id ]['quantity'] : 0;
	}

	/**
	 * Get amount refunded for the line item.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return int
	 */
	public function get_item_amount_refunded( $item ) {
		$this->set_order_refund_items();
		$order_item_id = $item->get_id();

		return isset( $this->refunded_line_items[ $order_item_id ] ) ? $this->refunded_line_items[ $order_item_id ]['subtotal'] : 0;
	}

	/**
	 * Get item quantity minus refunded quantity for the line item.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return int
	 */
	public function get_item_quantity_minus_refunded( $item ) {
		return $item->get_quantity( 'edit' ) - $this->get_item_quantity_refunded( $item );
	}

	/**
	 * Calculate shipping tax amount for line item/product as a total shipping tax amount ratio based on quantity.
	 *
	 * Loosely based on code in includes/admin/meta-boxes/views/html-order-item(s).php.
	 *
	 * @todo: if WC is currently not tax enabled, but it was before (or vice versa), would this work correctly?
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return float|int
	 */
	public function get_item_shipping_tax_amount( $item ) {
		$order_items = $this->get_item_count();
		if ( 0 === $order_items ) {
			return 0;
		}

		$quantity_refunded         = $this->get_item_quantity_refunded( $item );
		$product_qty               = $item->get_quantity( 'edit' ) - $quantity_refunded;
		$order_taxes               = $this->get_taxes();
		$line_items_shipping       = $this->get_items( 'shipping' );
		$total_shipping_tax_amount = 0;
		foreach ( $line_items_shipping as $item_id => $shipping_item ) {
			$tax_data = $shipping_item->get_taxes();
			if ( $tax_data ) {
				foreach ( $order_taxes as $tax_item ) {
					$tax_item_id    = $tax_item->get_rate_id();
					$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
					$refunded       = $this->get_tax_refunded_for_item( $item_id, $tax_item_id, 'shipping' );
					if ( $refunded ) {
						$total_shipping_tax_amount += $tax_item_total - $refunded;
					} else {
						$total_shipping_tax_amount += $tax_item_total;
					}
				}
			}
		}
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
}
