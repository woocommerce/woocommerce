---
post_title: WooCommerce plugin API callbacks
menu_title: Plugin API callbacks
tags: reference
---

## Overview

This document provides a guide on how to use the WooCommerce Plugin API to initiate callbacks for plugin actions, especially for gateways and classes not initialized by default.

## Callback URL Structure

Before WooCommerce 2.0, use:

`https://example.com/?wc-api=CALLBACK`

In WooCommerce 2.0 or later, use the endpoint:

`https://example.com/wc-api/CALLBACK/`

## Behavior

When the callback URL is accessed, WooCommerce will:

- Initialize the `CALLBACK` class, if available
- Trigger the `woocommerce_api_callback` action
- Exit WordPress

## Hooking into the API Callback

To hook into the callback, add an action in your plugin:

```php
add_action( 'woocommerce_api_callback', 'your_callback_handler_function' );
```

## Redirecting After Callback

It's possible to redirect users after the action has been executed using your custom handler function.
