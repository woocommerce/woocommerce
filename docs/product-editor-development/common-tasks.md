# Common tasks

> ⚠️ **Notice:** This documentation is currently a **work in progress**. While it's open to the public for transparency and collaboration, please be aware that some sections might be incomplete or subject to change. We appreciate your patience and welcome any contributions!

## Adding a new field to the product editor (end-to-end)

_TODO: Describe how to add a new field, including adding it to the REST API._

## Adding a group/section/field next to an existing one

Here's a snippet that adds a new block to the catalog section for simple products, between the first and second fields (order 15):

```php
if ( ! function_exists( 'YOUR_PREFIX_add_block_after_categories' ) ) {
	/**
	 * Add a new block to the template.
	 */
	function YOUR_PREFIX_add_block_after_categories( BlockInterface $product_categories_field ) {
    $product_categories_field->get_parent()->add_block(
      [
        'id'         => 'your-prefix-id',
        'blockName'  => 'your-block-name',
        'order'      => $product_categories_field->get_order() + 5,
        'attributes' => [
          'key'   => 'value',
        ],
      ]
    );
	}
	add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-categories', 'YOUR_PREFIX_add_block_after_categories' );
```

Result:
![Adding field next to other field](https://woocommerce.files.wordpress.com/2023/09/adding-field-next-to-other-field.png)

## Hiding a group/section/field

## Reordering groups/sections/fields
