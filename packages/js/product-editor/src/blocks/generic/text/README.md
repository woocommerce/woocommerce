# woocommerce/product-text-field

A reusable text field for the product editor.

![Product text field screenshot](https://woocommerce.files.wordpress.com/2023/10/woocommerceproduct-text-field.png)

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

### required

- **Type:** `Boolean`
- **Required:** `No`

Indicates and enforces that the field is required.

### tooltip

- **Type:** `String`
- **Required:** `No`

If provided, shows a tooltip next to the label with additional information.

### placeholder

- **Type:** `String`
- **Required:** `No`

Placeholder text that appears in the field when it's empty.

## Usage

Here's a snippet that adds a field similar to the previous screenshot:

```php
$section->add_block(
  [
      'id'         => 'example-text-meta',
      'blockName'  => 'woocommerce/product-text-field',
      'order'      => 13,
      'attributes' => [
        'label' => 'Text',
        'property' => 'meta_data.text',
        'placeholder' => 'Placeholder',
        'required' => true,
        'help' => 'Add additional information here',
        'tooltip' => 'My tooltip'
      ],
    ]
);
```

