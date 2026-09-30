# Marking a cart item as a child of another cart item

A Store API cart-item response can identify a child cart item with the readonly `parent_item_key` field. A non-empty value declares a parent cart item key; `null` means no parent was declared, not necessarily that the line has no parent. The interactive ProductButton block (PHP-rendered with the Interactivity API), including the one in Add to Cart with Options, excludes declared child lines from its in-cart quantity; undeclared child lines that match its product still count. The legacy React ProductButton and Mini-Cart item count do not use this declaration. This guide shows how an extension can retain a parent key while adding a child cart item and expose that relationship in the response.

## Filter contract

The `woocommerce_store_api_cart_item_parent_item_key` filter runs once for each valid cart item while WooCommerce serializes a Store API cart response.

```php
apply_filters(
	'woocommerce_store_api_cart_item_parent_item_key',
	null,
	$cart_item,
	$cart_item_key
);
```

The filter receives the following arguments:

- `$parent_item_key` — initially `null`, or the value returned by an earlier callback on this filter.
- `$cart_item` — the raw cart item array.
- `$cart_item_key` — the current cart item key as a string.

Return a non-empty string containing the parent cart item key for a child cart item your extension owns. If your extension has no valid parent key for this line, return the incoming `$parent_item_key` unchanged so an earlier extension's declaration is not erased. WooCommerce normalizes the final value to `null` unless it is a non-empty string.

The field is readonly response data. Extensions opt in to declaring their own child lines; WooCommerce does not infer a parent from cart item data or cart keys. Core does not verify whether a returned parent key exists in the cart, so the extension must retain and return the correct key.

See the [generated filter reference](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_store_api_cart_item_parent_item_key) for the full signature and parameter details.

## Example

The example adds a parent cart item, captures the actual key returned by `WC()->cart->add_to_cart()`, and stores that key in extension-owned data when adding the child cart item. WooCommerce processes this cart item data before generating the child cart item key. The response filter returns that key for the child; otherwise, it passes through the incoming value.

```php
<?php
const MY_EXTENSION_PARENT_ITEM_KEY = '_my_extension_parent_item_key';

function my_extension_add_parent_and_child( int $parent_product_id, int $child_product_id ): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$parent_item_key = WC()->cart->add_to_cart( $parent_product_id, 1 );

	if ( ! is_string( $parent_item_key ) || '' === $parent_item_key ) {
		return;
	}

	// This data is available before WooCommerce generates the child cart item key.
	WC()->cart->add_to_cart(
		$child_product_id,
		1,
		0,
		array(),
		array(
			MY_EXTENSION_PARENT_ITEM_KEY => $parent_item_key,
		)
	);
}

add_filter(
	'woocommerce_store_api_cart_item_parent_item_key',
	function ( $parent_item_key, $cart_item, $cart_item_key ) {
		if ( ! is_array( $cart_item ) ) {
			return $parent_item_key;
		}

		$stored_parent_item_key = $cart_item[ MY_EXTENSION_PARENT_ITEM_KEY ] ?? null;

		return is_string( $stored_parent_item_key ) && '' !== $stored_parent_item_key
			? $stored_parent_item_key
			: $parent_item_key;
	},
	10,
	3
);
```

If the parent and child are added in separate requests, the extension can persist the returned parent key in its own session or other state, then add it to the child cart item data before the child is added. The filter should still return the valid stored key for its child and pass through `$parent_item_key` when it has no valid key of its own.

## Related documentation

- [Available extensible endpoints](./available-endpoints-to-extend.md#cart-items) explains the Store API cart-item extension context.
- [Exposing your data](./extend-store-api-add-data.md) explains how to add extension-owned data to Store API responses.
