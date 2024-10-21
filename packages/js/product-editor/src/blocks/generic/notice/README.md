# woocommerce/product-notice-field

A reusable notice field for the product editor.

## Attributes

### message

-   **Type:** `String`
-   **Required:** `Yes`

Message to display on the notice.

Here's a snippet:

```php
$section->add_block(
  array(
    'id'             => 'wc-bis-notices',
    'blockName'      => 'woocommerce/product-notice-field',
    'attributes'     => array(
      'message' => __(
        'Back In Stock Notifications are disabled. Enable them in the settings.',
        'woocommerce-back-in-stock-notifications'
      ),
    ),
  )
);
```
