---
post_title: How to Add Conditional Additional Checkout Fields
sidebar_label: How to add additional conditional fields in checkout
---

# How to Make Your WooCommerce Additional Checkout Fields Conditionally Visible in the Checkout Block

This feature requires a minimum version of WooCommerce 9.9.0

Conditional visibility allows you to create smart, adaptive checkout forms that only show relevant fields when needed, reducing form clutter and improving the customer experience.

## Why Use Conditional Visibility?

Conditional fields help you:

* Reduce form complexity by hiding irrelevant fields
* Create dynamic checkout flows based on customer selections
* Show specialized fields only for specific products or customer types
* Improve conversion rates with cleaner, more focused forms
* Collect contextual information that's only relevant in certain situations

## Understanding JSON Schema for Conditions

WooCommerce’s additional checkout fields use JSON Schema to define conditional logic. Don’t worry if you’re not familiar with JSON Schema – we’ll walk through practical examples that you can adapt for your needs.

The basic structure looks like this:

```php
'required' => [
    // Define when to hide here
],
'hidden' => [
    // Define when to hide here
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
		'label'    => __('This order contains fragile items - special handling required?','your-text-domain'),
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

Display premium service options only for high-value orders:

```php
woocommerce_register_additional_checkout_field(
	array(
		'id'       => 'my-plugin/white-glove-service',
		'label'    => __('Add white glove delivery service?', 'your-text-domain'),
		'location' => 'order',
		'type'     => 'checkbox',
		'hidden' => [
			'cart' => [
				'properties' => [
					'totals' => [
						'properties' => [
							'totalPrice' => [
								'maximum' => 50000 // Hide if cart total is less than $500 (in cents)
							]
						]
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
		'id'       => 'my-plugin/tax-exemption-number',
		'label'    => __('Tax exemption number', 'your-text-domain'),
		'location' => 'address',
		'type'     => 'text',
		'required' => [
			'customer' => [
				'properties' => [
					'address' => [
						'properties' => [
							'country' => [
								'enum' => ['US', 'CA'] // Required only for US and Canada
							]
						]
					]
				]
			]
		],
		'hidden' => [
			'customer' => [
				'properties' => [
					'address' => [
						'properties' => [
							'country' => [
								'not' => [
									'enum' => ['US', 'CA'] // Hide for countries other than US and Canada
								]
							]
						]
					]
				]
			]
		]
	)
);
```

### Show Fields Based on Other Field Values

Create dependent fields where one field’s visibility depends on another field’s value:

```php
// First field - service type selection
woocommerce_register_additional_checkout_field(
	array(
		'id'       => 'my-plugin/service-type',
		'label'    => __('Type of service needed', 'your-text-domain'),
		'location' => 'order',
		'type'     => 'select',
		'options'  => array(
			array( 'value' => 'standard', 'label' => 'Standard' ),
			array( 'value' => 'express', 'label' => 'Express' ),
			array( 'value' => 'custom', 'label' => 'Custom' ),
		),
	)
);

// Second field - only show when "custom" is selected
woocommerce_register_additional_checkout_field(
	array(
		'id'       => 'my-plugin/custom-requirements',
		'label'    => __('Describe your custom requirements', 'your-text-domain'),
		'location' => 'order',
		'type'     => 'text',
		'required' => [
			'checkout' => [
				'properties' => [
					'additional_fields' => [
						'properties' => [
							'my-plugin/service-type' => [
								'const' => 'custom'
							]
						]
					]
				]
			]
							],
		'hidden' => [
			'checkout' => [
				'properties' => [
					'additional_fields' => [
						'properties' => [
							'my-plugin/service-type' => [
								'not' => [
									'const' => 'custom'
								]
							]
						]
					]
				]
			]
		]
	)
);
```

## Practical Complete Example

Here’s a comprehensive example for a store that offers both digital and physical products:

```php
add_action( 'woocommerce_init', function() {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	// Delivery preference - only for physical products
	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'my-store/delivery-preference',
			'label'    => __('Delivery preference', 'your-text-domain'),
			'location' => 'order',
			'type'     => 'select',
			'options'  => array(
				array( 'value' => 'doorstep', 'label' => __('Leave at doorstep', 'your-text-domain') ),
				array( 'value' => 'neighbor', 'label' => __('Leave with neighbor', 'your-text-domain') ),
				array( 'value' => 'pickup_point', 'label' => __('Delivery to pickup point', 'your-text-domain') ),
			),
			'required' => [
				'cart' => [
					'properties' => [
						'needs_shipping' => [
							'const' => true
						]
					]
				]
			],
			'hidden' => [
				'cart' => [
					'properties' => [
						'needs_shipping' => [
							'const' => false
						]
					]
				]
			]
		)
	);

	// Delivery instructions - only when 'doorstep' is selected
	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'my-store/doorstep-instructions',
			'label'    => __('Specific doorstep delivery instructions', 'your-text-domain'),
			'location' => 'order',
			'type'     => 'text',
			'required' => [
				'checkout' => [
					'properties' => [
						'additional_fields' => [
							'properties' => [
								'my-store/delivery-preference' => [
									'const' => 'doorstep'
								]
							]
						]
					]
				]
								],
			'hidden' => [
				'checkout' => [
					'properties' => [
						'additional_fields' => [
							'properties' => [
								'my-store/delivery-preference' => [
									'not' => [
										'const' => 'doorstep'
									]
								]
							]
						]
					]
				]
			]
		)
	);

	// Digital delivery email - only for digital products
	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'my-store/digital-delivery-email',
			'label'    => __('Alternative email for digital products', 'your-text-domain'),
			'location' => 'contact',
			'type'     => 'text',
			'required' => [
				'cart' => [
					'properties' => [
						'needs_shipping' => [
							'const' => false
						]
					]
				]
			],
			'hidden' => [
				'cart' => [
					'properties' => [
						'needs_shipping' => [
							'const' => true
						]
					]
				]
			],
			'sanitize_callback' => function ( $field_value ) {
				return sanitize_email( $field_value );
			},
			'validate_callback' => function ( $field_value ) {
				if ( ! is_email( $field_value ) ) {
					return new \WP_Error( 'invalid_alt_email', __('Please ensure your alternative email matches the correct format.', 'your-text-domain') );
				}
			},
		)
	);
});
```

## Available data for conditions

You can create conditions based on various checkout data:

1. Cart information: Total price, items, shipping rates, coupons, weight
2. Customer data: ID, billing/shipping addresses, email
3. Other additional fields: Reference values from other custom fields and more!

## Next Steps

Conditional visibility transforms static checkout forms into dynamic, intelligent interfaces that adapt to your customers’ needs. Combined with the basic additional fields from our previous post, you can create sophisticated checkout experiences that collect exactly the right information at exactly the right time.

Start experimenting with simple conditions and gradually build more complex logic as you become comfortable with the JSON Schema syntax. Your customers will appreciate the cleaner, more relevant checkout experience!
