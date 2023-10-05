# woocommerce/product-number-field

A reusable number field for the product editor.

![Product number field screenshot](https://woocommerce.files.wordpress.com/2023/10/woocommerceproduct-number-field.png)

## Attributes

### label

- **Type:** `String`
- **Required:** `Yes`

Label that appears on top of the field.

### property

- **Type:** `String`
- **Required:** `Yes`

Property in which the value is stored.


### help

- **Type:** `String`
- **Required:** `No`

Help text that appears below the field.

### suffix

- **Type:** `String`
- **Required:** `No`

Suffix that can be used for a unit of measure, as an example.


### placeholder

- **Type:** `String`
- **Required:** `No`

Placeholder text that appears in the field when it's empty.

## Usage

Here's a snippet that adds a field similar to the previous screenshot:

```php
$section->add_block(
  [
    'id'         => 'example-number-meta',
    'blockName'  => 'woocommerce/product-number-field',
    'attributes' => [
      'label' => 'Label',
      'property' => 'meta_data.number_field',
      'suffix' => 'suffix',
      'help' => 'Add additional information here',
      'placeholder' => 'Placeholder',
    ]
  ],
);
```

