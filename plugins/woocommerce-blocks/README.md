# WooCommerce Blocks <!-- omit in toc -->

[![Latest Tag](https://img.shields.io/github/tag/woocommerce/woocommerce-gutenberg-products-block.svg?style=flat&label=Latest%20Tag)](https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases)
[![View](https://img.shields.io/badge/Project%20Components-brightgreen.svg?style=flat)](https://woocommerce.github.io/woocommerce-blocks/)
![JavaScript and CSS Linting](https://github.com/woocommerce/woocommerce-gutenberg-products-block/workflows/JavaScript%20and%20CSS%20Linting/badge.svg?branch=trunk)
![PHP Coding Standards](https://github.com/woocommerce/woocommerce-gutenberg-products-block/workflows/PHP%20Coding%20Standards/badge.svg?branch=trunk)
![Automated tests](https://github.com/woocommerce/woocommerce-gutenberg-products-block/workflows/Automated%20tests/badge.svg?branch=trunk)

This is the feature plugin for WooCommerce + Gutenberg. This plugin serves as a space to iterate and explore new Blocks and updates to existing blocks for WooCommerce, and how WooCommerce might work with the block editor.

Use this plugin if you want access to the bleeding edge of available blocks for WooCommerce. However, stable blocks are bundled into WooCommerce, and can be added from the "WooCommerce" section in the block inserter.

-   [WCCOM product page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/)
-   [User documentation](https://docs.woocommerce.com/document/woocommerce-blocks/)

## Table of Contents <!-- omit in toc -->

-   [Documentation](#documentation)
    -   [Code Documentation](#code-documentation)
-   [Installing the plugin version](#installing-the-plugin-version)
-   [Installing the development version](#installing-the-development-version)
-   [Getting started with block development](#getting-started-with-block-development)
-   [Long-term vision](#long-term-vision)

## Documentation

To find out more about the blocks and how to use them, [check out the documentation on WooCommerce.com](https://docs.woocommerce.com/document/woocommerce-blocks/).

If you want to see what we're working on for future versions, or want to help out, read on.

### Code Documentation

-   [Blocks](./assets/js/blocks) - Documentation for specific Blocks.
-   [Editor Components](assets/js/editor-components) - Shared components used in WooCommerce blocks for the editor (Gutenberg) UI.
-   [WooCommerce Blocks Handbook](./docs) - Documentation for designers and developers on how to extend or contribute to blocks, and how internal developers should handle new releases.
-   [WooCommerce Blocks Storybook](https://woocommerce.github.io/woocommerce-blocks/) - Contains a list and demo of components used in the plugin.

## Installing the plugin version

We release a new version of WooCommerce Blocks onto WordPress.org every few weeks, which can be used as an easier way to preview the features.

> Note: The plugin follows a policy of supporting the "L0" strategy for version support. What this means is that the plugin will require the most recent version of WordPress, and the most recent version of WooCommerce core at the time of a release. You can read more about [this policy here](https://developer.woocommerce.com/?p=9998).

1. Make sure you have the latest available versions of WordPress and WooCommerce on your site.
2. The plugin version is available on WordPress.org. [Download the plugin version here.](https://wordpress.org/plugins/woo-gutenberg-products-block/)
3. Activate the plugin.

## Installing the development version

1. Make sure you have the latest versions of WordPress and WooCommerce on your site.
2. Get a copy of this plugin using the green "Clone or download" button on the right.
3. Make sure you're using Node.js v16.
4. `npm install` to install the dependencies.
5. `composer install` to install core dependencies.
6. To compile the code, run any of the following commands
    1. `npm run build` (production build).
    2. `npm run dev` (development build).
    3. `npm start` (development build + watching for changes).
7. Activate the plugin.

The source code is in the `assets/` folder and the compiled code is built into `build/`.

## Getting started with block development

Run through the ["Writing Your First Block Type" tutorial](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/writing-your-first-block-type/) for a quick course in block-building.

For deeper dive, try looking at the [core blocks code,](https://github.com/WordPress/gutenberg/tree/master/packages/block-library/src) or see what [components are available.](https://github.com/WordPress/gutenberg/tree/master/packages/components/src)

To begin contributing to the WooCommerce Blocks plugin, see our [getting started guide](./docs/contributors/contributing/getting-started.md) and [developer handbook](./docs/README.md).

Other useful docs to explore:

-   [`InnerBlocks`](https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inner-blocks/README.md)
-   [`apiFetch`](https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-api-fetch/)
-   [`@wordpress/editor`](https://github.com/WordPress/gutenberg/blob/master/packages/editor/README.md)
-   [`@wordpress/create-block`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/)

## Long-term vision

WooCommerce Blocks are the easiest, most flexible way to build your store user interface and showcase your products.
