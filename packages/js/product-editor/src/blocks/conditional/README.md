# woocommerce/conditional

Container to only conditionally render inner blocks.


## Attributes

### mustMatch

**Type**: `Record< string, Array< string > >`
**Required**: `Yes`

A list of requirements that must be met for the inner blocks to be rendered. The keys should reference properties from the product, and the values are possible values for that property so that the inner blocks are rendered.

## Usage

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
