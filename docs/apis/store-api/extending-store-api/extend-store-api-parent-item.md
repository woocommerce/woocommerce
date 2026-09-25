# Marking a cart item as a child of another cart item

A Store API cart-item response can identify a child cart item with the readonly `parent_item_key` field. The field contains the key of the parent cart item, or `null` when the cart item has no declared parent. This guide shows how an extension can retain a parent key while adding a child cart item and expose that relationship in the response.

The PHP/Interactivity API ProductButton, including the button in Add to Cart with Options, sums quantities across matching cart lines without a declared parent. A simple product matches by product ID; a shopper-selected variation matches its variation ID and equivalent selected attributes. Ordinary item-data lines still count unless declared as children. If only child lines match, the button shows its usual add text. An unselected variable parent does not aggregate the counts of its variations.

For an unchanged cart snapshot, the server-rendered and hydrated button counts agree when both target the same product and selection, including a directly rendered concrete variation. A default or URL-driven variation selected only by the client after hydration may change the button text without indicating a disagreement about child lines.

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

- `$parent_item_key` — initially `null`; a later callback receives the value returned by the previous callback.
- `$cart_item` — the raw cart item array.
- `$cart_item_key` — the current cart item key as a string.

Return a non-empty string containing the stored parent cart item key for a child your extension declares. If your callback has no parent key of its own for a line, return the incoming `$parent_item_key` unchanged. Returning `null` instead can erase another extension's earlier declaration, causing that child to count as a standalone line. The final value is emitted only if it is `null` or a non-empty string; an empty string or any other type becomes `null`. Core neither infers parentage from item data or cart keys nor checks whether a returned parent key exists in the cart.

The field is readonly, cart-item response data, not a request input. It does not change cart-line identity: a keyless add still sends an `add-item` quantity delta and leaves line selection to the server, even if only a child line exists. An explicit-key quantity change still updates that exact line with an absolute quantity, without sending `parent_item_key`. Mini-Cart totals still include children, and the legacy React ProductButton keeps its existing count.

See the [generated filter reference](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_store_api_cart_item_parent_item_key) for the full signature and parameter details.

## Example

The example adds a parent cart item, captures the actual key returned by `WC()->cart->add_to_cart()`, and stores that key in extension-owned data when adding the child cart item. WooCommerce processes this cart item data before generating the child cart item key. The response filter then returns the stored key for this extension's child and passes through the incoming `$parent_item_key` for lines it does not declare as children.

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

If the parent and child are added in separate requests, the extension can persist the returned parent key in its own session or other state, then add it to the child cart item data before the child is added. The filter should still return the stored key for its own child and pass through the incoming `$parent_item_key` when the extension has no parent to declare for a line.

## Related documentation

- [Available extensible endpoints](./available-endpoints-to-extend.md#cart-items) explains the Store API cart-item extension context.
- [Exposing your data](./extend-store-api-add-data.md) explains how to add extension-owned data to Store API responses.
