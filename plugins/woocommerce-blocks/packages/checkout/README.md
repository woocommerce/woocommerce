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

Alternatively, you can map this to external to a custom alias using the [WordPress Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin):

```js
// webpack.config.js
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const dependencyMap = {
	'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
};

const handleMap = {
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
};

module.exports = {
	// …snip
	plugins: [
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			requestToExternal( request ) {
				if ( dependencyMap[ request ] ) {
					return dependencyMap[ request ];
				}
			},
			requestToHandle( request ) {
				if ( handleMap[ request ] ) {
					return handleMap[ request ];
				}
			},
		} ),
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
