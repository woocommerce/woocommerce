# woocommerce/product-collapsible

Container with collapsible inner blocks.

![Collapsible](https://woocommerce.files.wordpress.com/2023/09/woocommerceproduct-collapsible.png)

## Attributes

### toggleText

- **Type**: `string`
- **Required**: ` Yes`

The text to display on the toggle button.

### initialCollapsed

- **Type**: `boolean`
- **Required**: ` Yes`

Controls if the content is collapsed by default.

### persistRender

- **Type**: `boolean`
- **Required**: ` Yes`

Controls if content is rendered to the DOM even when collapsed.

## Usage

Here's the code that was used to create the example in the screenshot above:

```php
$product_inventory_advanced = $product_inventory_section->add_block(
  [
    'id'         => 'product-inventory-advanced',
    'blockName'  => 'woocommerce/product-collapsible',
    'attributes' => [
      'toggleText'       => __( 'Advanced', 'woocommerce' ),
      'initialCollapsed' => true,
      'persistRender'    => true,
    ],
  ]
);
$product_inventory_advanced->add_block(
  [
    // add block information here
  ]
)
```
