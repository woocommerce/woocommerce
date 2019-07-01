# WooCommerce Admin

This is a feature plugin for a modern, javascript-driven WooCommerce Admin experience.

---

:warning: This project is in active development, and is not ready for general use. You can follow the features in development by looking at the [project's issues](https://github.com/woocommerce/woocommerce-admin/issues). **We do not recommend running this on production sites.**

---

## Prerequisites

[Gutenberg](https://wordpress.org/plugins/gutenberg/) and [WooCommerce](https://wordpress.org/plugins/woocommerce/) should be installed prior to activating the WooCommerce Admin feature plugin.

For better debugging, it's also recommended you add `define( 'SCRIPT_DEBUG', true );` to your wp-config. This will load the unminified version of all libraries, and specifically the development build of React.

## Development

After cloning the repo, install dependencies with `npm install`. Now you can build the files using one of these commands:

 - `npm run build` : Build a production version
 - `npm start` : Build a development version, watch files for changes

There are also some helper scripts:

 - `npm run lint` : Run eslint over the javascript files
 - `npm run i18n` : Create a PHP file with the strings from the javascript files, [used to get around lack of JS support in WordPress.org](https://github.com/WordPress/packages/tree/master/packages/i18n#build).

## Dev Docs

There is a "devdocs" page which is useful for displaying components individually outside of the application. It can be viewed via a normal `npm start` build at `http://<your-wp-site>/wp-admin/admin.php?page=wc-admin&path=/devdocs`.

This is useful for viewing of [WooCommerce components](https://woocommerce.github.io/woocommerce-admin/#/components/) components and ad-hoc testing.
