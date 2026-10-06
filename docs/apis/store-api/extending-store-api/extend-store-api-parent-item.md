# Marking a cart item as a child of another cart item

A Store API cart-item response can identify a child cart item with the readonly `parent_item_key` field. A non-empty string value is the key of a parent line that is in the shopper's current cart. `null` means no parent was declared, or the declared parent is not in the cart; it does not necessarily mean the line has no parent. This guide shows how an extension can retain a parent key while adding a child cart item, expose that relationship in the response, and what happens when the parent later leaves the cart. For how the field affects the in-cart count on a product button, see [Interactive ProductButton](#interactive-productbutton).

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

Return a non-empty string containing the parent cart item key for a child cart item your extension owns. This must be the actual key of a line in the shopper's cart, not a product ID. Your extension is responsible for obtaining it: capture the key that `WC()->cart->add_to_cart()` returns when the parent is added, retain it in your own data, and return it from the callback. If your extension has no valid parent key for this line, return the incoming `$parent_item_key` unchanged so an earlier extension's declaration is not erased.

The filter's return value is a declaration; the `parent_item_key` response field is what WooCommerce emits from it. After the last callback runs, WooCommerce normalizes the final value to a `string|null`:

- A value that is not a non-empty string becomes `null`.
- A non-empty string is kept only when it equals the key of a line in the session cart (`WC()->cart`). Otherwise it becomes `null`, including when `WC()->cart` is not a `WC_Cart`.

The membership check looks only at whether a line with that key is in the cart:

- The parent's position in the cart does not matter.
- A key naming a line that declares a parent of its own is kept, and so is a key equal to the line's own key. WooCommerce does not detect self-references, chains or cycles.
- The check uses the shopper's session cart, not a cart passed in by the caller that is serializing the response.
- The check does not use the `woocommerce_get_cart_contents` filter, so it does not depend on which lines that filter makes visible.

The field is readonly response data. Extensions opt in to declaring their own child lines; WooCommerce does not infer a parent from cart item data or cart keys.

See the [generated filter reference](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_store_api_cart_item_parent_item_key) for the full signature and parameter details.

## When the parent leaves the cart

Your filter runs every time a cart item is serialized, and the membership check runs after it. A child line therefore follows its parent's presence in the cart, without any cleanup by your extension. This applies to every Store API response that carries cart items: `GET /wc/store/v1/cart`, `GET /wc/store/v1/cart/items`, `GET /wc/store/v1/cart/items/{key}`, and the cart returned by mutations, including `POST /wc/store/v1/cart/remove-item`.

- **Parent in the cart:** the child's `parent_item_key` is the parent's key. The parent's own field is `null` unless something declares a parent for it.
- **Declared key names no cart line:** the child's `parent_item_key` is `null`, and the child is a standalone line.
- **Parent removed:** the response to the request that removes the parent already has the child's `parent_item_key` as `null`. The child stays in the cart with its quantity.
- **Parent no longer purchasable:** when the session cart loads, a line whose product cannot be purchased is not restored. For example, a product moved to draft is dropped for a customer who cannot edit products. Responses built from then on show the child with `parent_item_key` as `null`; the child stays with its quantity.
- **Parent returns under the same key:** the child's key is emitted again, because the same lookup finds the line.

Apart from `parent_item_key`, the child line is what WooCommerce already produces for the same cart. The cart's totals are recalculated as usual when the parent leaves, so the child's totals can change; for example, a fixed-cart coupon is divided again among the remaining lines. WooCommerce does not remove orphaned children, and this check adds no logging.

For example, a parent `P` and its child `C` progress like this (other fields omitted):

```json
{ "items": [
    { "key": "<P key>", "parent_item_key": null, "quantity": 1 },
    { "key": "<C key>", "parent_item_key": "<P key>", "quantity": 1 }
] }
```

After `P` is removed, or dropped when the session loads:

```json
{ "items": [
    { "key": "<C key>", "parent_item_key": null, "quantity": 1 }
] }
```

If your callback instead returned a key that never named a cart line, `C` would carry `"parent_item_key": null` from the first response.

### Interactive ProductButton

The interactive ProductButton block (PHP-rendered with the Interactivity API), including the one in Add to Cart with Options, leaves child lines out of its in-cart quantity: it excludes lines whose response carries a parent key, and counts the lines for its product that have `parent_item_key` as `null`. Take a quantity-1 child `C` of product X whose parent line is in the cart:

- With `P` in the cart, the button for X reads "Add to cart", both in the server-rendered page and after hydration.
- When `C` is orphaned because its declared key names no cart line, or because `P` was dropped when the session loaded, the button reads "1 in cart" on first render and still after hydration.
- When the shopper removes `P` from the interactive Mini-Cart on a page that shows the button, the button reads "1 in cart" once the removal request has completed, without a reload. While the request is pending the button still shows the previous count.

The Mini-Cart item count and the legacy React ProductButton do not use this field, so the orphaning does not change them.

## Example

The example adds a parent cart item, captures the actual key returned by `WC()->cart->add_to_cart()`, and stores that key in extension-owned data when adding the child cart item. WooCommerce processes this cart item data before generating the child cart item key. The response filter follows the [Filter contract](#filter-contract), returning the stored key for the child. The key is emitted only while the parent is in the cart; see [When the parent leaves the cart](#when-the-parent-leaves-the-cart).

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

If the parent and child are added in separate requests, the extension can persist the returned parent key in its own session or other state, then add it to the child cart item data before the child is added. The filter itself follows the same [Filter contract](#filter-contract).

## Related documentation

- [Available extensible endpoints](./available-endpoints-to-extend.md#cart-items) explains the Store API cart-item extension context.
- [Exposing your data](./extend-store-api-add-data.md) explains how to add extension-owned data to Store API responses.
