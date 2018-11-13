# WooCommerce Product Blocks

Feature plugin for the Gutenberg Products block.

## Getting started with the stable version:

1. The stable version is available on WordPress.org. [Download the stable version here.](https://wordpress.org/plugins/woo-gutenberg-products-block/)
2. Activate the plugin.
3. On Gutenberg posts you should now have a Products block available.

## Getting started with the development version:
1. Make sure you have:
  - the latest version of the Gutenberg plugin and WooCommerce 3.3.1+ installed and active
  - ***OR*** WordPress 5.0 (beta) and WooCommerce 3.5.1+
2. Get a copy of this plugin using the green "Clone or download" button on the right.
3. `npm install` to install the dependencies.
4. `npm run build` (build once) or `npm start` (keep watching for changes) to compile the code.
5. Activate the plugin.
6. On Gutenberg posts & pages you should now have a "Products" block available.

The source code is in the `assets/js/products-block.jsx` file and the compiled code is in `build/products-block.js`.

**Gutenberg Tutorial and Docs**: https://wordpress.org/gutenberg/handbook/blocks/

**Using API in Gutenberg**: https://github.com/WordPress/gutenberg/tree/master/packages/api-fetch

## Vision for the Feature

Users should be able to insert a variety of products from their store (specific products, products in a category, with assorted layouts and visual styles, etc.) into their post content using a simple and powerful visual editor.
