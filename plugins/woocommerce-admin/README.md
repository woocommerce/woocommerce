# WooCommerce Admin

This is a feature plugin for a modern, javascript-driven WooCommerce Admin experience.

---

:warning: This project is in active development, and is not ready for general use. You can follow the features in development by looking at the [project's issues](https://github.com/woocommerce/woocommerce-admin/issues). **We do not recommend running this on production sites.**

---

## Prerequisites

[WordPress 5.2 or greater](https://wordpress.org/download/) and [WooCommerce 3.6.0 or greater](https://wordpress.org/plugins/woocommerce/) should be installed prior to activating the WooCommerce Admin feature plugin.

For better debugging, it's also recommended you add `define( 'SCRIPT_DEBUG', true );` to your wp-config. This will load the unminified version of all libraries, and specifically the development build of React.

## Development

After cloning the repo, install dependencies with `npm install`. Now you can build the files using one of these commands:

 - `npm run build` : Build a production version
 - `npm run dev` : Build a development version
 - `npm start` : Build a development version, watch files for changes
 - `npm run build:release` : Build a WordPress plugin ZIP file (`woocommerce-admin.zip` will be created in the repository root)

There are also some helper scripts:

 - `npm run lint` : Run eslint over the javascript files
 - `npm run i18n` : A multi-step process, used to create a pot file from both the JS and PHP gettext calls. First it runs `i18n:js`, which creates a temporary `.pot` file from the JS files. Next it runs `i18n:php`, which converts that `.pot` file to a PHP file. Lastly, it runs `i18n:pot`, which creates the final `.pot` file from all the PHP files in the plugin (including the generated one with the JS strings).
 - `npm test` : Run the JS test suite

 To debug synced lookup information in the database, you can bypass the action scheduler and immediately sync order and customer information by using the `woocommerce_disable_order_scheduling` hook.

`add_filter( 'woocommerce_disable_order_scheduling', '__return_true' );`

## Privacy

If you have enabled WooCommerce usage tracking ( option `woocommerce_allow_tracking` ) then, in addition to the tracking described in https://woocommerce.com/usage-tracking/, this plugin also sends information about the actions that site administrators perform to Automattic - see https://automattic.com/privacy/#information-we-collect-automatically for more information.

## Contributing

There are many ways to contribute – reporting bugs, adding translations, feature suggestions and fixing bugs. For full details, please see [CONTRIBUTING.md](./CONTRIBUTING.md)
