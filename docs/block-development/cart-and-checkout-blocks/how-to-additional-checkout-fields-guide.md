---
post_title: How to Add Additional Fields
sidebar_label: How to add additional fields in checkout
---

# How to Add Additional Fields to the WooCommerce Checkout Block

This feature requires a minimum version of WooCommerce 8.9.0

The WooCommerce Checkout Block provides a powerful API for developers to add additional fields to collect information from customers during the checkout process. Whether you need to gather special delivery instructions, business details, or marketing preferences, additional checkout fields make it easy to extend your store’s functionality.

In this guide, we’ll walk through the process of adding your own additional fields to your checkout form and show you practical examples you can implement right away.

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
            'label'    => __( 'Your Field Label', 'your-text-domain'),
            'location' => 'contact', // or 'address' or 'order'
            'type'     => 'text',    // or 'select' or 'checkbox'
            'required' => false,
        )
    );
});
```

## Field Locations: Where Your Fields Appear

You can place your additional fields in three different locations:

![Additional field for contact](/img/doc_images/woo-local-checkout.png)

### Contact Information (`contact`)

Fields here appear at the top of the checkout form alongside the email field. Data saved here becomes part of the customer’s account and will be visible in their “Account details” section.

Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/marketing-opt-in',
        'label'    => __('Subscribe to our newsletter?', 'your-text-domain'),
        'location' => 'contact',
        'type'     => 'checkbox',
    )
);
```

![Additional field for contact](/img/doc_images/additional-field-contact.png)

### Address (`address`)

These fields appear in both the shipping and billing address forms. They’re saved to both the customer and the order, so returning customers won’t need to refill them.

Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/delivery-instructions',
        'label'    => __('Special delivery instructions', 'your-text-domain'),
        'location' => 'address',
        'type'     => 'text',
    )
);
```

![Additional field for address](/img/doc_images/additional-field-address.png)

### Order Information (`order`)

Fields in this location appear in a separate “Order information” block and are saved only to the order, not the customer’s account. Perfect for order-specific details that don’t need to be remembered for future purchases.

Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/gift-message',
        'label'    => __('Gift message', 'your-text-domain'),
        'location' => 'order',
        'type'     => 'text',
    )
);
```

![Additional field for order](/img/doc_images/additional-field-order.png)

## Supported Field Types

The API supports three field types:

### Text Fields

Perfect for collecting short text input:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/company-vat',
        'label'    => __('VAT Number', 'your-text-domain'),
        'location' => 'address',
        'type'     => 'text',
        'required' => true,
    )
);
```

### Select Dropdowns

Great for predefined options:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'       => 'my-plugin/preferred-delivery-time',
        'label'    => __('Preferred delivery time', 'your-text-domain'),
        'location' => 'order',
        'type'     => 'select',
        'options'  => array(
            array(
                'value' => 'morning',
                'label' => __('Morning (9AM - 12PM)', 'your-text-domain')
            ),
            array(
                'value' => 'afternoon',
                'label' => __('Afternoon (12PM - 5PM)', 'your-text-domain')
            ),
            array(
                'value' => 'evening',
                'label' => __('Evening (5PM - 8PM)', 'your-text-domain')
            ),
        ),
    )
);
```

### Checkboxes

Ideal for yes/no questions or opt-ins:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'           => 'my-plugin/age-verification',
        'label'        => __('I confirm I am over 18 years old', 'your-text-domain'),
        'location'     => 'contact',
        'type'         => 'checkbox',
        'required'     => true,
        'error_message' => __('You must be over 18 to place this order.', 'your-text-domain'),
    )
);
```

## Adding Field Attributes

You can enhance your fields with HTML attributes for better user experience:
Example:

```php
woocommerce_register_additional_checkout_field(
    array(
        'id'         => 'my-plugin/phone-number',
        'label'      => __('Alternative phone number', 'your-text-domain'),
        'location'   => 'contact',
        'type'       => 'text',
        'attributes' => array(
            'autocomplete' => 'tel',
            'pattern'      => '[0-9]{10}',
            'title'        => __('Please enter a 10-digit phone number', 'your-text-domain'),
            'placeholder'  => '1234567890',
        ),
    )
);
```

## Validation and Sanitization

To ensure the data entered into your custom fields is valid and secure, you can add custom validation and sanitization functions.

```php
add_action( 'woocommerce_init', function() {
    woocommerce_register_additional_checkout_field(
        array(
            'id'                => 'my-plugin/business-email',
            'label'             => __('Business Email', 'your-text-domain'),
            'location'          => 'contact',
            'type'              => 'text',
            'required'          => true,
            'sanitize_callback' => function( $value ) {
                return sanitize_email( $value );
            },
            'validate_callback' => function( $value ) {
                if ( ! is_email( $value ) ) {
                    return new WP_Error( 
                        'invalid_business_email', 
                        __('Please enter a valid business email address.', 'your-text-domain') 
                    );
                }
            },
        )
    );
});
```

### Validation

You can also use WordPress action hooks for validation:

```php
add_action( 'woocommerce_validate_additional_field', function( $errors, $field_key, $field_value ) {
    if ( 'my-plugin/business-email' === $field_key ) {
        if ( ! is_email( $field_value ) ) {
            $errors->add( 'invalid_business_email', __('Please enter a valid email address.', 'your-text-domain') );
        }
    }
}, 10, 3 );
```

## Accessing Field Values

After checkout, you can retrieve the field values using helper methods:

```php
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

$checkout_fields = Package::container()->get( CheckoutFields::class );
$order = wc_get_order( $order_id );

// Get a specific field value
$business_email = $checkout_fields->get_field_from_object( 
    'my-plugin/business-email', 
    $order, 
    'other' // Use 'billing' or 'shipping' for address fields
);

// Get all additional fields
$all_fields = $checkout_fields->get_all_fields_from_object( $order, 'other' );
```

### Complete Example

```php
add_action( 'woocommerce_init', function() {
    if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
        return;
    }

    // Company information
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'my-business-store/company-size',
            'label'    => __('Company size', 'your-text-domain'),
            'location' => 'contact',
            'type'     => 'select',
            'required' => true,
            'options'  => array(
                array( 'value' => '1-10', 'label' => __('1-10 employees', 'your-text-domain') ),
                array( 'value' => '11-50', 'label' => __('11-50 employees', 'your-text-domain') ),
                array( 'value' => '51-200', 'label' => __('51-200 employees', 'your-text-domain') ),
                array( 'value' => '200+', 'label' => __('200+ employees', 'your-text-domain') ),
            ),
        )
    );

    // Delivery preferences
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'my-business-store/requires-appointment',
            'label'    => __('Delivery requires appointment', 'your-text-domain'),
            'location' => 'address',
            'type'     => 'checkbox',
        )
    );

    // Order-specific notes
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'my-business-store/po-number',
            'label'    => __('Purchase Order Number', 'your-text-domain'),
            'location' => 'order',
            'type'     => 'text',
        )
    );
});
```

## Next Steps

You now have the foundation for adding additional checkout fields to your WooCommerce store using the checkout block.

The additional checkout fields API provides a robust foundation for customizing your checkout experience while maintaining compatibility with WooCommerce’s block-based checkout system. Start with simple fields and gradually add more sophisticated validation and conditional logic as your needs grow.
