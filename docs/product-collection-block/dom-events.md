# Product Collection - DOM Events <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [WooCommerce core events in WooCommerce Blocks](#woocommerce-core-events-in-woocommerce-blocks)
-   [WooCommerce Blocks events](#woocommerce-blocks-events)
    -   [`wc-blocks_product_list_rendered`](#wc-blocks_product_list_rendered)

## WooCommerce core events in WooCommerce Blocks

WooCommerce core uses jQuery events to trigger and listen to certain events, like when a product is added or removed from the cart. In WooCommerce Blocks, we moved away from using jQuery, but we still need to listen to those events. To achieve that, we have a utility named [`translatejQueryEventToNative()`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/3f7c3e517d7bf13008a22d0c2eb89434a9c35ae7/assets/js/base/utils/legacy-events.ts#L79-L106) that listens to those jQuery events, and every time one is triggered, it triggers an associated DOM native event (with the `wc-blocks_` prefix).

## WooCommerce Blocks events

### `wc-blocks_product_list_rendered`

This event is triggered when Product Collection block was rendered or re-rendered (e.g. due to page change).

#### `detail` parameters

| Parameter          | Type    | Default value | Description                                                                                                                                                                                                                                                                                                                                                                                   |
| ------------------ | ------- | ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `collection` | string | `''`       | Collection type. Core collections can be of type: `woocommerce/product-collection/product-catalog`, `woocommerce/product-collection/best-sellers`, `woocommerce/product-collection/featured`, `woocommerce/product-collection/new-arrivals`, `woocommerce/product-collection/on-sale`, `woocommerce/product-collection/top-rated`. For custom collection it will hold their name. |
