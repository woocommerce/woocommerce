# @woocommerce/create-woo-extension

Create Woo Extension scaffolds a fully functional modern development environment for integrating with WooCommerce. Use it to build extensions leveraging the power of WooCommerce.

## Configuration-Free

With build tooling already configured, getting started developing modern Javascript screens requires no effort. Create Woo Extension adds a React page integrating with WooCommerce Admin. Also included are PHP and Javascript unit testing, linting, and Prettier IDE confguration for WooCommerce and WordPress.

Add a project inside of your favorite environment setup or use the built in [wp-env](https://github.com/WordPress/gutenberg/tree/trunk/packages/env) for easily setting up a local WordPress environment for building and testing plugins and themes. `wp-env` is configured to load the latest WooCommerce to easily start developing with a single command.

## Installation

When installing from NPM:

```
npm install -g @woocommerce/create-woo-extension
```

When using the WooCommerce repository, e.g., for developing on the tool itself: 

```
cd packages/js/create-woo-extension
npm link @woocommerce/create-woo-extension
```

The `create-woo-extension` CLI tool should now be available globally on your machine. 

## Usage

```
create-woo-extension new my-extension-name
```

Navigate to the newly created folder and get started.

```
cd my-extension-name
npm install # Install dependencies
npm run build # Build the javascript
npm -g i @wordpress/env # If you don't already have wp-env
wp-env start # Start Wordpress environment
```

See the new plugin activated from the WordPress plugins page and navigate to http://localhost:8888/wp-admin/admin.php?page=wc-admin&path=%2Fmy-extension-name to check it out.

## Development

When updating this tool, please make sure to generate a changelog file (`pnpm --filter=@woocommerce/create-woo-extension run changelog add`) to go with your PR. 

Once a new version has been merged, publish it to [NPM](https://www.npmjs.com/package/@woocommerce/create-woo-extension) (TODO: document the how). 

Things to consider to improve:

- move this tool to TypeScript
- explore writing tests for Commander applications (and add some to this tool)
- dynamically fetch the latest WooCommerce version
