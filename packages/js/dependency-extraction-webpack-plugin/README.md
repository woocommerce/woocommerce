# Dependency Extraction Webpack Plugin

Extends Wordpress [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin) to automatically include WooCommerce dependencies in addition to WordPress dependencies.

## Installation

Install the module

```bash
pnpm install @woocommerce/dependency-extraction-webpack-plugin --save-dev
```

## Usage

Use this as you would [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin). The API is exactly the same, except that WooCommerce packages are also handled automatically.

```js
// webpack.config.js
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

module.exports = {
 // …snip
 plugins: [ new WooCommerceDependencyExtractionWebpackPlugin() ],
};
```

Additional module requests on top of Wordpress [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin) are:

| Request                        | Global                   | Script handle          | Notes                                                   |
| ------------------------------ | ------------------------ | ---------------------- | --------------------------------------------------------|
| `@woocommerce/data`            | `wc['data']`             | `wc-store-data`        | |
| `@woocommerce/csv-export`      | `wc['csvExport']`        | `wc-csv`               | |
| `@woocommerce/blocks-registry` | `wc['wcBlocksRegistry']` | `wc-blocks-registry`   | |
| `@woocommerce/block-data`      | `wc['wcBlocksData']`     | `wc-blocks-data-store` | This dependency does not have an associated npm package |
| `@woocommerce/settings`        | `wc['wcSettings']`       | `wc-settings`          | |
| `@woocommerce/*`               | `wc['*']`                | `wc-*`                 | |

### Options

An object can be passed to the constructor to customize the behavior, for example:

```js
module.exports = {
 plugins: [
  new WooCommerceDependencyExtractionWebpackPlugin( {
   bundledPackages: [ '@woocommerce/components' ],
  } ),
 ],
};
```

#### `bundledPackages`

- Type: array
- Default: []

A list of potential WooCommerce excluded packages, this will include the excluded package within the bundle (example above).

For more supported options see the original [dependency extraction plugin](https://github.com/WordPress/gutenberg/blob/trunk/packages/dependency-extraction-webpack-plugin/README.md#options).
