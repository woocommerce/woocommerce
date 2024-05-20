---
post_title: Product Editor Guidelines - Fields
menu_title: Fields
---

Fields are the simplest type of extension. They let users add extra product information, replace or manage the visibility of other fields assigned to a specific product type, and control the contents of other fields.

![Fields example](https://developer.woocommerce.com/wp-content/uploads/2023/12/product-editor-ext-guidelines-fields.png)

## What they are for

- Single-field, supplementary features
- Showing or hiding form elements depending on specific conditions

## What they aren't for

- Multi-field or multi-step forms
- Complex tables, e.g., permissions, restrictions, shipping volumes, etc
- Embedded third-party experiences and websites

Field extensions should always be logically related to the form area they are in. For example, if you're building a dropshipping extension, your warehouse selection field should live in the first section of the Inventory group. To ensure an excellent experience for our merchants, we do not recommend placing it in a separate group, section, or subsection.

## Other use cases include

- Adding extra product details, f.e. volume under Shipping
- Entering custom data, f.e. color or date and time
- Selecting from a third-party system, f.e. warranty type
