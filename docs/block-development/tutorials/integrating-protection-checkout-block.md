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

## Key points about server-side validation

- **Check for POST requests and the correct endpoint:** Ensure your validation logic only runs for POST requests to the checkout endpoint. This prevents unnecessary processing and avoids interfering with unrelated requests.
- **Safely access the protection token:** Always verify that the `extensions` key and your namespace exist in the request body before accessing the token. This prevents PHP notices and ensures your code handles malformed requests gracefully.
- **Return the `$result` parameter unless validation fails:** Always return the original `$result` parameter if your validation passes or if your logic should not run. Only return a `WP_Error` object if validation fails. This avoids interfering with other authentication or validation logic that may be running in WooCommerce or other plugins.
- **Allow certain payment methods to bypass protection (optional):** For example, you may want to skip CAPTCHA for express payment methods or hosted checkouts. Make this configurable via a filter so other developers can extend or override the behavior.

## Important Notes

### Security Considerations

1. **Always validate on the server side:** Client-side validation (e.g., JavaScript) can be bypassed by malicious users. Never rely solely on client-side checks for security-critical features like CAPTCHA or fraud protection.
2. **Use HTTPS:** Ensure your site uses HTTPS so that protection tokens and other sensitive data are transmitted securely between the client and server.
3. **Implement rate limiting:** Protect your endpoints from abuse by implementing [rate limiting](/docs/apis/store-api/rate-limiting/). This helps prevent brute-force attacks and reduces server load.
4. **Token expiration:** Ensure that protection tokens (e.g., CAPTCHA tokens) have appropriate expiration times and are validated for freshness on the server. Expired tokens should be rejected.

### Testing Your Integration

When testing your protection integration, consider the following:

1. **Test with the Checkout block enabled:** Ensure your protection mechanism works as expected in the block-based checkout flow.
2. **Verify validation failure for missing or invalid tokens:** Attempt to submit the checkout without a token or with an invalid token, and confirm that the server rejects the request appropriately.
3. **Test with different payment methods:** Make sure your logic correctly allows or blocks requests based on the selected payment method, especially if you allow some methods to bypass protection.
4. **Ensure compatibility with legitimate checkout flows:** Confirm that your protection mechanism does not interfere with normal, valid checkout submissions and that it works smoothly for real customers.
