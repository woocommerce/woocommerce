# woocommerce/product-number-field

A reusable number field for the product editor.

![Product number field screenshot](https://woocommerce.files.wordpress.com/2023/10/woocommerceproduct-number-field-1.png)

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

### suffix

- **Type:** `String`
- **Required:** `No`

Suffix that can be used for a unit of measure, as an example.


### placeholder

- **Type:** `String`
- **Required:** `No`

Placeholder text that appears in the field when it's empty.

### min

- **Type:** `Number`
- **Required:** `No`

The minimum numeric value that can be entered in the field.

### max

- **Type:** `Number`
- **Required:** `No`

The maximum numeric value that can be entered in the field.

## Usage

Here's a snippet that adds a field similar to the previous screenshot:

```php
$section->add_block(
  [
    'id'         => 'example-number-meta',
    'blockName'  => 'woocommerce/product-number-field',
    'attributes' => [
      'label' => 'Label',
      'property' => 'meta_data.number',
      'suffix' => 'suffix',
      'placeholder' => 'Placeholder',
      'required' => true,
      'help' => 'Add additional information here',
      'tooltip' => 'Tooltip information here'
    ]
  ],
);
```

