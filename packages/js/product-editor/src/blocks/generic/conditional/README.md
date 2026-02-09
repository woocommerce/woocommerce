# woocommerce/conditional

> ⚠️ **Note:** `woocommerce/conditional` is deprecated. Use conditional visibility
support in the Block Template API instead, through the `hideConditions` attribute on
any block and the `BlockInterface::add_hide_condition()` method.

Container to only conditionally render inner blocks.

<video src="https://github.com/woocommerce/woocommerce/assets/13437655/ccf6888d-59bd-4f7c-9487-105e5e0d8166"></video>

## Attributes

### mustMatch

- **Type**: `Record< string, Array< string > >`
- **Required**: `Yes`

A list of requirements that must be met for the inner blocks to be rendered. The keys should reference properties from the product, and the values are possible values for that property so that the inner blocks are rendered.

## Usage

Here's the code that was used to create the example in the video above:

```php
$wrapper = $product_summary_field->get_parent()->add_block(
  [
    'id'         => 'example-conditional-wrapper',
    'blockName'  => 'woocommerce/conditional',
    'order'      => $product_summary_field->get_order() + 5,
    'attributes' => [
      'mustMatch' => [
        'name' => [ 'Car', 'Bike' ]
      ],
    ],
  ]
);
$wrapper->add_block(
  [
    'id'         => 'example-pricing-field',
    'blockName'  => 'woocommerce/product-pricing-field',
    'order'      => $product_summary_field->get_order() + 5,
    'attributes' => [
      'label'    => __( 'Example price field', 'woocommerce'),
      'property' => 'custom_price',
      'help'     => 'This is a help text',
    ],
  ]
);
```
