# WooCommerce Product Blocks <!-- omit in toc -->

[![Latest Tag](https://img.shields.io/github/tag/woocommerce/woocommerce-gutenberg-products-block.svg?style=flat&label=Latest%20Tag)](https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases)
[![Travis](https://travis-ci.com/woocommerce/woocommerce-gutenberg-products-block.svg?branch=main)](https://travis-ci.com/woocommerce/woocommerce-gutenberg-products-block)
[![View](https://img.shields.io/badge/Project%20Components-brightgreen.svg?style=flat)](https://woocommerce.github.io/woocommerce-gutenberg-products-block)

This is the feature plugin for WooCommerce + Gutenberg. This plugin serves as a space to iterate and explore new Blocks and updates to existing blocks for WooCommerce, and how WooCommerce might work with the block editor.

Use this plugin if you want access to the bleeding edge of available blocks for WooCommerce. However, stable blocks are bundled into WooCommerce, and can be added from the "WooCommerce" section in the block inserter.

## Table of Contents <!-- omit in toc -->

- [Documentation](#documentation)
  - [Code Documentation](#code-documentation)
  - [Contributing](#contributing)
- [Installing the plugin version](#installing-the-plugin-version)
- [Installing the development version](#installing-the-development-version)
- [Getting started with block development](#getting-started-with-block-development)
- [Vision for the Feature](#vision-for-the-feature)

## Documentation

To find out more about the blocks and how to use them, [check out the documentation on WooCommerce.com](https://docs.woocommerce.com/document/woocommerce-blocks/).

If you want to see what we're working on for future versions, or want to help out, read on.

### Code Documentation

- [Blocks](./assets/js/blocks)
- [Editor Components](assets/js/editor-components)
- [Other Docs](./docs)

### Contributing

- [Publishing a release](docs/releases/readme.md)

## Installing the plugin version

We release a new version of WooCommerce Blocks onto WordPress.org every few weeks, which can be used as an easier way to preview the features.

> Note: The plugin follows a policy of supporting the "L2" strategy for version support. What this means is that the plugin will support the most recent two minor versions of WordPress, and the most recent two minor versions of WooCommerce core at the time of a release. 
>
> That means if the latest version of WooCommerce is 4.3 at the time of a release, then our minimum version requirements for WooCommerce core would be 4.1+. 
>
> We **recommend** you always keep WordPress and WooCommerce core up to date in order to ensure your store is running with the most recent fixes and enhancements to help your store be successful.

1. Make sure you have WordPress 5.3+ and WooCommerce 4.0+
2. The plugin version is available on WordPress.org. [Download the plugin version here.](https://wordpress.org/plugins/woo-gutenberg-products-block/)
3. Activate the plugin.

## Installing the development version

1. Make sure you have WordPress 5.3+ and WooCommerce 4.0+
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
