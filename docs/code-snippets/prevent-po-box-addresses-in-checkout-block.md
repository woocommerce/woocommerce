---
post_title: Prevent PO Box addresses in the Checkout block
sidebar_label: Prevent PO Box addresses in Checkout block
---

# Prevent PO Box addresses in the Checkout block

The Checkout block uses the Store API checkout flow, so shortcode checkout hooks such as `woocommerce_after_checkout_validation` do not run there. To validate orders submitted through the Checkout block, use the `woocommerce_checkout_validate_order_before_payment` hook.

The following snippet checks the shipping address when one exists, and falls back to billing details when checkout does not include a separate shipping address. Add it with the Code Snippets plugin and set it to run everywhere.

```php
/**
 * Prevent Checkout block orders from using PO Box addresses.
 *
 * @param WC_Order $order  Checkout order.
 * @param WP_Error $errors Validation errors.
 */
function wc_prevent_checkout_block_po_box_addresses( $order, $errors ) {
	$address = $order->has_shipping_address()
		? $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2()
		: $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();

	$postcode = $order->has_shipping_address()
		? $order->get_shipping_postcode()
		: $order->get_billing_postcode();

	$normalized_address = strtolower( str_replace( array( ' ', '.', ',' ), '', $address . ' ' . $postcode ) );

	if ( false !== strpos( $normalized_address, 'pobox' ) ) {
		$errors->add(
			'po_box_address',
			__( 'Sorry, we cannot ship to PO Box addresses.', 'woocommerce' )
		);
	}
}
add_action( 'woocommerce_checkout_validate_order_before_payment', 'wc_prevent_checkout_block_po_box_addresses', 10, 2 );
```

For example, the snippet blocks address formats such as `PO Box 123`, `P.O. Box 123`, and `POBOX 123`.
