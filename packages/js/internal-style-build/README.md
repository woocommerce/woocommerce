# Style Build Helper

This is a partial [Webpack](https://webpack.js.org/) config for building WooCommece component styles using base styles from Gutenberg. It is used to replace the [`bin/packages/build.js`](https://github.com/woocommerce/woocommerce-admin/blob/6859249/bin/packages/build.js) script.


## Usage

Create a `webpack.config.js` in your package root that defines the `entry` and `output`, making use of the `rules` and `plugins` from `@woocommerce/internal-style-build`.

***Note:*** The `entry` should be named `'build-style'` so the CSS will get picked up by the main `client/` application's `CopyWebpackPlugin` config.

```js
// packages/<package-name>/webpack.config.js

import { webpackConfig } from '@woocommerce/internal-style-build';

module.exports = {
	mode: process.env.NODE_ENV || 'development',
	entry: {
		'build-style': __dirname + '/src/style.scss',
	},
	output: {
		path: __dirname,
	},
	module: {
		rules: webpackConfig.rules,
	},
	plugins: webpackConfig.plugins,
};
```
