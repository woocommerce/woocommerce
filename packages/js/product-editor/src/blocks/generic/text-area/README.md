# woocommerce/product-text-area-field

A reusable text area field for the product editor, enhancing product data input with a multiline text area suitable for detailed information, descriptions, and more.

## Attributes

### label

-   **Type:** `String`
-   **Required:** `Yes`

The label appears above the textarea field.

### property

-   **Type:** `String`
-   **Required:** `Yes`

The product entity property where the value is stored.

### help

-   **Type:** `String`
-   **Required:** `No`

Help text that appears below the field, providing additional guidance to the user.

### required

-   **Type:** `Boolean`|`String`
-   **Required:** `No`

Indicates that the field is required.

### tooltip

-   **Type:** `String`
-   **Required:** `No`

Shows a tooltip next to the label with additional information when provided.

### placeholder

-   **Type:** `String`
-   **Required:** `No`

Placeholder text that appears within the textarea when it is empty.

### mode

-   **Type:** `String`
-   **Required:** `No`
-   **Default:** `'rich-text'`

Defines the editing mode of the textarea. Options are `'plain-text'` or `'rich-text'`.

### allowedFormats

-   **Type:** `Array`
-   **Required:** `No`
-   **Default:** `['core/bold', 'core/italic', 'core/link', etc.]`

Specifies the allowed formatting options when in 'rich-text' mode. 

### direction

-   **Type:** `String`
-   **Required:** `No`
-   **Default:** `'ltr'`
-   **Options:** `'ltr'`, `'rtl'`

The text directionality for the textarea, supporting left-to-right (ltr) or right-to-left (rtl) content.

### align

-   **Type:** `String`
-   **Required:** `No`
-   **Options:** `'left'`, `'center'`, `'right'`, `'justify'`

Aligns the text within the text area field according to the specified value.

## Usage

Here's a snippet that adds a text area field to the product editor, similar to the screenshot:

```php
$section->add_block(
  array(
    'id'         => 'example-text-area-meta',
    'blockName'  => 'woocommerce/product-text-area-field',
    'order'      => 15,
    'attributes' => array(
      'label'       => 'Detailed Description',
      'property'    => 'short_description',
      'placeholder' => 'Enter a short product information here',
      'required'    => false,
      'help'        => 'Add any additional details or information that customers should know.',
      'mode'        => 'rich-text',
      'allowedFormats' => [ 'core/bold', 'core/italic', 'core/link' ],
    ),
  )
);
