<?php
/**
 * Order line item checks for REST write requests.
 *
 * @package WooCommerce\Internal\RestApi
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RestApi;

/**
 * Line item checks shared by the order controllers.
 *
 * The checks use the controller's own get_product_id() and item_is_null(), so subclass overrides still apply.
 */
trait OrderLineItemValidationTrait {

	/**
	 * Checks whether a line item is a custom item: one with no product, but a name and a total.
	 *
	 * @param array $line_item Line item data from the request.
	 * @return bool
	 */
	private function is_custom_line_item( $line_item ): bool {
		$has_product_reference = ! empty( $line_item['product_id'] ) || ! empty( $line_item['variation_id'] ) || ! empty( $line_item['sku'] );
		$has_name              = isset( $line_item['name'] ) && is_string( $line_item['name'] ) && '' !== trim( $line_item['name'] );
		$has_total             = isset( $line_item['total'] ) && is_numeric( wc_format_decimal( $line_item['total'] ) );

		return ! $has_product_reference && $has_name && $has_total;
	}

	/**
	 * Checks that every new line item, other than custom items, points to an existing product, before any item is saved.
	 * Otherwise a bad item fails the request after earlier items were already saved, along with any stock changes.
	 *
	 * @param mixed $line_items Line items from the request.
	 * @return void
	 * @throws \WC_REST_Exception When a new line item has no product reference, or its product doesn't exist.
	 */
	private function check_new_line_item_products( $line_items ): void {
		if ( ! is_array( $line_items ) ) {
			return;
		}

		foreach ( $line_items as $posted ) {
			if ( ! is_array( $posted ) ) {
				continue;
			}

			// Existing items can point to products deleted since the order was placed, and must stay editable.
			if ( ! empty( $posted['id'] ) ) {
				continue;
			}

			// Null and zero-quantity items are removals.
			if ( $this->item_is_null( $posted ) ) {
				continue;
			}

			if ( isset( $posted['quantity'] ) && 0 === $posted['quantity'] ) {
				continue;
			}

			$this->check_line_item_product( $posted, wc_get_product( $this->get_product_id( $posted ) ) );
		}
	}

	/**
	 * Checks that a new line item points to an existing product, unless it's a custom item.
	 *
	 * @param array                  $line_item Line item data from the request.
	 * @param \WC_Product|false|null $product   The product the line item resolves to.
	 * @return void
	 * @throws \WC_REST_Exception When the product doesn't exist.
	 */
	private function check_line_item_product( $line_item, $product ): void {
		if ( $this->is_custom_line_item( $line_item ) ) {
			return;
		}

		if ( $product && $product->exists() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- REST API JSON response, not HTML.
		throw new \WC_REST_Exception( 'woocommerce_rest_invalid_product_id', __( 'Invalid product ID.', 'woocommerce' ), 400 );
	}
}
