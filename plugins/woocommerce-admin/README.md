# WooCommerce Admin

This is a javascript-driven, React-based admin interface for WooCommerce.

## Development

### Prerequisites

Run `pnpm -- turbo run build --filter=@woocommerce/admin-library` to build dependencies first.

### Build, Watch

You can build the files using one of these commands:

- `pnpm -- turbo run build --filter=@woocommerce/admin-library`: Build a production version
- `pnpm dev --filter=@woocommerce/admin-library` : Build a development version
- `pnpm start --filter=@woocommerce/admin-library` : Build a development version, watch files for changes

### Helper scripts

There are a number of helper scripts exposed via our package.json (below list is not exhaustive, you can view the package.json file directly to see all):

- `pnpm -- turbo run lint --filter=@woocommerce/admin-library`: Run eslint over the JavaScript files
- `pnpm -- turbo run test --filter=@woocommerce/admin-library`: Run the JS test suite
- `pnpm ts:check --filter=@woocommerce/admin-library`: Run a type check over the TypeScript files
- `pnpm test:watch --filter=@woocommerce/admin-library`: Watch the JS test suite

### Add Changelog

Run `pnpm changelog add --filter=woocommerce` to create a changelog file.

## End-to-end tests

Please refer to the [WooCommerce End to End Tests](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/README.md)
## Common Issues

If you're encountering any issue setting things up, chances are we have been there too. Please have a look at our [wiki](https://github.com/woocommerce/woocommerce/wiki/Common-Issues) for a list of common problems.
