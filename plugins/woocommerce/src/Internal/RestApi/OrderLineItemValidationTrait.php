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
 * A trait rather than part of OrderLineItemValidator, because the checks go through the controller's own
 * get_product_id() and item_is_null(), so subclasses that override them still apply.
 */
trait OrderLineItemValidationTrait {

	/**
	 * Checks that every new line item, other than custom items, points to an existing product, before any item is saved.
	 * Otherwise a bad item fails the request after earlier items were already saved, along with any stock changes.
	 *
	 * @param mixed $line_items Line items from the request.
	 * @return void
	 * @throws \WC_REST_Exception When a new line item has no product reference, or its product doesn't exist.
	 */
	private function check_new_line_item_products( $line_items ) {
		if ( ! is_array( $line_items ) ) {
			return;
		}

		foreach ( $line_items as $posted ) {
			if ( ! is_array( $posted ) || ! empty( $posted['id'] ) || $this->item_is_null( $posted ) || ( isset( $posted['quantity'] ) && 0 === $posted['quantity'] ) || OrderLineItemValidator::is_custom_line_item( $posted ) ) {
				continue;
			}

			$product = wc_get_product( $this->get_product_id( $posted ) );
			if ( ! $product || ! $product->exists() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- REST API JSON response, not HTML.
				throw new \WC_REST_Exception( 'woocommerce_rest_invalid_product_id', __( 'Invalid product ID.', 'woocommerce' ), 400 );
			}
		}
	}
}
