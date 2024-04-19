---
post_title: Product editor development handbook
menu_title: Development handbook
---

This handbook is a guide for extension developers looking to add support for the new product editor in their extensions. The product editor uses [Gutenberg's Block Editor](https://github.com/WordPress/gutenberg/tree/trunk/packages/block-editor), which is going to help WooCommerce evolve alongside the WordPress ecosystem.

The product editor's UI consists of Groups (currently rendered as tabs), Sections, and Fields, which are all blocks. For guidelines on how to extend the product editor (e.g. where to add blocks), please refer to the [Product Editor Extensibility Guidelines](./product-editor-extensibility-guidelines.md).

![Product editor structure](https://developer.woocommerce.com/wp-content/uploads/sites/2/2023/09/groups-sections-fields.jpg)

The form's structure is defined in PHP using a Template, which is a tree structure of blocks. The template can be modified by using the Template API to add new Groups, Sections, and Fields as well as remove existing ones.

For more information about when to perform template modifications, see the [block template lifecycle](./block-template-lifecycle.md).

Many extensibility implementations can be done using only the PHP-based Block Template API alongside our library of [generic blocks](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/blocks/generic/README.md). More complex interactivity can be implemented using JavaScript and React (the same library used to implement the core blocks used in the product editor). [@woocommerce/create-product-editor-block](https://github.com/woocommerce/woocommerce/packages/js/create-product-editor-block/README.md) can help scaffold a development environment with JavaScript and React.

## Declaring compatibility with the product editor

To declare compatibility with the product editor, add the following to your plugin's root PHP file:

```php
use Automattic\WooCommerce\Utilities\FeaturesUtil;

add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'product_block_editor', plugin_basename( __FILE__ ), true );
		}
	}
);
```

Please note that this check is currently not being enforced: the product editor can still be enabled even without this declaration. However, we might enforce this in the future, so it is recommended to declare compatibility as soon as possible.

## Related documentation

- [Examples on Template API usage](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/README.md/)
- [Related hooks and Template API documentation](https://github.com/woocommerce/woocommerce/plugins/woocommerce/src/Admin/BlockTemplates/README.md)
- [Generic blocks documentation](https://github.com/woocommerce/woocommerce//packages/js/product-editor/src/blocks/generic/README.md)
