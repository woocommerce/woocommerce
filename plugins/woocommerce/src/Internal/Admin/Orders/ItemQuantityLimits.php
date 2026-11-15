<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Orders;

use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Admin-side rules for the quantities an order line item accepts.
 *
 * The admin order editor renders quantity inputs with a minimum of 0, so
 * merchants cannot enter negative quantities. Orders created through the
 * REST API or by extensions may already contain negative quantities, so for
 * existing items the minimum is floored at the stored quantity to keep those
 * orders editable.
 *
 * Covers product line items only: fee and shipping lines have no quantity
 * input in the admin editor, and posted quantities for them are ignored.
 */
class ItemQuantityLimits {

	/**
	 * Get the minimum quantity accepted for an existing order item in the admin editor.
	 *
	 * @since 11.2.0
	 * @param WC_Order_Item         $item    Line item being edited.
	 * @param WC_Product|false|null $product The item's product when the caller already
	 *                                       resolved it; null to resolve it here.
	 * @return string Numeric string, filtered through 'woocommerce_quantity_input_min_admin'.
	 */
	public function get_quantity_input_min( WC_Order_Item $item, $product = null ): string {
		if ( null === $product ) {
			$product = $item instanceof WC_Order_Item_Product ? $item->get_product() : false;
		}

		$default = (string) min( 0, (float) $item->get_quantity() );

		/**
		 * This filter is documented in includes/admin/meta-boxes/views/html-order-item.php
		 *
		 * @since 5.8.0
		 */
		$min = apply_filters( 'woocommerce_quantity_input_min_admin', $default, $product, 'edit' );

		// A callback can return anything; fall back to the default on non-numeric values.
		return is_numeric( $min ) ? (string) $min : $default;
	}

	/**
	 * Validate the quantity requested for a product being added to an order.
	 *
	 * @since 11.2.0
	 * @param float      $qty     Requested quantity.
	 * @param WC_Product $product Product being added.
	 * @return void
	 * @throws \Exception When the quantity is below the allowed minimum.
	 */
	public function validate_new_item_quantity( float $qty, WC_Product $product ): void {
		/**
		 * This filter is documented in includes/admin/meta-boxes/views/html-order-item.php
		 *
		 * @since 5.8.0
		 */
		$min = apply_filters( 'woocommerce_quantity_input_min_admin', '0', $product, 'add' );

		// A callback can return anything; fall back to the default on non-numeric values.
		$min = is_numeric( $min ) ? (float) $min : 0.0;

		if ( $qty < $min ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- below_min_exception() strips and decodes the message for a JS alert.
			throw $this->below_min_exception( $product->get_name(), $min );
		}
	}

	/**
	 * Validate the order_item_qty values posted by the admin order items screen.
	 *
	 * Item ids that do not belong to the given order are ignored.
	 *
	 * @since 11.2.0
	 * @param WC_Order $order The order the posted items belong to.
	 * @param array    $items Posted items, as parsed from the serialized form data
	 *                        (the same shape wc_save_order_items receives).
	 * @return void
	 * @throws \Exception When a quantity is below the item's allowed minimum.
	 */
	public function validate_posted_item_quantities( WC_Order $order, array $items ): void {
		if ( empty( $items['order_item_qty'] ) || ! is_array( $items['order_item_qty'] ) ) {
			return;
		}

		$has_min_filter = has_filter( 'woocommerce_quantity_input_min_admin' );
		$order_items    = null;

		foreach ( $items['order_item_qty'] as $item_id => $posted_qty ) {
			$qty = (float) wc_stock_amount( wp_unslash( $posted_qty ) );

			// Without a filter the minimum is min( 0, stored quantity ), which is
			// never above 0, so a non-negative quantity cannot fail: skip the
			// per-item and product lookups on this hot path.
			if ( $qty >= 0 && ! $has_min_filter ) {
				continue;
			}

			if ( null === $order_items ) {
				$order_items = $order->get_items();
			}

			$item = $order_items[ absint( $item_id ) ] ?? null;

			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$min = (float) $this->get_quantity_input_min( $item );

			if ( $qty < $min ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- below_min_exception() strips and decodes the message for a JS alert.
				throw $this->below_min_exception( $item->get_name(), $min );
			}
		}
	}

	/**
	 * Build the exception for a quantity below the allowed minimum.
	 *
	 * The message is plain text destined for a JS alert, never rendered as
	 * HTML: entities are decoded first so stored names read naturally, then
	 * any resulting markup is stripped. The name-embedding exceptions in the
	 * AJAX handlers apply the same treatment.
	 *
	 * @since 11.2.0
	 * @param string $name Product or order item name.
	 * @param float  $min  Minimum accepted quantity.
	 * @return \Exception
	 */
	private function below_min_exception( string $name, float $min ): \Exception {
		return new \Exception(
			wp_strip_all_tags(
				html_entity_decode(
					sprintf(
						/* translators: 1: product or order item name, 2: minimum quantity accepted */
						__( 'The quantity of "%1$s" must be %2$s or higher.', 'woocommerce' ),
						$name,
						wc_format_localized_decimal( (string) $min )
					),
					ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
				)
			)
		);
	}
}
