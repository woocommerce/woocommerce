---
post_title: Email Preview Integration
menu_title: Email Preview Integration
---

<!-- markdownlint-disable MD024 -->

## Overview

WooCommerce's Email Preview feature allows you to preview email templates using dummy data (i.e., it doesn't use the actual data from the database). This means that extensions registering new email types need to integrate with the email preview system to properly showcase their emails. 

## Integration Hooks

The following hooks allow extensions to take full control of email preview:

### Style and Content Settings

These hooks allow extensions to add new style and content settings that the email preview listens for changes, and updates the preview when changes are made.

#### `woocommerce_email_preview_email_style_setting_ids`

Add new email style settings that the email preview listens for changes.

```php
add_filter( 'woocommerce_email_preview_email_style_setting_ids', function( $setting_ids ) {
    $setting_ids[] = 'my_extension_email_style';
    return $setting_ids;
} );
```

#### `woocommerce_email_preview_email_content_setting_ids`

Add new email content settings that the email preview listens for changes.

```php
add_filter( 'woocommerce_email_preview_email_content_setting_ids', function( $setting_ids ) {
    $setting_ids[] = 'my_extension_email_content';
    return $setting_ids;
} );
```

### Dummy Data Modification

#### `woocommerce_email_preview_dummy_order`

Modify the dummy `WC_Order` object used in preview.

```php
add_filter( 'woocommerce_email_preview_dummy_order', function( $order ) {
    // Modify the dummy order object
    $order->set_currency( 'EUR' );
    return $order;
} );
```

#### `woocommerce_email_preview_dummy_product`

Modify the dummy product used in preview.

```php
add_filter( 'woocommerce_email_preview_dummy_product', function( $product ) {
    // Modify the dummy product object
    $product->set_name( 'My Product' );
    return $product;
} );
```

#### `woocommerce_email_preview_dummy_product_variation`

Modify the dummy product variation used in preview.

```php
add_filter( 'woocommerce_email_preview_dummy_product_variation', function( $variation ) {
    // Modify the dummy variation object
    $variation->set_name( 'My Variation' );
    return $variation;
} );
```

#### `woocommerce_email_preview_dummy_address`

Modify the dummy address used in preview.

```php
add_filter( 'woocommerce_email_preview_dummy_address', function( $address ) {
    // Modify the dummy address array
    $address['first_name'] = 'Preview';
    $address['last_name'] = 'Customer';
    return $address;
} );
```

### Placeholders and Email Object

#### `woocommerce_email_preview_placeholders`

Modify placeholders to be replaced in the email.

```php
add_filter( 'woocommerce_email_preview_placeholders', function( $placeholders ) {
    // Add custom placeholders
    $placeholders['{custom_placeholder}'] = 'Custom Value';
    return $placeholders;
} );
```

#### `woocommerce_prepare_email_for_preview`

Modify the `WC_Email` object used in preview.

```php
add_filter( 'woocommerce_prepare_email_for_preview', function( $email ) {
    // Modify the email object, e.g. to replace WC_Order
    $email->set_object( $custom_object );
    return $email;
} );
```

## Best Practices

When integrating with email preview:

1. **Use Appropriate Object Types**
   - Use the correct object type for your extension (e.g., `WC_Subscription` instead of `WC_Order` for WooCommerce Subscriptions)
   - Ensure all required properties are set on dummy objects

2. **Handle Placeholders**
   - Add all custom placeholders your extension uses
   - Provide realistic preview values for placeholders

3. **Style and Content Settings**
   - Register all custom settings that affect email appearance or content

4. **Testing**
   - Test preview with different email types
   - Verify all custom content appears correctly
   - Check responsive design

## Troubleshooting

Common issues and solutions:

1. **I see "There was an error rendering an email preview." error**
   - There is a PHP error in your code that is causing the email preview to fail. Check your error log for more details.
   - Alternatively, you can modify [`class-wc-admin.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/admin/class-wc-admin.php) and remove [`try/catch` block](https://github.com/woocommerce/woocommerce/blob/f5310a33fbb160a73ea2de95efe4759c3aa791ea/plugins/woocommerce/includes/admin/class-wc-admin.php#L212-L218) around rendering the email preview to see the error message.

2. **Style or Content Settings Changes Not Reflecting**
   - Ensure the style settings are registered using the `woocommerce_email_preview_email_style_setting_ids` filter
   - Ensure the content settings are registered using the `woocommerce_email_preview_email_content_setting_ids` filter
3. **I don't see my extension's emails**
   - Ensure the email is registered using the `woocommerce_email_classes` filter
