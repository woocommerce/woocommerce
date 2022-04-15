# Create Extension

Scaffold a modern JavaScript WordPress plugin with WooCommerce tooling.

## Includes

-   [wp-scripts](https://github.com/WordPress/gutenberg/tree/master/packages/scripts)
-   [WooCommerce Dependency Extraction Webpack Plugin](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/dependency-extraction-webpack-plugin)
-   [WooCommerce ESLint Plugin with WordPress Prettier](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/eslint-plugin)

### Usage

At the root of a [WooCommerce Admin](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce-admin) installation, run the create extension command.

```
pnpm run create-wc-extension
```

The script will create a sibling directory by a name of your choosing. Once you change directories into the new folder, install dependencies and start a development build.

```
pnpm install
pnpm start
```
