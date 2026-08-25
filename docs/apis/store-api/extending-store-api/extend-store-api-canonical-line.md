# Marking a differentiated cart line as canonical

## The problem

Every line in the Store API cart-item response carries an `is_canonical_product_line` boolean: `true` when the line is the canonical line for its product — the single line a configuration-free add of the product (or product + variation) would be merged into — and `false` when the line's identity was differentiated by extra cart item data. Core computes the default from cart-key identity: a line is canonical when its stored key matches the key a plain add (with no extra `cart_item_data`) would produce.

If your extension intercepts a product's plain adds — for example, a bundle that stamps its container line with `cart_item_data` so the line is never cart-key-identical to a plain add — the default heuristic never marks your differentiated line canonical, so clients treat the line as if a plain line were missing.

## The solution

The `woocommerce_store_api_cart_item_is_canonical_product_line` filter lets an extension override that default for the lines it manages. It fires once per line, after core has computed the default, with two arguments: the core-computed default — derived from cart-key identity via `CartItemUtils::is_standalone_line()` — and the cart item array.

Return `true` to mark the line canonical for your own purposes; return the incoming `$is_canonical` value to leave the default untouched. A non-boolean return is discarded in favor of the core-computed default, and the field itself is readonly: it is computed server-side per response, clients cannot write it, and the filter is the only way to change it.

The hook's full description and parameters live in its [hooks reference entry](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_store_api_cart_item_is_canonical_product_line); this guide covers only the pattern.

## Basic usage

The recognition callback is the heart of the pattern: it checks for the marker your extension stamps on its differentiated line, returns `true` when the marker is present, and passes the incoming value through otherwise.

```php
add_filter(
	'woocommerce_store_api_cart_item_is_canonical_product_line',
	function ( $is_canonical, $cart_item ) {
		if ( isset( $cart_item['_bundle_container'] ) ) {
			return true;
		}

		return $is_canonical;
	},
	10,
	2
);
```

The marker is `_bundle_container`: an underscore-prefixed key stays out of customer-visible item data (see "Things to consider" below). The other half of the loop — stamping that marker onto the line when the bundle is added to the cart — is shown in "Putting it all together".

## Things to consider

### The marker stays out of customer-visible item data

Cart item data keys are exposed in customer-visible places, but keys starting with an underscore are skipped there: the marker remains internal to the line while still participating in the line's identity.

### The field is readonly

`is_canonical_product_line` is a readonly response field: clients cannot set it, and nothing else can write it. The filter is the only way to influence the value an extension's line carries.

## API Definition

| Filter | Signature |
| ------ | --------- |
| `woocommerce_store_api_cart_item_is_canonical_product_line` | `apply_filters( 'woocommerce_store_api_cart_item_is_canonical_product_line', bool $is_canonical, array $cart_item )` |

- `$is_canonical` — the core-computed default: whether the line is canonical for its product.
- `$cart_item` — the cart item array; the extension's marker, when present, is a key of this array.

The full description and parameter table are in the [hooks reference](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_store_api_cart_item_is_canonical_product_line).

## Putting it all together

The full loop has two halves: stamp the marker on the bundle's container line when it is added to the cart, then recognize the marker in the filter. Pasted into your extension, with the placeholder bundle condition adapted to your own detection, this marks the bundle's differentiated line canonical:

```php
<?php
/**
 * Mark the extension's bundle container line canonical.
 */

// Half one: stamp the marker on the bundle's container line.
add_filter(
	'woocommerce_add_cart_item_data',
	function ( $cart_item_data, $product_id, $variation_id, $quantity ) {
		// Placeholder bundle condition: replace `false` with your own
		// detection of when the product (or variation) being added is your
		// bundle's container. The real detection is specific to your
		// extension, so it is not invented here.
		$is_bundle_container = false;

		if ( $is_bundle_container ) {
			$cart_item_data['_bundle_container'] = true;
		}

		return $cart_item_data;
	},
	10,
	4
);

// Half two: recognize the marker and mark the line canonical.
add_filter(
	'woocommerce_store_api_cart_item_is_canonical_product_line',
	function ( $is_canonical, $cart_item ) {
		if ( isset( $cart_item['_bundle_container'] ) ) {
			return true;
		}

		return $is_canonical;
	},
	10,
	2
);
```

The `woocommerce_add_cart_item_data` filter's own [hooks reference entry](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_add_cart_item_data) documents its parameters, so they are not repeated here; the only part of the snippet you need to adapt is the placeholder bundle condition.

This pattern is covered end-to-end by the blocks e2e suite via the `canonical-line-filter` test plugin (`plugins/woocommerce/tests/e2e/test-plugins/blocks/canonical-line-filter.php`), which stands in for an extension in the tests.
