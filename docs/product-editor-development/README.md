# Product Editor Development Handbook

> ⚠️ **Notice:** This documentation is currently a **work in progress**. Please be aware that some sections might be incomplete or subject to change. We appreciate your patience and welcome any contributions!

This handbook is a guide for extension developers looking to add support for the new product editor in their extensions. The product editor uses [Gutenberg's Block Editor](https://github.com/WordPress/gutenberg/tree/trunk/packages/block-editor), which is going to help WooCommerce evolve alongside the WordPress ecosystem.

The product editor's UI consists of Groups (currently rendered as tabs), Sections, and Fields, which are all blocks.

![Product editor structure](https://woocommerce.files.wordpress.com/2023/09/groups-sections-fields.jpg)

The form's structure is defined in PHP using a Template, which is a tree structure of blocks. The template can be modified by using the Template API to add new Groups, Sections, and Fields as well as remove existing ones.

Many extensibility implementations can be done using only the PHP-based Block Template API alongside our library of [generic blocks](../../packages/js/product-editor/src/blocks/generic/README.md). More complex interactivity can be implemented using JavaScript and React (the same library used to implement the core blocks used in the product editor). [@woocommerce/create-product-editor-block](../../packages/js/create-product-editor-block/README.md) can help scaffold a development environment with JavaScript and React.

## Related documentation

- [Examples on Template API usage](../../plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/README.md)
- [Related hooks and Template API documentation](../../plugins/woocommerce/src/Admin/BlockTemplates/README.md)
- [Generic blocks documentation](../../packages/js/product-editor/src/blocks/generic/README.md)
