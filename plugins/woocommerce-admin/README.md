# WooCommerce Admin

This is a feature plugin for a modern, javascript-driven WooCommerce Admin experience.

---

:warning: This project is in active development, and is not ready for general use. You can follow the features in development by looking at the [project's issues](https://github.com/woocommerce/wc-admin/issues). **We do not recommend running this on production sites.**

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
 - `npm run i18n` : A multi-step process, used to create a pot file from both the JS and PHP gettext calls. First it runs `i18n:js`, which creates a temporary `.pot` file from the JS files. Next it runs `i18n:php`, which converts that `.pot` file to a PHP file. Lastly, it runs `i18n:pot`, which creates the final `.pot` file from all the PHP files in the plugin (including the generated one with the JS strings).
