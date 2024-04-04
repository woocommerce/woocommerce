# Adding an endpoint to ExtendSchema <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Extending `GET` endpoints in Store API](#extending-get-endpoints-in-store-api)
-   [Use cases for adding new endpoints](#use-cases-for-adding-new-endpoints)
    -   [Extending an existing schema](#extending-an-existing-schema)
    -   [Adding a new schema](#adding-a-new-schema)

This document is intended for internal developers of the WooCommerce Blocks plugin. If you feel like a new endpoint should be added, feel free to open an issue or a PR detailing why.

## Extending `GET` endpoints in Store API

ExtendSchema needs to expose each endpoint individually. If you want to expose a new endpoint, you have to follow these steps:

1. In `ExtendSchema` class, add your endpoint `IDENTIFIER` to the `$endpoints` variable.

```php

use Automattic\WooCommerce\StoreApi\Schemas\BillingAddressSchema;

private $endpoints = [ /* other identifiers */, BillingAddressSchema::IDENTIFIER ];

```

This is to prevent accidentally exposing new endpoints.

2. Inside your endpoint schema class (for this example, inside `BillingAddressSchema`), in its `get_properties` method, add this call at the end of the returned array.

You can pass extra parameters to `get_extended_schema` and those would be passed to third party code.

```php
self::EXTENDING_KEY => $this->get_extended_schema( self::IDENTIFIER ),
```

`EXTENDING_KEY` value is `extensions`, we use a constant to make sure we don't have a typo.

3. Inside the same class, in `get_item_response`, add the below line. Like `get_extended_schema`, you can pass extra parameters here as well.

Make sure to only expose what's needed.

```php
self::EXTENDING_KEY => $this->get_extended_data( self::IDENTIFIER, $cart_item ),
```

That's it, your endpoint would now contain `extensions` in your endpoint, and you can consume it in the frontend.

Extending a new endpoint is usually half the work, you will need to receive this data in the frontend and pass it to any other extensibility point (Slot, Filter, Event).

## Use cases for adding new endpoints

### Extending an existing schema

There might be a case when you want to extend an existing schema, for example, you want to add `order_number` to the checkout endpoint. In [Add order number to checkout schema](https://github.com/woocommerce/woocommerce-blocks/pull/9927/) we did that.

### Adding a new schema

There might be a case when you want to add a new schema, for example, you need a new endpoint that do not exist in the Store API yet, e.g. `wc/store/order`. In [Add an endpoint for getting pay for order orders](https://github.com/woocommerce/woocommerce-blocks/pull/10199/) we did that.

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/internal-developers/rest-api/extend-rest-api-new-endpoint.md)

<!-- /FEEDBACK -->
