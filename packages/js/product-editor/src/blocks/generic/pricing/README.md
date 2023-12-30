# woocommerce/product-pricing-field

A product price block with currency display.

![Product pricing field](https://woocommerce.files.wordpress.com/2023/09/woocommerceproduct-pricing-field.png)

## Attributes

### property

- **Type:** `String`
- **Required:** `Yes`

Property in which the price value is stored.

### label

- **Type:** `String`
- **Required:** `Yes`

Label that appears on top of the price field.

### help

- **Type:** `String`
- **Required:** `No`

Help text that appears below the price field.

### tooltip

-   **Type:** `String`
-   **Required:** `No`

Tooltip text that is shown when hovering the icon at the side of the label.

## Usage

Here's the code that adds the field from the screenshot after the Summary field:

```php
<?php

if ( ! function_exists( 'add_pricing_field' ) ) {
  function add_pricing_field( $product_summary_field ) {
    $product_summary_field->get_parent()->add_block(
      [
        'id'         => 'example-pricing-field',
        'blockName'  => 'woocommerce/product-pricing-field',
        'order'      => $product_summary_field->get_order() + 5,
        'attributes' => [
          'label'    => __( 'Example price field', 'woocommerce'),
          'property' => 'custom_price',
          'help'     => 'This is a help text',
          'tooltip'  => 'This is a tooltip',
        ],
      ]
    );
  }
}

if ( ! function_exists( 'example_hook_up_block_template_modifications_pricing' ) ) {
  function example_hook_up_block_template_modifications_pricing() {
    add_action(
      'woocommerce_block_template_area_product-form_after_add_block_product-summary',
      'add_pricing_field'
    );
  }
}

add_action( 'init', 'example_hook_up_block_template_modifications_pricing', 0 );
```
