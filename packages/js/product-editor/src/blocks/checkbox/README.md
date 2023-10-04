# woocommerce/product-checkbox-field

A reusable checkbox for the product editor.

![Product checkbox field screenshot](https://woocommerce.files.wordpress.com/2023/09/checkbox.png)

_Please note that to persist a custom field in the product it also needs to be added to the WooCommerce REST API._

## Attributes

### title

- **Type:** `String`
- **Required:** `No`

Header that appears above the checkbox.

### label

- **Type:** `String`
- **Required:** `No`

Label that appears at the side of the checkbox.

### property

- **Type:** `String`
- **Required:** `Yes`

Property in which the checkbox value is stored.

### tooltip

- **Type:** `String`
- **Required:** `No`

Tooltip text that is shown when hovering the icon at the side of the label.

## Usage

Here's an example on the code that is used for the 'sold_individually' field in the Inventory section:

```php
$parent_container->add_block(
  [
    'id'         => 'product-limit-purchase',
    'blockName'  => 'woocommerce/product-checkbox-field',
    'order'      => 20,
    'attributes' => [
      'title'    => __(
        'Restrictions',
        'woocommerce'
      ),
      'label'    => __(
        'Limit purchases to 1 item per order',
        'woocommerce'
      ),
      'property' => 'sold_individually',
      'tooltip'  => __(
        'When checked, customers will be able to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods.',
        'woocommerce'
      ),
    ],
  ]
);
```

