# Marking a cart item as a child of another cart item

If your extension adds cart items that belong to another cart item, such as the contents of a bundle or a product add-on, you can mark them as children of that parent item. Store API cart-item responses then include the parent's key in the readonly `parent_item_key` field.

Blocks use this field to treat child items as part of their parent. For example, the product button's "X in cart" count leaves child items out, so adding a bundle doesn't make the products inside it look like they were added on their own.

WooCommerce never marks an item as a child by itself. Your extension decides which items are children.

## Declaring a parent

Use the `woocommerce_store_api_cart_item_parent_item_key` filter. It runs for each cart item in a Store API response and receives:

- `$parent_item_key`: `null`, or the value returned by an earlier callback.
- `$cart_item`: the cart item array.
- `$cart_item_key`: the cart item key.

For a child item your extension added, return the parent's cart item key. This is the key that `WC()->cart->add_to_cart()` returned when the parent was added, not a product ID. Your extension needs to store it, usually in the child's cart item data.

For any other item, return `$parent_item_key` unchanged so you don't erase a parent declared by another extension.

See the [generated filter reference](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_store_api_cart_item_parent_item_key) for the full signature.

## Example

This example adds a parent item, stores its key in the child's cart item data, and returns that key from the filter.

```php
<?php
const MY_EXTENSION_PARENT_ITEM_KEY = '_my_extension_parent_item_key';

function my_extension_add_parent_and_child( int $parent_product_id, int $child_product_id ): void {
	$parent_item_key = WC()->cart->add_to_cart( $parent_product_id, 1 );

	if ( ! $parent_item_key ) {
		return;
	}

	WC()->cart->add_to_cart(
		$child_product_id,
		1,
		0,
		array(),
		array( MY_EXTENSION_PARENT_ITEM_KEY => $parent_item_key )
	);
}

add_filter(
	'woocommerce_store_api_cart_item_parent_item_key',
	function ( $parent_item_key, $cart_item ) {
		return $cart_item[ MY_EXTENSION_PARENT_ITEM_KEY ] ?? $parent_item_key;
	},
	10,
	2
);
```

If the parent and child are added in separate requests, store the parent key somewhere your extension can read it later, such as the customer session, and add it to the child's cart item data when you add the child.

## When the parent leaves the cart

WooCommerce only returns a parent key while that parent item is in the cart. If the shopper removes the parent, or it's dropped from the cart, the child's `parent_item_key` becomes `null`. The child stays in the cart and counts as a standalone item again.

You don't need to clean anything up. Your filter can keep returning the stored key, and WooCommerce handles the rest.

## Related documentation

- [Available extensible endpoints](./available-endpoints-to-extend.md#cart-items) explains the Store API cart-item extension context.
- [Exposing your data](./extend-store-api-add-data.md) explains how to add extension-owned data to Store API responses.
