---
post_title: Cart and Checkout - Overview of data flow between client and server
menu_title: Data flow overview
tags: reference
---

<!-- markdownlint-disable MD041 -->

In the WooCommerce Cart and Checkout blocks, the server is the source of truth for critical transactional and persistent data. This includes:

-   Cart item details (items, quantities, and prices)
-   Cart totals (e.g. taxes, fees, subtotals)
-   Customer information (shipping and billing addresses, other customer data)
    -   Additional fields added using the [Additional Checkout Fields API](https://developer.woocommerce.com/docs/cart-and-checkout-additional-checkout-fields/) are also persisted server-side.
-   Shipping methods and rates
-   Other cart details, such as applied coupons

Such data must be persisted server-side to ensure accuracy, consistency, and reliability across different user sessions and devices.
Ephemeral UI state—such as temporary validation states, or UI-specific interactions like expanded/collapsed sections should remain client-side and not be automatically persisted on the server, unless the specific state needs to be maintained across page loads or is critical to the user's checkout process.

You may wish to get data from the server into the client, and vice/versa. This document will outline the general concepts and data flow in the Cart/Checkout blocks, and provide links, or guidance on some common use cases.

## Where is data stored?

All the data relating to a cart, customer, and order is stored on the server, either in the database or in the customer's session. When it is sent to the client, it is stored in [`@wordpress/data`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/) data stores.

## How do I get my Server-side (PHP) data into the client (JavaScript) and vice/versa

Since the server is the source of truth, all data from the client should eventually make its way there if it needs to be persisted with the order.

There is also the case that your client needs data that can only be derived server-side, such as configuration options set in the WooCommerce settings dashboard, or data coming from external services (e.g. courier rates or payment APIs).

### Server (PHP) to Client (JavaScript)

There are two ways to get your data from the server to the client, depending on whether it is static or dynamic.

#### Static data

Static data is not likely to change based on actions the shopper takes, for example, an option from the WooCommerce settings dashboard. The recommended way of getting this data to the client is using the `AssetDataRegistry`.

When data is added here, it is serialized and sent to the client on page load. It will not change based on actions the shopper takes, e.g. adding items to the cart or changing their address.

A key/value pair can be added to the registry like so:

```php
add_action(
    'woocommerce_blocks_loaded',
    function() {
      $asset_data_registry = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class );
      $asset_data_registry->add( 'namespace/value', 'this is a custom value' );
    }
);
```

If a duplicate key exists, it will not be overwritten. Using a unique identifier is important, the recommendation is `namespace/value` to help ensure the key is unique.

To get this data on the client, use `wc.wcSettings.getSetting` like so:

```js
const myCustomValue = wc.wcSettings.getSetting(
	'namespace/value',
	'Fallback value.'
);
```

#### Dynamic data

Dynamic data is data that is likely to change in response to the shopper's actions, for example, changing location, or items in the cart. In this case, you need to add this to the cart API response. The cart response is sent on many routes during the shopper's journey, and on almost every API response triggered by the Cart/Checkout blocks.

To add data here, you'll need to extend the API response. See [Exposing your data in the Store API](../../plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-add-data.md).

### Client (JavaScript) to Server (PHP)

Getting data from the client to the server can be done in three different ways:

#### Checkout field updates

When the shopper updates a checkout field, the data is sent to the server immediately. For Additional Fields, this is sent via a PUT request to the `/checkout` endpoint. For address fields, this is sent via a POST request to the `cart/update-customer` endpoint (via batch). Both will return an updated cart, which is then applied to the client and totals are updated.

#### Piggybacking on a Store API request

This is useful for things that don't require an immediate response from the server, for example if you've added a new block to the Checkout and it contains a form field that should be saved along with the order. If this form field just needs to be saved and doesn't need to update any other values in the cart, then the data should be sent with the checkout request. The [Add a new inner block containing a custom field to the WooCommerce Checkout Block](../../plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-add-custom-fields.md) documentation provides a worked example of using `setExtensionData` along with extending the Store API to receive your data in an existing request.

#### Sending data on-demand using `extensionCartUpdate`

You may wish to send your data to the server immediately, rather than waiting for a Store API request to be made. This may be desired if the data may update the cart, for example adding fees, changing which shipping methods are available, or changing the tax rates.

The [Updating the cart on-demand with the Store API](../../plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-update-cart.md) document outlines how to do this. 

## When is data sent/received in the Cart/Checkout blocks?

### Page load

On page load, if the Cart or Checkout block is present, the cart state is sent along with the initial request, and is hydrated into a client-side [`@wordpress/data`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/) data store.

On page load, the `wc/store/cart` and `wc/store/checkout` data stores are hydrated with the data from the server. The `wc/store/payment` data store is populated partly with data from the server, but needs some client-side processing to finish before all payment methods are registered there.

### Entering customer data into the checkout form

When the shopper enters data into the form, the data is immediately written to the `wc/store/cart` data store, and a debounced method called [`pushChanges`](https://github.com/woocommerce/woocommerce/blob/4861ec250ef1789f814f4209755165e8abe7b838/plugins/woocommerce-blocks/assets/js/data/cart/push-changes.ts#L167) is called. This method sends the customer data to the server where it is persisted against the customer. The full cart is sent back as a response, and the data store is updated with this.

This is important to note, because if any code is running on the server that modifies the customer addresses, then it will be reflected in the response.

For example, if a plugin modifies the address data to ensure all city names are capitalised, and the shopper enters "london" into the city, when the data is returned to the client, the text would change to "London" and the input field would update.

Modifying form fields while the shopper is interacting with them is a jarring experience, so instead of making these changes while the user is interacting with the form, consider making them while processing the checkout action on the server.

### Adding coupons

When the shopper expands the coupon form, the state is stored locally. This kind of data is not sent to the server. The request to add the coupon is made when the shopper presses the Apply button. A Store API request is made and it returns a new cart which is then applied.

### Changing shipping method

When the shopper changes shipping method, a Store API request is made and it returns a new cart which is then applied.

### Changing payment method

When the shopper changes payment method, it does not automatically send a Store API request to the server. If you need to update the server when the payment method is changed and before the order is placed, the correct approach would be to use [`cartExtensionUpdate`](../../plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-update-cart.md).

### Adding/editing order notes

Adding or editing order notes is stored locally on the client, these notes are not sent to the server until the shopper submits the checkout form.

## Cart

The items listed in the Checkout section above also apply to the Cart block (besides changing payment method, the shopper cannot select their payment method on the Cart block unless it's an express payment method, which behaves differently and is implemented entirely client-side).

### Changing item quantity, adding, or removing items

When the shopper updates an item's quantity, removes an item from their cart, or adds an item (e.g. from the Cart cross-sells block) a Store API request is made. The local cart is updated with the response.

### Using the shipping calculator

This behaves the same way as the address forms in the Checkout block, however the address in the shipping calculator is only sent to the server if the postcode is valid, and all required fields have values.
