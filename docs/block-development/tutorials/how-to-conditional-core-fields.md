---
post_title: How to Add Conditional Rules to Core Checkout Fields
sidebar_label: How to add conditional rules to core checkout fields
---

# How to Add Conditional Rules to Core Checkout Fields in the Checkout Block

This feature requires a minimum version of WooCommerce 10.8.0.

The Checkout block has long supported conditional `required`, `hidden`, and `validation` rules on additional fields registered via `woocommerce_register_additional_checkout_field()`. The same mechanism is now available for **core fields** - the built-in fields like `first_name`, `last_name`, `country`, `postcode`, `phone`, and `email`.

Rules use JSON Schema and are evaluated identically on the client (for live UI updates) and on the server (in the Store API, so rules cannot be bypassed by crafted requests).

## When to Use This

You might want conditional rules on core fields to:

- Make the `phone` field required only in certain countries.
- Hide the `postcode` for regions that do not use postcodes.
- Attach a country-specific regex to `postcode`.
- Require `company` only when the customer is checking out as a business.
- Enforce that `first_name` and `last_name` meet a project-specific pattern.

## The API

Rules are attached to a core field with `woocommerce_register_checkout_core_field_rules()`:

```php
woocommerce_register_checkout_core_field_rules(
    string $field_id, // 'first_name', 'last_name', 'country', 'postcode', 'email', ...
    array  $rules     // Any subset of 'required', 'hidden', 'validation'
);
```

The accepted field ids are the keys returned by `CheckoutFields::get_core_fields_keys()`:

- `email`
- `country`
- `first_name`
- `last_name`
- `company`
- `address_1`
- `address_2`
- `city`
- `state`
- `postcode`
- `phone`

Rule values use the same JSON Schema shape as additional fields. See `how-to-conditional-additional-fields.md` for more detail on writing JSON Schema rules.

## Examples

### Require the phone number only for orders shipping to Germany

```php
add_action(
    'woocommerce_blocks_loaded',
    function () {
        woocommerce_register_checkout_core_field_rules(
            'phone',
            array(
                'required' => array(
                    'customer' => array(
                        'properties' => array(
                            'shipping_address' => array(
                                'properties' => array(
                                    'country' => array( 'const' => 'DE' ),
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );
    }
);
```

### Hide the postcode for Hong Kong addresses

```php
woocommerce_register_checkout_core_field_rules(
    'postcode',
    array(
        'hidden' => array(
            'customer' => array(
                'properties' => array(
                    'shipping_address' => array(
                        'properties' => array(
                            'country' => array( 'const' => 'HK' ),
                        ),
                    ),
                ),
            ),
        ),
    )
);
```

### Enforce a UK-specific postcode pattern

```php
woocommerce_register_checkout_core_field_rules(
    'postcode',
    array(
        'validation' => array(
            'type'    => 'string',
            'pattern' => '^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$',
        ),
    )
);
```

Multiple rule types can be registered in a single call, and subsequent calls for the same field are merged.

## How Evaluation Works

The `required`, `hidden`, and `validation` rules are evaluated against a DocumentObject that contains the current cart, checkout, and customer state. This is the same object used for additional fields, and the same path rules apply:

- Core address fields (`first_name`, `last_name`, `country`, `postcode`, etc.) are validated against `customer.billing_address` or `customer.shipping_address`.
- `email` is validated against the billing address.
- A hidden field is never reported as required, and its value is not enforced.

## Server-Side Enforcement

When the Checkout block posts to the Store API, the server re-evaluates every rule against the same DocumentObject. A request that bypasses the client UI still receives the same errors the UI would surface.

## Related

- [How to add conditional rules to additional fields](./how-to-conditional-additional-fields.md)
- [How to add additional checkout fields](./how-to-additional-checkout-fields-guide.md)
