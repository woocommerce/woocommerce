---
post_title: How to Add Additional Fields
sidebar_label: Additional Fields in Checkout
---

# How to Add Additional Fields to the WooCommerce Checkout Block

The WooCommerce Checkout Block provides a powerful API for developers to add additional fields to collect information from customers during the checkout process. Whether you need to gather special delivery instructions, business details, or marketing preferences, additional checkout fields make it easy to extend your store’s functionality.

In this post, we’ll walk through the process of adding your own additional fields to your checkout form and show you practical examples you can implement right away.

## Table of Contents

* [Getting Started](#getting-started)
* [Field Locations: Where Your Fields Appear](#field-locations-where-your-fields-appear)

  * [Contact Information (`contact`)](#contact-information-contact)
  * [Address (`address`)](#address-address)
  * [Order Information (`order`)](#order-information-order)
* [Supported Field Types](#supported-field-types)

  * [Text Fields](#text-fields)
  * [Select Dropdowns](#select-dropdowns)
  * [Checkboxes](#checkboxes)
* [Adding Field Attributes](#adding-field-attributes)
* [Validation and Sanitization](#validation-and-sanitization)
* [Accessing Field Values](#accessing-field-values)
* [Next Steps](#next-steps)

## Getting Started

To add additional checkout fields, you’ll use the `woocommerce_register_additional_checkout_field()` function. This should be called after the `woocommerce_init` action to ensure WooCommerce is fully loaded.

Here's the basic structure:

```php
add_action( 'woocommerce_init', function() {
    if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
        return;
    }

    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'your-namespace/field-name',
            'label'    => __( 'Your Field Label', 'your-text-domain' ),
            'location' => 'contact', // or 'address' or 'order'
            'type'     => 'text',    // or 'select' or 'checkbox'
            'required' => false,
        )
    );
});
```

## Field Locations: Where Your Fields Appear

You can place your additional fields in three different locations:

### Contact Information (`contact`)

Fields here appear at the top of the checkout form alongside the email field. Data saved here becomes part of the customer’s account and will be visible in their “Account details” section.

Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/marketing-opt-in',
        'label'    => __( 'Subscribe to our newsletter?', 'your-text-domain' ),
        'location' => 'contact',
        'type'     => 'checkbox',
    )
);
```

### Address (`address`)

These fields appear in both the shipping and billing address forms. They’re saved to both the customer and the order, so returning customers won’t need to refill them.

Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/delivery-instructions',
        'label'    => __( 'Special delivery instructions', 'your-text-domain' ),
        'location' => 'address',
        'type'     => 'text',
    )
);
```

### Order Information (`order`)

Fields in this location appear in a separate “Order information” block and are saved only to the order, not the customer’s account. Perfect for order-specific details that don’t need to be remembered for future purchases.

Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/gift-message',
        'label'    => __( 'Gift message', 'your-text-domain' ),
        'location' => 'order',
        'type'     => 'text',
    )
);
```

## Supported Field Types

You can use the following field types:

### Text Fields

```php
'type' => 'text'
```

### Select Dropdowns

```php
'type'    => 'select',
'options' => array(
    'option1' => __( 'Option 1', 'your-text-domain' ),
    'option2' => __( 'Option 2', 'your-text-domain' ),
)
```

### Checkboxes

```php
'type' => 'checkbox'
```

## Adding Field Attributes

You can add additional attributes to your fields, such as placeholders, default values, and custom classes.

Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'          => 'my-plugin/custom-field',
        'label'       => __( 'Custom Field', 'your-text-domain' ),
        'location'    => 'order',
        'type'        => 'text',
        'required'    => true,
        'placeholder' => __( 'Enter your value here', 'your-text-domain' ),
        'default'     => 'Default Value',
        'class'       => array( 'form-row-wide' ),
    )
);
```

## Validation and Sanitization

To ensure the data entered into your custom fields is valid and secure, you can add custom validation and sanitization functions.

### Sanitization

Use the `woocommerce_sanitize_additional_field` filter to sanitize field values.

Example:

```php
add_filter( 'woocommerce_sanitize_additional_field', function( $field_value, $field_key ) {
    if ( 'my-plugin/custom-field' === $field_key ) {
        $field_value = sanitize_text_field( $field_value );
    }
    return $field_value;
}, 10, 2 );
```

### Validation

Use the `woocommerce_validate_additional_field` action to validate field values.

Example:

```php
add_action( 'woocommerce_validate_additional_field', function( $field_key, $field_value, $error ) {
    if ( 'my-plugin/custom-field' === $field_key && empty( $field_value ) ) {
        $error->add( $field_key, __( 'This field is required.', 'your-text-domain' ) );
    }
}, 10, 3 );
```

## Accessing Field Values

To retrieve the values of your custom fields from an order, use the `get_meta` method.

Example:

```php
$order = wc_get_order( $order_id );
$custom_field_value = $order->get_meta( 'my-plugin/custom-field' );
```

## Next Steps

Now that you've learned how to add additional fields to the WooCommerce Checkout Block, consider exploring more advanced features such as conditional field visibility, dynamic field values, and integrating with third-party services.

For more information, refer to the [WooCommerce developer documentation](https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/).

---

*Note: This Markdown file is based on the blog post by Code by Tom. For the original post and more details, visit [Code by Tom's blog](https://codebytom.blog/2025/05/30/how-to-add-additional-fields-to-the-woocommerce-checkout-block/).*
