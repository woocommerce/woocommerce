# DOM Events <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [WooCommerce core events in WooCommerce Blocks](#woocommerce-core-events-in-woocommerce-blocks)
-   [WooCommerce Blocks events](#woocommerce-blocks-events)
    -   [`wc-blocks_adding_to_cart`](#wc-blocks_adding_to_cart)
    -   [`wc-blocks_added_to_cart`](#wc-blocks_added_to_cart)
        -   [`detail` parameters:](#detail-parameters)
    -   [`wc-blocks_removed_from_cart`](#wc-blocks_removed_from_cart)

Some blocks need to react to certain events in order to display the most up to date data or behave in a certain way. That's the case of the Cart block, for example, that must listen to 'add to cart' events in order to update the cart contents; or the Mini-Cart block, that gets opened every time a product is added to the cart.

## WooCommerce core events in WooCommerce Blocks

WooCommerce core uses jQuery events to trigger and listen to certain events, like when a product is added or removed from the cart. In WooCommerce Blocks, we moved away from using jQuery, but we still need to listen to those events. To achieve that, we have a utility named [`translatejQueryEventToNative()`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/3f7c3e517d7bf13008a22d0c2eb89434a9c35ae7/assets/js/base/utils/legacy-events.ts#L79-L106) that listens to those jQuery events, and every time one is triggered, it triggers an associated DOM native event (with the `wc-blocks_` prefix).

## WooCommerce Blocks events

### `wc-blocks_adding_to_cart`

This event is the equivalent to the jQuery event `adding_to_cart` triggered by WooCommerce core. It indicates that the process of adding a product to the cart was sent to the server, but there is still no indication on whether the product was successfully added or not.

_Example usage in WC Blocks:_ Mini-Cart block listens to this event to append its dependencies.

### `wc-blocks_added_to_cart`

This event is the equivalent to the jQuery event `added_to_cart` triggered by WooCommerce core. It indicates that the process of adding a product to the cart has finished with success.

_Example usage in WC Blocks:_ Cart and Mini-Cart blocks (via the `useStoreCart()` hook) listen to this event to know if they need to update their contents.

#### `detail` parameters

| Parameter          | Type    | Default value | Description                                                                                                                                                                                                                                                                                                                                                                                   |
| ------------------ | ------- | ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `preserveCartData` | boolean | `false`       | Whether Cart data in the store should be preserved. By default, it's `false` so any `wc-blocks_added_to_cart` event will invalidate cart data and blocks listening to it will refetch cart data again. However, if the code triggering the event already updates the store (ie: All Products block), it can set `preserveCartData: true` to avoid the other blocks refetching the data again. |

### `wc-blocks_removed_from_cart`

This event is the equivalent to the jQuery event `removed_from_cart` triggered by WooCommerce core. It indicates that a product has been removed from the cart.

_Example usage in WC Blocks:_ Cart and Mini-Cart blocks (via the `useStoreCart()` hook) listen to this event to know if they need to update their contents.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-block/dom-events.md)

<!-- /FEEDBACK -->

