---
post_title: Choosing the default payment method
sidebar_label: Default payment method

---

# Choosing the default payment method

By default, the Checkout block loads with a payment method already selected. WooCommerce picks the first of these that exists: the customer's default saved card, the method they picked earlier in the same session, or the first enabled gateway. Some stores have requirements on whether a method may be preselected, or on which kind: for example, that the shopper must choose one themselves, or that a card is never selected for them.

The `woocommerce_default_payment_method` filter lets a store decide what the Checkout block starts with when the shopper hasn't picked a method yet. It works with gateway ids. It is not the default saved card a customer picks in My Account: that card only decides which gateway WooCommerce would otherwise preselect.

## The filter

```php
apply_filters( 'woocommerce_default_payment_method', $payment_method, $available_gateways );
```

| Argument | Type | Description |
| --- | --- | --- |
| `$payment_method` | `string` | The gateway id WooCommerce would preselect: the customer's default saved card's gateway, the method remembered in the session, or the first enabled gateway, or `''` when there is none. |
| `$available_gateways` | `array` | The gateways available for the current cart, keyed by gateway id. |

Return a gateway id from `$available_gateways` to preselect it, or an empty string to select nothing. Any other value selects nothing. Returning `$payment_method` unchanged keeps WooCommerce's answer as it is.

Attach the callback by `init` at the latest, so it is in place for every Store API checkout request.

## What changes for shoppers

Once a callback is attached:

- The Checkout block keeps the shopper's own choice while that gateway is enabled, and it survives a reload. The filter decides what the block starts with before they choose.
- The block follows the server. When the filter returns nothing, nothing is selected, and the block doesn't fall back to the first method. When it returns a gateway, that gateway is selected as it would be today, including the customer's default saved card for it.

Without a callback, nothing changes.

## Examples

Never preselect a payment method:

```php
add_filter( 'woocommerce_default_payment_method', '__return_empty_string' );
```

Never preselect a credit card. WooCommerce has no "is a credit card" flag on gateways, so the callback names the card gateway. A saved card is always a card, and the customer's default one is what WooCommerce would preselect, so the callback checks for it too:

```php
add_filter(
	'woocommerce_default_payment_method',
	function ( $payment_method ) {
		if ( 'stripe' === $payment_method ) {
			return '';
		}
		$default_token = WC_Payment_Tokens::get_customer_default_token( get_current_user_id() );
		if ( $default_token && $default_token->get_gateway_id() === $payment_method ) {
			return '';
		}
		return $payment_method;
	}
);
```

## Forgetting the choice after each purchase

WooCommerce remembers the shopper's choice in the session until it expires, so their next purchase in the same session starts with it. A store that must have the shopper choose anew for each purchase can clear it when the cart becomes empty. Both hooks are needed: removing the last item by hand doesn't fire `woocommerce_cart_emptied`.

```php
add_action( 'woocommerce_cart_emptied', 'myshop_forget_payment_choice' );
add_action(
	'woocommerce_cart_item_removed',
	function ( $cart_item_key, $cart ) {
		if ( $cart->is_empty() ) {
			myshop_forget_payment_choice();
		}
	},
	10,
	2
);

function myshop_forget_payment_choice() {
	if ( WC()->session instanceof WC_Session ) {
		WC()->session->set( 'chosen_payment_method', null );
	}
}
```

## Things to keep in mind

- The callback must give the same answer for the same shopper on every request. The checkout page and the Store API both ask it.
- Logic based on the shopper's IP address needs the checkout page excluded from full-page caching, or one visitor's answer is served to everyone.
- The filter only covers the Checkout block. The classic checkout shortcode and the pay-for-order page still preselect a gateway.
