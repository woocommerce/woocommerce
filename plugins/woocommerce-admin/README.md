# WooCommerce Admin

This is a feature plugin for a modern, javascript-driven WooCommerce Admin experience.

## Prerequisites

[Gutenberg](https://wordpress.org/plugins/gutenberg/) and [WooCommerce](https://wordpress.org/plugins/woocommerce/) should be installed prior to activating the WooCommerce Admin feature plugin. You also need v3 of the WooCommerce REST API via [wc-api-dev](https://github.com/woocommerce/wc-api-dev/) or [the feature branch](https://github.com/woocommerce/woocommerce/tree/feature/rest-api-v3).

For better debugging, it's also recommended you add `define( 'SCRIPT_DEBUG', true );` to your wp-config. This will load the unminified version of all libraries, and specifically the development build of React.

## Development

After cloning the repo, install dependencies with `npm install`. Now you can build the files using one of these commands:

 - `npm run build` : Build a production version
 - `npm start` : Build a development version, watch files for changes

There are also some helper scripts:

 - `npm run lint` : Run eslint over the javascript files
 - `npm run i18n` : Create a PHP file with the strings from the javascript files, [used to get around lack of JS support in WordPress.org](https://github.com/WordPress/packages/tree/master/packages/i18n#build).
