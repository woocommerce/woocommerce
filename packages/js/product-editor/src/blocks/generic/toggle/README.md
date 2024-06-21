# woocommerce/product-text-area-field

A reusable toggle field for the product editor.

## Attributes

### label

-   **Type:** `String`
-   **Required:** `Yes`

The label appears above the field.

### property

-   **Type:** `String`
-   **Required:** `Yes`

The product entity property where the value is stored.

### help

-   **Type:** `String`
-   **Required:** `No`

Help text that appears below the field, providing additional guidance to the user.

### checkedHelp

-   **Type:** `String`
-   **Required:** `No`

Help text that appears below the field when the value is checked. Overrides the `help` attribute when the field is checked.

### uncheckedHelp

-   **Type:** `String`
-   **Required:** `No`

Help text that appears below the field when the value is unchecked. Overrides the `help` attribute when the field is unchecked.

### required

-   **Type:** `Boolean`|`String`
-   **Required:** `No`

Indicates that the field is required.

### disabled

-   **Type:** `Boolean`
-   **Required:** `No`

Indicates that the field is not editable.

### disabledCopy

-   **Type:** `String`
-   **Required:** `No`

Text that appears when the field is disabled.

### checkedValue

-   **Type:** `Object`
-   **Required:** `No`

The value that is stored when the field is checked.

### uncheckedValue

-   **Type:** `Object`
-   **Required:** `No`

The value that is stored when the field is unchecked.


## Usage

Here's a snippet that adds a text area field to the product editor:

```php
$section->add_block(
  array(
    'id'         => 'example-toggle-meta',
    'blockName'  => 'woocommerce/product-toggle-field',
    'order'      => 15,
    'attributes' => array(
      'property'       => 'virtual',
      'checkedValue'   => false,
      'uncheckedValue' => true,
      'label'          => __( 'This variation requires shipping or pickup', 'woocommerce' ),
      'uncheckedHelp'  => __( 'This variation will not trigger your customer\'s shipping calculator in cart or at checkout. This product also won\'t require your customers to enter their shipping details at checkout. <a href="https://woocommerce.com/document/managing-products/#adding-a-virtual-product" target="_blank" rel="noreferrer">Read more about virtual products</a>.', 'woocommerce' ),
    ),
  )
);
