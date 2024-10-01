# woocommerce/product-radio-field block

Radio button field for the product editor.

![Product radio field](https://woocommerce.files.wordpress.com/2023/09/woocommerceproduct-radio-field.png)

## Attributes

### title

-   **Type:** `String`
-   **Required:** `Yes`

### description

-   **Type:** `String`
-   **Required:** `No`

### property

-   **Type:** `String`
-   **Required:** `Yes`

### options

-   **Type:** `Array`
-   **Required:** `Yes`

### disabled

-   **Type:** `Boolean`
-   **Required:** `No`

## Usage

Here's an example of the usage on the "Charge sales tax on" field in the Pricing section:

```php
$product_pricing_section->add_block(
  [
    'id'         => 'product-sale-tax',
    'blockName'  => 'woocommerce/product-radio-field',
    'order'      => 30,
    'attributes' => [
      'title'    => __( 'Charge sales tax on', 'woocommerce' ),
      'property' => 'tax_status',
      'options'  => [
        [
          'label' => __( 'Product and shipping', 'woocommerce' ),
          'value' => 'taxable',
        ],
        [
          'label' => __( 'Only shipping', 'woocommerce' ),
          'value' => 'shipping',
        ],
        [
          'label' => __( "Don't charge tax", 'woocommerce' ),
          'value' => 'none',
        ],
      ],
    ],
  ]
);
```
