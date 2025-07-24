---
sidebar_label: Integrating Protection with the Checkout Block
sidebar_position: 6
---

# Integrating Protection with the Checkout Block

If you're a developer of a Captcha or fraud protection plugin, make sure your solution is hooking into the [Store API](/docs/apis/store-api/) and integrating with the Checkout block. This tutorial will guide you through the process of adding protection mechanisms to the WooCommerce Checkout block.

## Overview

The WooCommerce Checkout block uses the [Store API](/docs/apis/store-api/) for processing orders, which provides a secure, unauthenticated API for customer-facing functionality. To integrate protection mechanisms like CAPTCHA or fraud detection, you'll need to:

1. **Render your protection element** in the checkout block using WordPress block filters
2. **Handle client-side validation** using the checkout data store
3. **Validate on the server-side** using the Store API authentication hooks

## Step 1: Rendering Your Protection Element

The first step is to render your CAPTCHA or protection element in the checkout block. You can use the `render_block` filter to inject your HTML before or after specific checkout blocks.

### Using the render_block Filter

The `render_block` filter allows you to modify the output of any WordPress block. For the checkout block, you'll want to target the `woocommerce/checkout-actions-block` which contains the "Place order" button.

```php
add_filter(
    'render_block_woocommerce/checkout-actions-block',
    function( $block_content ) {
        $captcha = '<div class="my-captcha-element" data-sitekey="' . esc_attr( get_option( 'plugin_captcha_sitekey' ) ) . '"></div>';
        return $block_content . $captcha;
    },
    999,
    1
);
```

**Key points about this code:**

- The filter targets `woocommerce/checkout-actions-block` which is the block containing the place order button
- We use a priority of `999` to ensure our content is added after other modifications
- The `data-sitekey` attribute stores your CAPTCHA configuration, but this may be different for your plugin
- We append our protection element after the original block content by concatenating it to `$block_content` (no output buffering needed)

## Step 2: Client-Side Integration

Once your protection element is rendered, you need to integrate it with the checkout data store to capture the validation token and pass it to the server.

### Using the Checkout Data Store

The checkout block uses a data store to manage state. You can use the `setExtensionData` method to pass your protection token to the server.

```js
/* Woo Checkout Block */
if ( wp && wp.data ) {
  var unsubscribe = wp.data.subscribe( function () {
    const turnstileItem = document.querySelector(".my-captcha-element");

    if ( turnstile && turnstileItem ) {
      turnstile.render( turnstileItem, {
        sitekey: turnstileItem.dataset.sitekey,
        callback: function( data ) {
          wp.data
            .dispatch("wc/store/checkout")
            .setExtensionData("plugin-namespace-turnstile", {
              token: data,
            });
        },
      });

      unsubscribe();
    }
  }, "wc/store/cart" );
}
```

**Key points about this JavaScript:**

- We subscribe to the cart data store to detect when the checkout is ready
- The `turnstile.render()` method initializes your CAPTCHA (replace with your specific implementation)
- `setExtensionData()` stores the token in the checkout data store
- The namespace should be unique to your plugin (e.g., `my-plugin-turnstile`)

### Data Store Integration

For more information about the checkout data store and available methods, see the [Checkout Data Store documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/data-store/checkout.md).

## Step 3: Server-Side Validation

The most critical step is validating the protection token on the server side. This should happen early in the authentication process to prevent any unauthorized checkout attempts.

### Using the core `rest_authentication_errors` filter

The [`rest_authentication_errors`](https://developer.wordpress.org/reference/hooks/rest_authentication_errors/) filter is the ideal place to validate your protection token because it runs before any checkout processing begins.

```php
add_filter( 'rest_authentication_errors', 'plugin_check_turnstile_token' );

function plugin_check_turnstile_token( $result ) {
    // Skip if this is not a POST request.
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
        // Always return the result or an error, never a boolean. This ensures other checks aren't thrown away like rate limiting or authentication.
        return $result;
    }

    // Skip if this is not the checkout endpoint.
    if (
        ! isset( $GLOBALS['wp']->query_vars['rest_route'] ) ||
        ! preg_match( '#/wc/store(?:/v\d+)?/checkout#', $GLOBALS['wp']->query_vars['rest_route'] )
    ) {
        return $result;
    }

    // get request body
    $request_body = json_decode( \WP_REST_Server::get_raw_data(), true );

    if ( isset( $request_body['payment_method'] ) ) {
        $chosen_payment_method = sanitize_text_field(  $request_body['payment_method'] );

        // Provide ability to short circuit the check to allow express payments or hosted checkouts to bypass the check.
        $selected_payment_methods = apply_filters(  'plugin_payment_methods_to_skip', array('woocommerce_payments' ) );
        if( is_array( $selected_payment_methods ) ) {
            if ( in_array( $chosen_payment_method, $selected_payment_methods, true ) ) {
                return $result;
            }
        }
    }

    if (
        ! isset( $request_body['extensions'] ) ||
        empty( $request_body['extensions'] ) ||
        ! isset( $request_body['extensions']['plugin-namespace-turnstile'] )
    ) {
        return new WP_Error( 'challenge_failed', 'Captcha challenge failed' );
    }
    $extensions = $request_body['extensions'];
    $token = sanitize_text_field( $extensions['plugin-namespace-turnstile']['token'] );
    $check = my_token_check_function( $token );
    $success = $check['success'];

    if( $success !== true ) {
        return new WP_Error( 'challenge_failed', 'Captcha challenge failed' );
    }

    return $result;
}
```