# @woocommerce/create-woo-extension

Create Woo Extension scaffolds a fully functional modern development environment for integrating with WooCommerce. Use it to build a extensions to leverage the power of WooCommerce.

## Leave the configuration up to us

With Javascript build tooling already configured, getting started developing modern screens requires no configuration. Create Woo Extension adds a React Page integrating with WooCommerce Admin. Also included are PHP and Javascript unit testing ,linting, and Prettier IDE confguration for WooCommercea and WordPress.

Drop the resulting project inside of your favorite environment setup or use the built in [wp-env](https://github.com/WordPress/gutenberg/tree/trunk/packages/env) for easily setting up a local WordPress environment for building and testing plugins and themes. `wp-env` is configured to load the lates WooCommerce to easily start developing with a single command.

## Installation

```
npx @wordpress/create-block -t @woocommerce/create-woo-extension my-extension-name
```

Navigate to the newly created folder and get started.

```
cd my-extension-name
npm install # Install dependencies
npm run build # Build the javascript
npm -g i @wordpress/env # If you don't already have wp-env
wp-env start # Start Wordpress environment
```

When this has completed, go to your WordPress plugins page and activate the plugin.

## Development

For development on this tool itself, you can also install from a local directory.

```
npx @wordpress/create-block -t ./path/to/woocommerce/packages/js/create-woo-extension my-extension-name
```

This is a template to used with [`@wordpress/create-block`](https://github.com/WordPress/gutenberg/tree/trunk/packages/create-block) to create a WooCommerce Extension starting point.
