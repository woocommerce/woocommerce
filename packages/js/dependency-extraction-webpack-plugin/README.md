# Dependency Extraction Webpack Plugin

Extends WordPress [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin) to automatically include WooCommerce dependencies in addition to WordPress dependencies.

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

**Note:** If you plan to extend the webpack configuration from `@wordpress/scripts` with `WooCommerceDependencyExtractionWebpackPlugin`, be sure to remove the default instance of the plugin:

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const webpackConfig = {
	...defaultConfig,
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
```

Additional module requests on top of WordPress [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin) are:

| Request | Global | Script handle | Notes |
| --- | --- | --- | --- |
| `@woocommerce/data` | `wc['data']` | `wc-store-data` | Registered in wp-admin only. Not available on the storefront. |
| `@woocommerce/csv-export` | `wc['csvExport']` | `wc-csv` | Registered in wp-admin only. Not available on the storefront. |
| `@woocommerce/blocks-registry` | `wc['wcBlocksRegistry']` | `wc-blocks-registry` | |
| `@woocommerce/block-data` | `wc['wcBlocksData']` | `wc-blocks-data-store` | This dependency does not have an associated npm package |
| `@woocommerce/settings` | `wc['wcSettings']` | `wc-settings` | This is an alias for a WooCommerce core script, not the npm package of the same name. See below. |
| `@woocommerce/*` | `wc['*']` | `wc-*` | |

### `@woocommerce/settings`

The `@woocommerce/settings` request is not the [`@woocommerce/settings` npm package](https://www.npmjs.com/package/@woocommerce/settings). That package is deprecated and should not be installed. The plugin maps the request to the `wc.wcSettings` global and adds `wc-settings` to your script dependencies; WooCommerce core loads that script, and the data it exposes, whenever a script depends on the `wc-settings` handle. The source lives in [`plugins/woocommerce/client/blocks/packages/public-api/settings`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/client/blocks/packages/public-api/settings).

Use `getSetting` to read data that WooCommerce, or your own PHP code, registered on the server. See [Data flow: server to client](https://github.com/woocommerce/woocommerce/blob/trunk/docs/block-development/reference/overview-of-data-flow.md#server-php-to-client-javascript) for how to register that data.

```js
import { getSetting } from '@woocommerce/settings';

const value = getSetting( 'my-plugin/value', 'fallback' );
```

#### Using `@woocommerce/settings` with Jest

Jest cannot resolve the request because there is no package to install. Map it to a local mock and define the `wcSettings` global in a setup file:

```js
// jest.config.js
module.exports = {
	moduleNameMapper: {
		'@woocommerce/settings': '<rootDir>/tests/mocks/woocommerce-settings.js',
	},
	setupFiles: [ '<rootDir>/tests/setup-globals.js' ],
};
```

```js
// tests/mocks/woocommerce-settings.js
module.exports = {
	getSetting: ( name, fallback = false ) =>
		name in global.wcSettings ? global.wcSettings[ name ] : fallback,
};
```

```js
// tests/setup-globals.js
global.wcSettings = {
	adminUrl: 'https://example.com/wp-admin/',
};
```

Mock only the helpers and settings your code uses. The mock and global that the WooCommerce monorepo uses for its own tests are in [`packages/js/internal-js-tests/src/mocks/woocommerce-settings.js`](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/internal-js-tests/src/mocks/woocommerce-settings.js) and [`packages/js/internal-js-tests/src/setup-globals.js`](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/internal-js-tests/src/setup-globals.js).

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
