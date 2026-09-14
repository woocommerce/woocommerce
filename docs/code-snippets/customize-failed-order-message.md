---
post_title: Customize the failed order message
sidebar_label: Customize the failed order message
---

# Customize the failed order message

WooCommerce displays a message on the order confirmation page when an order fails. In WooCommerce 11.2 and later, use the `woocommerce_thankyou_order_failed_text` filter to customize this message in both the classic and block-based order confirmation pages.

The filter receives the current message and the failed order, so the message can include order details:

When packaging this snippet in a plugin or theme, replace `your-text-domain` with the plugin or theme's text domain so its translation catalog can include the message.

```php
/**
 * Customize the message shown for failed orders.
 *
 * @param string   $message Current failed order message.
 * @param WC_Order $order   Failed order.
 * @return string
 */
function your_prefix_customize_failed_order_message( $message, $order ) {
	return sprintf(
		/* translators: %s: Order number. */
		__( 'We could not process order #%s. Please try again or contact us for help.', 'your-text-domain' ),
		esc_html( $order->get_order_number() )
	);
}
add_filter( 'woocommerce_thankyou_order_failed_text', 'your_prefix_customize_failed_order_message', 10, 2 );
```

The callback must return a string. Safe inline HTML is allowed and is sanitized before WooCommerce displays it.
