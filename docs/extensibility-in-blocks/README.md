---
category_title: Extensibility in Blocks 
category_slug: extensibility-in-blocks
post_title:  Extensibility in blocks
---


These documents are all dealing with extensibility in the various WooCommerce Blocks.

## Imports and dependency extration

The documentation in this section will use window globals in code examples, for example:

```js
const { registerCheckoutFilters } = window.wc.blocksCheckout;
```

However, if you're using `@woocommerce/dependency-extraction-webpack-plugin` for enhanced dependency management you can instead use ES module syntax:

```js
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
```

See <https://www.npmjs.com/package/@woocommerce/dependency-extraction-webpack-plugin> for more information.

## Hooks (actions and filters)

| Document                      | Description                                             |
| ----------------------------- | ------------------------------------------------------- |
| [Actions](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/hooks/actions.md) | Documentation covering action hooks on the server side. |
| [Filters](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/hooks/filters.md) | Documentation covering filter hooks on the server side. |
| [Migrated Hooks](/docs/cart-and-checkout-legacy-hooks/) | Documentation covering the migrated WooCommerce core hooks. |

## REST API

| Document                                                                                       | Description                                                         |
| ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| [Exposing your data in the Store API.](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-add-data.md)                 | Explains how you can add additional data to Store API endpoints.    |
| [Available endpoints to extend with ExtendSchema](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/rest-api/available-endpoints-to-extend.md) | A list of all available endpoints to extend.                        |
| [Available Formatters](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-formatters.md)                               | Available `Formatters` to format data for use in the Store API.     |
| [Updating the cart with the Store API](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-update-cart.md)              | Update the server-side cart following an action from the front-end. |

## Checkout Payment Methods

| Document                                                                               | Description                                                                                                 |
| -------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| [Checkout Flow and Events](/docs/cart-and-checkout-checkout-flow-and-events/)     | All about the checkout flow in the checkout block and the various emitted events that can be subscribed to. |
| [Payment Method Integration](/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/) | Information about implementing payment methods.                                                             |
| [Filtering Payment Methods](/docs/cart-and-checkout-filtering-payment-methods-in-the-checkout-block/)   | Information about filtering the payment methods available in the Checkout Block.                            |

## Checkout Block

In addition to the reference material below, [please see the `block-checkout` package documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/packages/checkout/README.md) which is used to extend checkout with Filters, Slot Fills, and Inner Blocks.

| Document                                                                                         | Description                                                                                                       |
|--------------------------------------------------------------------------------------------------| ----------------------------------------------------------------------------------------------------------------- |
| [How the Checkout Block processes an order](/docs/cart-and-checkout-how-the-checkout-block-processes-an-order/) | The detailed inner workings of the Checkout Flow.                                                                 |
| [IntegrationInterface](/docs/cart-and-checkout-handling-scripts-styles-and-data/)                                | The `IntegrationInterface` class and how to use it to register scripts, styles, and data with WooCommerce Blocks. |
| [Available Filters](/docs/category/cart-and-checkout-blocks/available-filters/)                                       | All about the filters that you may use to change values of certain elements of WooCommerce Blocks.                |
| [Slots and Fills](/docs/cart-and-checkout-slot-and-fill/)                                                | Explains Slot Fills and how to use them to render your own components in Cart and Checkout.                       |
| [Available Slot Fills](/docs/cart-and-checkout-available-slots/)                                 | Available Slots that you can use and their positions in Cart and Checkout.                                        |
| [DOM Events](/docs/cart-and-checkout-dom-events/)                                                     | A list of DOM Events used by some blocks to communicate between them and with other parts of WooCommerce.         |
| [Filter Registry](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/packages/checkout/filter-registry/README.md)                          | The filter registry allows callbacks to be registered to manipulate certain values.                               |
| [Additional Checkout Fields](/docs/cart-and-checkout-additional-checkout-fields/)                     | The filter registry allows callbacks to be registered to manipulate certain values.                               |
