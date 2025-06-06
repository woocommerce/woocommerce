---
post_title: How to Add Conditional Additional Checkout Fields
sidebar_label: How to add additional conditional fields in checkout
---
# How to Make Your WooCommerce Additional Checkout Fields Conditionally Visible in the Checkout Block

*Published: May 30, 2025*

In our previous post, we covered the basics of adding additional fields to the WooCommerce Checkout Block. Now, let's take it a step further and explore how to make those fields appear and disappear based on customer choices, cart contents, or other dynamic conditions.

Conditional visibility allows you to create smart, adaptive checkout forms that only show relevant fields when needed, reducing form clutter and improving the customer experience.

## Table of Contents

* [Why Use Conditional Visibility?](#why-use-conditional-visibility)
* [Understanding JSON Schema for Conditions](#understanding-json-schema-for-conditions)
* [Common Conditional Scenarios](#common-conditional-scenarios)

  * [Show Fields Based on Shipping Method](#show-fields-based-on-shipping-method)
  * [Show Fields Based on Cart Contents](#show-fields-based-on-cart-contents)
  * [Show Fields Based on Cart Value](#show-fields-based-on-cart-value)
  * [Show Fields Based on Customer Location](#show-fields-based-on-customer-location)
  * [Show Fields Based on Other Field Values](#show-fields-based-on-other-field-values)
* [Practical Complete Example](#practical-complete-example)
* [Next Steps](#next-steps)

## Why Use Conditional Visibility?

Conditional fields help you:

* Reduce form complexity by hiding irrelevant fields
* Create dynamic checkout flows based on customer selections
* Show specialized fields only for specific products or customer types
* Improve conversion rates with cleaner, more focused forms
* Collect contextual information that's only relevant in certain situations

## Understanding JSON Schema for Conditions

WooCommerce's additional checkout fields use JSON Schema to define conditional logic. Don't worry if you're not familiar with JSON Schema – we'll walk through practical examples that you can adapt for your needs.

The basic structure looks like this:

```php
'required' => [
    [
        'type' => 'object',
        'properties' => [
            // Define conditions here
        ]
    ]
],
'hidden' => [
    [
        'type' => 'object',
        'properties' => [
            // Define when to hide here
        ]
    ]
]
```

## Common Conditional Scenarios

### Show Fields Based on Shipping Method

One of the most common use cases is showing fields only when specific shipping methods are selected (e.g., Local Pickup):

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/delivery-instructions',
        'label'    => __('Special delivery instructions', 'your-text-domain'),
        'location' => 'order',
        'type'     => 'text',
        'required' => [
            'cart' => [
                'properties' => [
                    'prefers_collection' => [
                        'const' => true
                    ]
                ]
            ]
        ],
        'hidden' => [
            'cart' => [
                'properties' => [
                    'prefers_collection' => [
                        'const' => false
                    ]
                ]
            ]
        ]
    )
);
```

### Show Fields Based on Cart Contents

Display fields only when specific products are in the cart:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/fragile-handling',
        'label'    => __('This order contains fragile items – special handling required?', 'your-text-domain'),
        'location' => 'order',
        'type'     => 'checkbox',
        'required' => [
            'cart' => [
                'properties' => [
                    'items' => [
                        'contains' => [
                            'enum' => [2766, 456, 789] // Product IDs for fragile items
                        ]
                    ]
                ]
            ]
        ]
    )
);
```

### Show Fields Based on Cart Value

You can conditionally display fields based on the total cart value. For example, show a field only if the cart total exceeds \$100:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/high-value-note',
        'label'    => __('Additional instructions for high-value orders', 'your-text-domain'),
        'location' => 'order',
        'type'     => 'text',
        'required' => [
            'cart' => [
                'properties' => [
                    'total' => [
                        'minimum' => 100
                    ]
                ]
            ]
        ],
        'hidden' => [
            'cart' => [
                'properties' => [
                    'total' => [
                        'maximum' => 99.99
                    ]
                ]
            ]
        ]
    )
);
```

### Show Fields Based on Customer Location

Display fields only for customers from specific countries:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/eu-vat-number',
        'label'    => __('EU VAT Number', 'your-text-domain'),
        'location' => 'billing',
        'type'     => 'text',
        'required' => [
            'customer' => [
                'properties' => [
                    'billing_country' => [
                        'enum' => ['DE', 'FR', 'ES'] // List of EU country codes
                    ]
                ]
            ]
        ],
        'hidden' => [
            'customer' => [
                'properties' => [
                    'billing_country' => [
                        'not' => [
                            'enum' => ['DE', 'FR', 'ES']
                        ]
                    ]
                ]
            ]
        ]
    )
);
```

### Show Fields Based on Other Field Values

You can also conditionally display fields based on the values of other custom fields:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/alternate-contact',
        'label'    => __('Alternate Contact Number', 'your-text-domain'),
        'location' => 'contact',
        'type'     => 'text',
        'required' => [
            'customer' => [
                'properties' => [
                    'my-plugin/contact-preference' => [
                        'const' => 'phone'
                    ]
                ]
            ]
        ],
        'hidden' => [
            'customer' => [
                'properties' => [
                    'my-plugin/contact-preference' => [
                        'not' => [
                            'const' => 'phone'
                        ]
                    ]
                ]
            ]
        ]
    )
);
```

## Practical Complete Example

Combining multiple conditions, here's a comprehensive example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/special-instructions',
        'label'    => __('Special Instructions', 'your-text-domain'),
        'location' => 'order',
        'type'     => 'textarea',
        'required' => [
            'cart' => [
                'properties' => [
                    'total' => [
                        'minimum' => 150
                    ],
                    'items' => [
                        'contains' => [
                            'enum' => [1234, 5678]
                        ]
                    ]
                ]
            ]
        ],
        'hidden' => [
            'cart' => [
                'properties' => [
                    'total' => [
                        'maximum' => 149.99
                    ]
                ]
            ]
        ]
    )
);
```

In this example, the "Special Instructions" field appears only when the cart total is at least \$150 and contains specific products.

## Next Steps

Now that you've learned how to make your WooCommerce additional checkout fields conditionally visible, consider exploring more advanced features such as:

* Conditional field visibility based on user roles
* Dynamic field values populated from external APIs
* Integrating with third-party services for enhanced functionality

For more information, refer to the [WooCommerce developer documentation](https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/).

---

*Note: This Markdown file is based on the blog post by Code by Tom. For the original post and more details, visit [Code by Tom's blog](https://codebytom.blog/2025/05/30/how-to-make-your-woocommerce-additional-checkout-fields-conditionally-visible/).*
