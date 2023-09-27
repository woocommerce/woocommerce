# Common tasks

> ⚠️ **Notice:** This documentation is currently a **work in progress**. Please be aware that some sections might be incomplete or subject to change. We appreciate your patience and welcome any contributions!

## Adding a group/section/field next to an existing one

Here's a snippet that adds a new block to the catalog section for simple products, between the first and second fields (order 15):

```php
<?php

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

if ( ! function_exists( 'YOUR_PREFIX_add_block_after_categories' ) ) {
	/**
	 * Add a new block to the template.
	 */
	function YOUR_PREFIX_add_block_after_categories( BlockInterface $product_categories_field ) {
    $product_categories_field->get_parent()->add_block(
      [
        'id'         => 'your-checkbox-id',
        'blockName'  => 'woocommerce/product-checkbox-field',
        'order'      => $product_categories_field->get_order() + 1,
        'attributes' => [
          'label'   => 'Your Checkbox',
					'value'   => 'meta_data.your_checkbox',
        ],
      ]
    );
	}
}
add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-categories', 'YOUR_PREFIX_add_block_after_categories' );
```

Result:
![Adding field next to other field](https://woocommerce.files.wordpress.com/2023/09/adding-field-next-to-other-field.png)
