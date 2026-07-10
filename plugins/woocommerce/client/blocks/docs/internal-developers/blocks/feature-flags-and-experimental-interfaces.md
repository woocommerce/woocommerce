# Feature Flags and Experimental Interfaces <!-- omit in toc -->

WooCommerce Blocks uses feature flags to control access to in-progress features and experimental prefixes to identify interfaces that can change without notice. These are separate concepts: a feature can be behind a flag without exposing an experimental interface, and an experimental interface can remain available regardless of a feature flag.

This document covers flags used by Blocks and WooCommerce-owned experimental interfaces in the Blocks and Store API code. It does not inventory experimental components, properties, or supports imported from WordPress packages. The source code linked below remains the source of truth.

## Feature flags

WooCommerce currently uses two feature-flag systems in Blocks:

- Build-configured flags are defined for each build environment in the [WooCommerce Admin configuration](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/client/admin/config) and checked with `Automattic\WooCommerce\Admin\Features\Features::is_enabled()`.
- Runtime feature definitions are registered in [`FeaturesController`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Features/FeaturesController.php), exposed on the **WooCommerce > Settings > Advanced > Features** screen when applicable, and checked with `FeaturesUtil::feature_is_enabled()`.

### Build-configured flags

| Flag | Defaults | Current Blocks usage |
| --- | --- | --- |
| `experimental-blocks` | Enabled in [development](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/admin/config/development.json) and disabled in [core builds](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/admin/config/core.json). | Exposed to the editor through [`isExperimentalBlocksEnabled()`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/settings/blocks/feature-flags.ts). It currently gates the **Disable product descriptions** editor control in Checkout Order Summary Cart Items and conditional checkout-field processing in the Store API. It does not determine which block scripts webpack builds or which general block types are registered. |
| `rest-api-v4` | Disabled in both development and core build configurations. | Exposed through [`isExperimentalWcRestApiV4Enabled()`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/settings/blocks/feature-flags.ts). It switches product entities from `/wc/v3/products` to `/wc/v4/products`, registers the settings entity, and enables the v4 product-data paths used by Product Price and Product Button in the editor. |

### Runtime feature flags

These flags are experimental, disabled by default, and registered in [`FeaturesController`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Features/FeaturesController.php).

| Flag | Current Blocks usage |
| --- | --- |
| `cart_save_for_later` | Registers the `woocommerce/saved-for-later` block through [`BlockTypesController`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/BlockTypesController.php), enables the related shopper-list APIs, and exposes the Save for later action in Cart when the block is present. |
| `product_wishlist` | Registers the `woocommerce/wishlist` and `woocommerce/add-to-wishlist-button` blocks and enables the related shopper-list APIs and My Account endpoint. |
| `wc-visual-attribute` | Enables the experimental `wc-visual` product attribute type for block themes. The Product Filters and Add to Cart + Options blocks read the resulting attribute data to display visual variation choices. |

The hidden, mature `cart_checkout_blocks` feature definition is a compatibility marker used to report extensions that declared compatibility with Cart and Checkout blocks. It does not enable or disable those blocks.

## Experimental interfaces

Names prefixed with `__experimental`, `experimental__`, or `Experimental` are unstable. Extensions using them must expect breaking changes and should migrate when a stable replacement is available.

### Current PHP hooks

| Interface | Type | Purpose |
| --- | --- | --- |
| `__experimental_woocommerce_blocks_add_data_attributes_to_namespace` | Filter | Adds block namespaces whose rendered markup should receive block and attribute `data-` attributes. See [`BlockTypesController`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/BlockTypesController.php). |
| `__experimental_woocommerce_blocks_add_data_attributes_to_block` | Filter | Adds individual blocks whose rendered markup should receive block and attribute `data-` attributes. See [`BlockTypesController`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/BlockTypesController.php). |
| `__experimental_woocommerce_blocks_payment_gateway_features_list` | Filter | Changes the features exposed by the PayPal Standard Blocks integration. See [`PayPal`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/Payments/Integrations/PayPal.php). |
| `__experimental_woocommerce_store_api_batch_request_methods` | Filter | Changes the HTTP methods allowed in Store API batch requests. See [`Batch`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/StoreApi/Routes/V1/Batch.php). |
| `__experimental_woocommerce_{$product_type}_add_to_cart_with_options_block_template_part` | Filter | Allows an extension to provide an Add to Cart + Options template part for a custom product type. See [`AddToCartWithOptions`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php). |

### Deprecated PHP interfaces

The following compatibility aliases still run but should not be used in new code.

| Deprecated interface | Stable replacement |
| --- | --- |
| `__experimental_woocommerce_blocks_register_checkout_field()` | `woocommerce_register_additional_checkout_field()` |
| `__experimental_woocommerce_blocks_sanitize_additional_field` | `woocommerce_sanitize_additional_field` |
| `__experimental_woocommerce_blocks_validate_additional_field` | `woocommerce_validate_additional_field` |
| `__experimental_woocommerce_blocks_validate_location_{$location}_fields` | `woocommerce_blocks_validate_location_{$location}_fields` |
| `__experimental_woocommerce_blocks_checkout_order_processed` | `woocommerce_store_api_checkout_order_processed` |
| `__experimental_woocommerce_blocks_checkout_update_order_meta` | `woocommerce_store_api_checkout_update_order_meta` |
| `__experimental_woocommerce_blocks_checkout_update_order_from_request` | `woocommerce_store_api_checkout_update_order_from_request` |

The checkout-field aliases are implemented in [`functions.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/Domain/Services/functions.php) and [`CheckoutFields`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/Domain/Services/CheckoutFields.php). The checkout-order aliases are emitted by the [Store API Checkout route](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/StoreApi/Routes/V1/Checkout.php) and [`CheckoutTrait`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/StoreApi/Utilities/CheckoutTrait.php).

### Store API parameters and properties

| Interface | Purpose |
| --- | --- |
| `__experimental_calc_totals` | Checkout request parameter that recalculates cart totals before validation. See the [Checkout route](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/StoreApi/Routes/V1/Checkout.php). |
| `__experimental_visual` | Product attribute terms request parameter that includes experimental visual swatch data. See [`ProductAttributeTerms`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/StoreApi/Routes/V1/ProductAttributeTerms.php). |
| `__experimental_woocommerce_blocks_hidden` | Cart item data property that overrides the `hidden` value used by Blocks. See [`CartItemSchema`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/StoreApi/Schemas/V1/CartItemSchema.php). |

### JavaScript methods

| Interface | Status and purpose |
| --- | --- |
| `__experimentalRegisterProductCollection` | Registers a Product Collection collection. It is experimental and can change without notice. See [`register-product-collection.tsx`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/blocks-registry/product-collection/register-product-collection.tsx). |
| `__experimentalDeRegisterPaymentMethod` | Deregisters a payment method. It is primarily used by tests. See [`registry.ts`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/blocks-registry/payment-methods/registry.ts). |
| `__experimentalDeRegisterExpressPaymentMethod` | Deregisters an express payment method. It is primarily used by tests. See [`registry.ts`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/blocks-registry/payment-methods/registry.ts). |
| `__experimentalRegisterCheckoutFilters` | Deprecated alias for `registerCheckoutFilters`. |
| `__experimentalApplyCheckoutFilter` | Deprecated alias for `applyCheckoutFilter`. |

The deprecated Checkout Filter aliases are implemented in the [`filter-registry`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/packages/checkout/filter-registry/index.ts).

### SlotFills

| Export | Internal slot name | Placement |
| --- | --- | --- |
| `ExperimentalOrderMeta` | `__experimentalOrderMeta` | Below the Checkout summary or above the Cart checkout button. |
| `ExperimentalOrderShippingPackages` | `__experimentalOrderShippingPackages` | Inside the shipping options shown by Cart and Checkout. |
| `ExperimentalOrderLocalPickupPackages` | `__experimentalOrderLocalPickupPackages` | Inside the Checkout pickup options. |
| `ExperimentalDiscountsMeta` | `__experimentalDiscountsMeta` | Below each discount in the Cart and Checkout totals. |

See the [available SlotFills](https://github.com/woocommerce/woocommerce/blob/trunk/docs/block-development/extensible-blocks/cart-and-checkout-blocks/available-slot-fills.md) and the [checkout components source](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/client/blocks/packages/checkout/components) for their current props and placement.

### Store events

[`useStoreEvents`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/base/context/hooks/use-store-events.ts) emits actions through `@wordpress/hooks`. Store events use the `experimental__woocommerce_blocks-` prefix.

| Action | Parameters |
| --- | --- |
| `experimental__woocommerce_blocks-cart-add-item` | `product` |
| `experimental__woocommerce_blocks-cart-view-link` | `product` |
| `experimental__woocommerce_blocks-cart-remove-item` | `product`, `quantity` |
| `experimental__woocommerce_blocks-cart-set-item-quantity` | `product`, `quantity` |
| `experimental__woocommerce_blocks-product-view-link` | `product` |
| `experimental__woocommerce_blocks-product-list-render` | `products`, `listName` |

Examples:

```js
wp.hooks.addAction(
	'experimental__woocommerce_blocks-cart-add-item',
	'plugin/namespace',
	( { product } ) => {
		console.log( `${ product.name } was added to the cart` );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-cart-set-item-quantity',
	'plugin/namespace',
	( { product, quantity } ) => {
		console.log( `${ product.name } quantity changed to ${ quantity }` );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-cart-remove-item',
	'plugin/namespace',
	( { product, quantity } ) => {
		console.log( `${ quantity } of ${ product.name } were removed` );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-product-view-link',
	'plugin/namespace',
	( { product } ) => {
		console.log( `${ product.name } view link was selected` );
	}
);
```

### Checkout events

Checkout events use the `experimental__woocommerce_blocks-checkout-` prefix. `dispatchCheckoutEvent()` adds the current `storeCart` value to every event payload in addition to the parameters listed below.

| Action | Additional parameters |
| --- | --- |
| `experimental__woocommerce_blocks-checkout-submit` | None |
| `experimental__woocommerce_blocks-checkout-set-selected-shipping-rate` | `shippingRateId` |
| `experimental__woocommerce_blocks-checkout-set-active-payment-method` | `paymentMethodSlug` |
| `experimental__woocommerce_blocks-checkout-render-checkout-form` | None |
| `experimental__woocommerce_blocks-checkout-set-email-address` | None |
| `experimental__woocommerce_blocks-checkout-set-shipping-address` | None |
| `experimental__woocommerce_blocks-checkout-set-billing-address` | None |

Examples:

```js
wp.hooks.addAction(
	'experimental__woocommerce_blocks-checkout-submit',
	'plugin/namespace',
	( { storeCart } ) => {
		console.log( 'The checkout form was submitted', storeCart );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-checkout-set-selected-shipping-rate',
	'plugin/namespace',
	( { shippingRateId, storeCart } ) => {
		console.log( `Selected shipping rate: ${ shippingRateId }`, storeCart );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-checkout-set-active-payment-method',
	'plugin/namespace',
	( { paymentMethodSlug, storeCart } ) => {
		console.log( `Selected payment method: ${ paymentMethodSlug }`, storeCart );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-checkout-render-checkout-form',
	'plugin/namespace',
	( { storeCart } ) => {
		console.log( 'The checkout form was rendered', storeCart );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-checkout-set-email-address',
	'plugin/namespace',
	( { storeCart } ) => {
		console.log( 'The email address changed', storeCart );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-checkout-set-shipping-address',
	'plugin/namespace',
	( { storeCart } ) => {
		console.log( 'The shipping address changed', storeCart );
	}
);

wp.hooks.addAction(
	'experimental__woocommerce_blocks-checkout-set-billing-address',
	'plugin/namespace',
	( { storeCart } ) => {
		console.log( 'The billing address changed', storeCart );
	}
);
```
