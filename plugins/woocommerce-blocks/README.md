# WooCommerce Product Blocks

Feature plugin for WooCommerce + Gutenberg. This plugin serves as a space to iterate and explore new Blocks for WooCommerce, and how WooCommerce might work with the block editor.

If you're just looking to use these blocks, update to the latest version of WooCommerce! The blocks are bundled into WooCommerce since version 3.6, and can be added from the "WooCommerce" section in the block inserter.

## Documentation

To find out more about the blocks and how to use them, [check out the documentation on WooCommerce.com](https://docs.woocommerce.com/document/woocommerce-blocks/).

If you want to see what we're working on for future versions, or want to help out, read on.

## Table of Contents

-   [Installing the stable version](#installing-the-stable-version)
-   [Installing the development version](#installing-the-development-version)
-   [Getting started with block development](#getting-started-with-block-development)
-   [Contributing](CONTRIBUTING.md)
    -   [About the npm scripts](CONTRIBUTING.md#npm-scripts)
    -   [Publishing a release](CONTRIBUTING.md#publishing-woocommerceblock-library)
-   Code Documentation
    -   [Blocks](assets/js/blocks)
    -   [Components](assets/js/components)

## Installing the stable version

We release a new version of WooCommerce Blocks onto WordPress.org every few weeks, which can be used as an easier way to preview the features.

1. Make sure you have WordPress 5.0+ and WooCommerce 3.7+
2. The stable version is available on WordPress.org. [Download the stable version here.](https://wordpress.org/plugins/woo-gutenberg-products-block/)
3. Activate the plugin.

## Installing the development version

1. Make sure you have WordPress 5.0+ and WooCommerce 3.7+
2. Get a copy of this plugin using the green "Clone or download" button on the right.
3. `npm install` to install the dependencies.
4. `composer install` to install core dependencies.
5. `npm run build` (build once) or `npm start` (keep watching for changes) to compile the code.
6. Activate the plugin.

The source code is in the `assets/` folder and the compiled code is built into `build/`.

## Getting started with block development

Run through the ["Writing Your First Block Type" tutorial](https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/) for a quick course in block-building.

For deeper dive, try looking at the [core blocks code,](https://github.com/WordPress/gutenberg/tree/master/packages/block-library/src) or see what [components are available.](https://github.com/WordPress/gutenberg/tree/master/packages/components/src)

To begin contributing to the WooCommerce Blocks plugin, see our [getting started guide](./docs/contributors/getting-started.md) and [developer handbook](./docs/readme.md).

Other useful docs to explore:

-   [`InnerBlocks`](https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inner-blocks/README.md)
-   [`apiFetch`](https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-api-fetch/)
-   [`@wordpress/editor`](https://github.com/WordPress/gutenberg/blob/master/packages/editor/README.md)

## Vision for the Feature

Users should be able to insert a variety of products from their store (specific products, products in a category, with assorted layouts and visual styles, etc.) into their post content using a simple and powerful visual editor.
