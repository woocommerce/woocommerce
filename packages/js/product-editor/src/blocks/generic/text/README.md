# woocommerce/product-text-field

A reusable text field for the product editor.

![Product text field screenshot](https://woocommerce.files.wordpress.com/2023/10/woocommerceproduct-text-field.png)

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

### required

-   **Type:** `Boolean`|`String`
-   **Required:** `No`

Indicates and enforces that the field is required.
If the value is string it will be used as the custom error message.

### tooltip

-   **Type:** `String`
-   **Required:** `No`

If provided, shows a tooltip next to the label with additional information.

### placeholder

-   **Type:** `String`
-   **Required:** `No`

Placeholder text that appears in the field when it's empty.

### type

-   **Type:** `Object`
    -   `value`
        -   **Type:** `String`
        -   **Required:** `No`
        -   **Default:** `'text'`
    -   `message`
        -   **Type:** `String`
        -   **Required:** `No`
-   **Required:** `No`

Refers to the type of the input. The `value` can be [any valid input type](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#input_types). The message is used as a custom error `message` for the [typeMismatch](https://developer.mozilla.org/en-US/docs/Web/API/ValidityState/typeMismatch) validity.

### pattern

-   **Type:** `Object`
    -   `value`
        -   **Type:** `String`
        -   **Required:** `Yes`
    -   `message`
        -   **Type:** `String`
        -   **Required:** `No`
-   **Required:** `No`

Refers to the validation pattern of the input. The `value` can be [any valid regular expression](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/pattern). The `message` is used as a custom error message for the [patternMismatch](https://developer.mozilla.org/en-US/docs/Web/API/ValidityState/patternMismatch) validity.

### minLength

-   **Type:** `Object`
    -   `value`
        -   **Type:** `Number`
        -   **Required:** `Yes`
    -   `message`
        -   **Type:** `String`
        -   **Required:** `No`
-   **Required:** `No`

Refers to the minimum string length constraint of the input. The `value` can be [any positive integer](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/minLength). The `message` is used as a custom error message for the [tooShort](https://developer.mozilla.org/en-US/docs/Web/API/ValidityState/tooShort) validity.

### maxLength

-   **Type:** `Object`
    -   `value`
        -   **Type:** `Number`
        -   **Required:** `Yes`
    -   `message`
        -   **Type:** `String`
        -   **Required:** `No`
-   **Required:** `No`

Refers to the maximum string length constraint of the input. The `value` can be [any positive integer](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/maxLength). The `message` is used as a custom error message for the [tooLong](https://developer.mozilla.org/en-US/docs/Web/API/ValidityState/tooLong) validity.

### min

-   **Type:** `Object`
    -   `value`
        -   **Type:** `Number`
        -   **Required:** `Yes`
    -   `message`
        -   **Type:** `String`
        -   **Required:** `No`
-   **Required:** `No`

Refers to the [minimum](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/min) value that is acceptable and valid for the input containing the attribute. The `value` must be less than or equal to the value of the `max` attribute. The `message` is used as a custom error message for the [rangeUnderflow](https://developer.mozilla.org/en-US/docs/Web/API/ValidityState/rangeUnderflow) validity.

### max

-   **Type:** `Object`
    -   `value`
        -   **Type:** `Number`
        -   **Required:** `Yes`
    -   `message`
        -   **Type:** `String`
        -   **Required:** `No`
-   **Required:** `No`

Refers to the [maximum](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/max) value that is acceptable and valid for the input containing the attribute. The `value` must be greater than or equal to the value of the `min` attribute. The `message` is used as a custom error message for the [rangeOverflow](https://developer.mozilla.org/en-US/docs/Web/API/ValidityState/rangeOverflow) validity.

## Usage

Here's a snippet that adds a field similar to the previous screenshot:

```php
$section->add_block(
  array(
    'id'         => 'example-text-meta',
    'blockName'  => 'woocommerce/product-text-field',
    'order'      => 13,
    'attributes' => array(
      'label'       => 'Text',
      'property'    => 'meta_data.text',
      'placeholder' => 'Placeholder',
      'required'    => true,
      'help'        => 'Add additional information here',
      'tooltip'     => 'My tooltip',
    ),
  )
);
```

Here's a snippet that adds fields validations:

```php
$section->add_block(
  array(
    'id'         => 'product-external-url',
    'blockName'  => 'woocommerce/product-text-field',
    'order'      => 10,
    'attributes' => array(
      'property'    => 'external_url',
      'label'       => __( 'Link to the external product', 'woocommerce' ),
      'placeholder' => __( 'Enter the external URL to the product', 'woocommerce' ),
      'suffix'      => true,
      'type'        => array(
        'value'   => 'url',
        'message' => __( 'Link to the external product is an invalid URL.', 'woocommerce' ),
      ),
      'minLength'   => array(
        'value'   => 8,
        'message' => __( 'The link must be longer than %d.', 'woocommerce' ),
      ),
      'required'    => __( 'Link to the external product is required.', 'woocommerce' ),
    ),
  )
);
```
