# WooCommerce Blocks <!-- omit in toc -->

This is the client for WooCommerce + Gutenberg. This package serves as a space to iterate and explore new Blocks and updates to existing blocks for WooCommerce, and how WooCommerce might work with the Block Editor.

## Table of Contents <!-- omit in toc -->

-   [Documentation](#documentation)
    -   [Code Documentation](#code-documentation)
-   [Getting started with block development](#getting-started-with-block-development)
-   [Long-term vision](#long-term-vision)

## Documentation

To find out more about the blocks and how to use them, [check out the documentation on WooCommerce.com](https://woo.com/document/woocommerce-blocks/).

If you want to see what we're working on for future versions, or want to help out, read on.

### Code Documentation

-   [Blocks](./assets/js/blocks) - Documentation for specific Blocks.
-   [Editor Components](assets/js/editor-components) - Shared components used in WooCommerce blocks for the editor (Gutenberg) UI.
-   [WooCommerce Blocks Handbook](./docs) - Documentation for designers and developers on how to extend or contribute to blocks, and how internal developers should handle new releases.
-   [WooCommerce Blocks Storybook](https://woocommerce.github.io/woocommerce-blocks/) - Contains a list and demo of components used in the plugin.

## Getting started with block development

Run through the ["Writing Your First Block Type" tutorial](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/writing-your-first-block-type/) for a quick course in block-building.

For deeper dive, try looking at the [core blocks code,](https://github.com/WordPress/gutenberg/tree/trunk/packages/block-library/src) or see what [components are available.](https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src)

Other useful docs to explore:

-   [`InnerBlocks`](https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/inner-blocks/README.md)
-   [`apiFetch`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/)
-   [`@wordpress/editor`](https://github.com/WordPress/gutenberg/blob/trunk/packages/editor/README.md)
-   [`@wordpress/create-block`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/)

## Long-term vision

WooCommerce Blocks are the easiest, most flexible way to build your store's user interface and showcase your products.
