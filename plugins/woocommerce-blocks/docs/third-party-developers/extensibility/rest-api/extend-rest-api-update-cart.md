# Updating the cart with the Store API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [The problem](#the-problem)
-   [The solution](#the-solution)
-   [Basic usage](#basic-usage)
-   [Things to consider](#things-to-consider)
    -   [Extensions cannot update the client-side cart state themselves](#extensions-cannot-update-the-client-side-cart-state-themselves)
    -   [Only one callback for a given namespace may be registered](#only-one-callback-for-a-given-namespace-may-be-registered)
-   [API Definition](#api-definition)
    -   [PHP](#php)
    -   [JavaScript](#javascript)
-   [Putting it all together](#putting-it-all-together)
    -   [The "Redeem" button](#the-redeem-button)
    -   [Registering a callback to run when the `cart/extensions` endpoint is hit](#registering-a-callback-to-run-when-the-cartextensions-endpoint-is-hit)

## The problem

You're an extension developer, and your extension does some server-side processing as a result of some client-side input, i.e. a shopper filling in an input field in the Cart sidebar, and then pressing a button.

This server-side processing causes the state of the cart to change, and you want to update the data displayed in the client-side Cart or Checkout block.

You can't simply update the client-side cart state yourself. This is restricted to prevent malfunctioning extensions inadvertently updating it with malformed or invalid data which will cause the whole block to break.

## The solution

`ExtendSchema` offers the ability for extensions to register callback functions to be executed when signalled to do so by the client-side Cart or Checkout.

WooCommerce Blocks also provides a front-end function called `extensionCartUpdate` which can be called by client-side code, this will send data (specified by you when calling `extensionCartUpdate`) to the `cart/extensions` endpoint. When this endpoint gets hit, any relevant (based on the namespace provided to `extensionCartUpdate`) callbacks get executed, and the latest server-side cart data gets returned and the block is updated with this new data.

## Basic usage

In your extension's server-side integration code:

```php
add_action('woocommerce_blocks_loaded', function() {
  woocommerce_store_api_register_update_callback(
    [
      'namespace' => 'extension-unique-namespace',
      'callback'  => /* Add your callable here */
    ]
  );
} );
```

and on the client side:

```ts
const { extensionCartUpdate } = wc.blocksCheckout;
const { processErrorResponse } = wc.wcBlocksData;

extensionCartUpdate( {
	namespace: 'extension-unique-namespace',
	data: {
		key: 'value',
		another_key: 100,
		third_key: {
			fourth_key: true,
		},
	},
} ).then( () => {
	// Cart has been updated.
} ).catch( ( error ) => {
	// Handle error.
	processErrorResponse(error);
} );
```

## Things to consider

### Extensions cannot update the client-side cart state themselves

You may be wondering why it's not possible to just make a custom AJAX endpoint for your extension that will update the cart. As mentioned, extensions are not permitted to update the client-side cart's state, because doing this incorrectly would cause the entire block to break, preventing the user from continuing their checkout. Instead you _must_ do this through the `extensionCartUpdate` function.

### Only one callback for a given namespace may be registered

With this in mind, if your extension has several client-side interactions that result in different code paths being executed on the server-side, you may wish to pass additional data through in `extensionsCartUpdate`. For example if you have two actions the user can take, one to _add_ a discount, and the other to _remove_ it, you may wish to pass a key called `action` along with the other data to `extensionsCartUpdate`. Then in your callback, you can check this value to distinguish which code path you should execute.

Example:

```php
<?php
function add_discount() {
  /* Do some processing here */
}

function remove_discount() {
  /* Do some processing here */
}

add_action('woocommerce_blocks_loaded', function() {
  woocommerce_store_api_register_update_callback(
    [
      'namespace' => 'extension-unique-namespace',
      'callback'  => function( $data ) {
        if ( $data['action'] === 'add' ) {
          add_discount( );
        }
        if ( $data['action'] === 'remove' ) {
          remove_discount();
        }
      }
    ]
  );
} );
```

If you try to register again, under the same namespace, the previously registered callback will be overwritten.

## API Definition

### PHP

`ExtendSchema::register_update_callback`: Used to register a callback to be executed when the `cart/extensions` endpoint gets hit with a given namespace. It takes an array of arguments

| Attribute   | Type       | Required | Description                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ----------- | ---------- | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `namespace` | `string`   | Yes      | The namespace of your extension. This is used to determine which extension's callbacks should be executed.                                                                                                                                                                                                                                                                                                                  |
| `callback`  | `Callable` | Yes      | The function/method (or Callable) that will be executed when the `cart/extensions` endpoint is hit with a `namespace` that matches the one supplied. The callable should take a single argument. The data passed into the callback via this argument will be an array containing whatever data you choose to pass to it. The callable does not need to return anything, if it does, then its return value will not be used. |

### JavaScript

`extensionCartUpdate`: Used to signal that you want your registered callback to be executed, and to pass data to the callback. It takes an object as its only argument.

| Attribute   | Type     | Required | Description                                                                                                                                                      |
| ----------- | -------- | -------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `namespace` | `string` | Yes      | The namespace of your extension. This is used to determine which extension's callbacks should be executed.                                                       |
| `data`      | `Object` | No       | The data you want to pass to your callback. Anything in the `data` key will be passed as the first (and only) argument to your callback as an associative array. |

## Putting it all together

You are the author of an extension that lets the shopper redeem points that they earn on your website for a discount on their order. There is a text field where the shopper can enter how many points they want to redeem, and a submit button that will apply the redemption.

Your extension adds these UI elements to the sidebar in the Cart and Checkout blocks using the [`DiscountsMeta`](../checkout-block/available-slot-fills.md) Slot.

More information on how to use Slots is available in our [Slots and Fills documentation](../checkout-block/slot-fills.md).

Once implemented, the sidebar has a control added to it like this:

![image](https://user-images.githubusercontent.com/5656702/125109827-bf7c8300-e0db-11eb-9e51-59921b38a0c2.png)

### The "Redeem" button

In your UI, you are tracking the value the shopper enters into the `Enter amount` box using a React `useState` variable. The variable in this example shall be called `pointsInputValue`.

When the `Redeem` button gets clicked, you want to tell the server how many points to apply to the shopper's basket, based on what they entered into the box, apply the relevant discount, update the server-side cart, and then show the updated price in the client-side sidebar.

To do this, you will need to use `extensionCartUpdate` to tell the server you want to execute your callback, and have the new cart state loaded into the UI. The `onClick` handler of the button may look like this:

```js
const { extensionCartUpdate } = window.wc.blocksCheckout;

const buttonClickHandler = () => {
	extensionCartUpdate( {
		namespace: 'super-coupons',
		data: {
			pointsInputValue,
		},
	} );
};
```

### Registering a callback to run when the `cart/extensions` endpoint is hit

So far, we haven't registered a callback with WooCommerce Blocks yet, so when `extensionCartUpdate` causes the `cart/extensions` endpoint to get hit, nothing will happen.

Much like adding data to the Store API (described in more detail in [Exposing your data in the Store API](./extend-rest-api-add-data.md).) we can add the callback by invoking the `register_update_callback` method on the `ExtendSchema` class from WooCommerce Blocks.

We have written a function called `redeem_points` which applies a discount to the WooCommerce cart. This function does not return anything. Note, the actual implementation of this function is not the focus of this document, so has been omitted. All that is important to note is that it modifies the WooCommerce cart.

```php
<?php
function redeem_points( $points ) {
  /* Do some processing here that applies a discount to the WC cart based on the value of $points */
}

add_action('woocommerce_blocks_loaded', function() {
  woocommerce_store_api_register_update_callback(
    [
      'namespace' => 'super-coupons',
      'callback'  => function( $data ) {
        redeem_points( $data['points'] );
      },
    ]
  );
} );
```

Now that this is registered, when the button is pressed, the `cart/extensions` endpoint is hit, with a `namespace` of `super-coupons` our `redeem_points` function will be executed. After this has finished processing, the client-side cart will be updated by WooCommerce Blocks.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/rest-api/extend-rest-api-update-cart.md)

<!-- /FEEDBACK -->
