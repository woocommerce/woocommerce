# Checkout <!-- omit in toc -->

Components and utilities making it possible to integrate with the WooCommerce Cart and Checkout Blocks.

## Table of Contents <!-- omit in toc -->

- [Installation](#installation)
- [Usage](#usage)
  - [Aliased imports](#aliased-imports)
- [Folder Structure Overview](#folder-structure-overview)

## Installation

This package is available as an external when the [WooCommerce Blocks Feature Plugin](https://wordpress.org/plugins/woo-gutenberg-products-block/) is installed and activated.

## Usage

Package components can be accessed via the `wc` global:

```js
const { ... } = wc.blocksCheckout;
```

### Aliased imports

Alternatively, you can map this to externals (or aliases) using the [WooCommerce Dependency Extraction Webpack Plugin](https://github.com/woocommerce/woocommerce-admin/tree/main/packages/dependency-extraction-webpack-plugin). Just add the above Webpack plugin to your package.json:

```bash
npm install @woocommerce/dependency-extraction-webpack-plugin --save-dev
```

Now, you can include this plugin in your Webpack configuration:

```js
// webpack.config.js
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

module.exports = {
	// …snip
	plugins: [
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
```

## Folder Structure Overview

This package contains the following directories. Navigate to a directory for more in depth documentation about each module.

| Directory                                            | Contents                                                                                                                                                                                                                                                                                                               |
| :--------------------------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <nobr>[`blocks-registry/`](./blocks-registry)</nobr> | Used to **register new Inner Blocks** that can be inserted either automatically or optionally within the Checkout Block. _**Example use case:** Creating a newsletter subscription block on the Checkout._                                                                                                             |
| [`components/`](./components)                        | Components available for use by Checkout Blocks.                                                                                                                                                                                                                                                                       |
| <nobr>[`filter-registry/`](./filter-registry)</nobr> | Used to **manipulate content** where filters are available. _**Example use case:** Changing how prices are displayed._ ([Documentation](./filter-registry))                                                                                                                                                            |
| [`slot/`](./slot)                                    | Slot and Fill are a pair of components which enable developers to render in a React element tree. In this context, they are used to **insert content within Blocks** where slot fills are available. _**Example use case:** Adding a custom component after the shipping options._ ([Documentation](./slot/README.md)) |
| [`utils/`](./utils)                                  | Miscellaneous utility functions for dealing with checkout functionality.                                                                                                                                                                                                                                               |

<br/><br/><p align="center">
<a href="https://woocommerce.com/">
<img src="https://woocommerce.com/wp-content/themes/woo/images/logo-woocommerce@2x.png" alt="WooCommerce" height="28px" style="filter: grayscale(100%);
	opacity: 0.2;" />
</a><br/><a href="https://woocommerce.com/careers/">We're hiring</a>! Come work with us!</p>
