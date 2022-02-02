# Extensibility in WooCommerce Blocks <!-- omit in toc -->

These documents are all dealing with extensibility in the various WooCommerce Blocks.

## Table of Contents <!-- omit in toc -->

- [Hooks (actions and filters)](#hooks-actions-and-filters)
- [REST API](#rest-api)
- [Checkout Payment Methods](#checkout-payment-methods)
- [Checkout Block](#checkout-block)

## Hooks (actions and filters)

| Document                | Description                                             |
| ----------------------- | ------------------------------------------------------- |
| [Actions](./actions.md) | Documentation covering action hooks on the server side. |
| [Filters](./filters.md) | Documentation covering filter hooks on the server side. |

## REST API

| Document                                                                                | Description                                                                         |
| --------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| [Exposing your data in the Store API.](./extend-rest-api-add-data.md)                   | Explains how you can add additional data to Store API endpoints.                    |
| [Available endpoints to extend with ExtendRestAPI.](./available-endpoints-to-extend.md) | A list of all available endpoints to extend.                                        |
| [Adding an endpoint to ExtendRestAPI.](./extend-rest-api-new-endpoint.md)               | A step by step process for contributors to expose a new endpoint via ExtendRestApi. |
| [Available Formatters](./extend-rest-api-formatters.md)                                 | Available `Formatters` to format data for use in the Store API.                     |

## Checkout Payment Methods

| Document                                                      | Description                                                                                                 |
| ------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| [Checkout Flow and Events](./checkout-flow-and-events.md)     | All about the checkout flow in the checkout block and the various emitted events that can be subscribed to. |
| [Payment Method Integration](./payment-method-integration.md) | Information about implementing payment methods.                                                             |
| [Filtering Payment Methods](./filtering-payment-methods.md)   | Information about filtering the payment methods available in the Checkout Block.                            |

## Checkout Block

In addition to the reference material below, [please see the `block-checkout` package documentation](../../packages/checkout/README.md) which is used to extend checkout with Filters, Slot Fills, and Inner Blocks.

| Document                                           | Description                                                                                                       |
| -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| [IntegrationInterface](./integration-interface.md) | The `IntegrationInterface` class and how to use it to register scripts, styles, and data with WooCommerce Blocks. |
| [Available Filters](./available-filters.md)        | All about the filters that you may use to change values of certain elements of WooCommerce Blocks.                |
| [Slots and Fills.](./slot-fills.md)                | Explains Slot Fills and how to use them to render your own components in Cart and Checkout.                       |
| [Available Slot Fills.](./available-slot-fills.md) | Available Slots that you can use and their positions in Cart and Checkout.                                        |
| [DOM Events](./dom-events.md)                      | A list of DOM Events used by some blocks to communicate between them and with other parts of WooCommerce.         |

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/extensibility/README.md)
<!-- /FEEDBACK -->

