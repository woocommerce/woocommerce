# woocommerce/product-select-field

A reusable select field for the product editor.

## Attributes

### label

-   **Type:** `String`
-   **Required:** `Yes`

Label that appears on top of the field.

### property

-   **Type:** `String`
-   **Required:** `Yes`

Property in which the value is stored.

### help

-   **Type:** `String`
-   **Required:** `No`

Help text that appears below the field.

### multiple

-   **Type:** `Boolean`
-   **Required:** `No`

Indicates where the select is of multiple choices or not.

### placeholder

-   **Type:** `String`
-   **Required:** `No`

Placeholder text that appears in the field when it's empty.

### disabled

-   **Type:** `Boolean`
-   **Required:** `No`

Indicates and enforces that the field is disabled.

### options

-   **Type:** `Array`
-   **Items:** `Object`
    -   `value`
        -   **Type:** `String`
        -   **Required:** `Yes`
    -   `label`
        -   **Type:** `String`
        -   **Required:** `Yes`
    -   `disabled`
        -   **Type:** `Boolean`
        -   **Required:** `No`
-   **Required:** `No`

Refers to the options of the select field.

## Usage

Here's a snippet that adds tax classes as options to the
single selection field:

```php
$section->add_block(
  array(
    'id'         => 'unique-block-id',
    'blockName'  => 'woocommerce/product-select-field',
    'order'      => 13,
    'attributes' => array(
      'label'    => 'Tax class',
      'property' => 'tax_class',
      'help'     => 'Apply a tax rate if this product qualifies for tax reduction or exemption.',
      'options'  => array(
        array(
          'value' => 'Standard rate',
          'label' => '',
        ),
        array(
          'value' => 'Reduced rate',
          'label' => 'reduced-rate',
        ),
        array(
          'value' => 'Zero rate',
          'label' => 'zero-rate',
        ),
      ),
    ),
  )
);
```
