# JS build system

WooCommerce Blocks uses Webpack to build the files that will be consumed by browsers. There are several different Webpack configs in order to build files for different contexts of the plugin. They can all be found in [`webpack.config.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/6da64165025e7a2afc1782e4b278d72536e7b754/webpack.config.js#L162-L169), but this is a quick summary:

-   `CoreConfig`: config for shared libraries like settings, blocks data or some HOCs and context.
-   `MainConfig`: config that builds the JS files used by blocks in the editor and is responsible for registering the blocks in Gutenberg.
-   `FrontendConfig`: config that builds the JS files used by blocks in the store frontend.
-   `PaymentsConfig`: config that builds the JS files used by payment methods in the Cart and Checkout blocks.
-   `StylingConfig`: config that builds CSS files. You can read more about it in the page [CSS build system](css-build-system.md).

Details on each config can be found in [`webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-configs.js). Entry points are declared in [`webpack-entries.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-entries.js).

## Environment variables

Different builds are generated depending on the variable `NODE_ENV`. It can have two values: `production` or `development`. Production builds include tree-shaking, minification and other performance enhancements, while development builds are focused on development and include source maps. You can read more about [environment variables](https://webpack.js.org/guides/environment-variables/) in Webpack docs.

## Babel

Most of our code is transpiled by Babel. This allows us to use the latest JavaScript technologies without affecting browser support.

Some of the Babel plugins we use can be found in [`webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-configs.js).

## External scripts

Several scripts are loaded as externals. That means that they are imported in WC Blocks as any other package, but instead of being bundled in our built files, they are loaded from an external file. We use [`@wordpress/dependency-extraction-webpack-plugin`](https://developer.wordpress.org/block-editor/packages/packages-dependency-extraction-webpack-plugin/) to automatize dependency extraction for common WP scripts.

## Aliases

There are several aliases for internal imports which make importing files across the file tree easier. Instead of having to write a long relative path, they allow writing an alias:

```diff
-import { useStoreCartCoupons } from '../../../../base/hooks';
+import { useStoreCartCoupons } from '@woocommerce/base-context/hooks';
```

Aliases also ease refactors because imports no longer depend on the exact location of the file.

All available aliases can be found in [`webpack-helpers.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/6da64165025e7a2afc1782e4b278d72536e7b754/bin/webpack-helpers.js#L36-L91).

## Styling

CSS builds follow a separate path from JS builds, more details can be found in the [CSS build system](css-build-system.md).

## Legacy builds

In the past, when building WC Blocks, special builds targeting old versions of WordPress were generated. Those builds were named 'legacy builds' and might have a smaller set of blocks available. For example, All Products and Filter blocks were not available on WP 5.2.

There were legacy builds of the `MainConfig`, `FrontendConfig` and `StylingConfig`. Currently, those builds are no longer generated since WC Blocks doesn't support WP 5.2 anymore, but the built system is still in place in case legacy builds are needed in the future.

## Relevant files

Webpack config is split between several files:

- [`webpack.config.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/webpack.config.js): Top level webpack config. Includes support for legacy and main build.
- [`bin/webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-configs.js): Code for generating each build config. This most closely resembles a classic webpack config - if you're looking for something, start here.
- [`bin/webpack-entries.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-entries.js): Code for generating [webpack `entry` definitions](https://webpack.js.org/concepts/entry-points/) and mapping source files to entry points. If you're adding a new block or module to the build, start here.
- [`bin/webpack-helpers.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-helpers.js): Includes utils to load external code at run time, e.g. some dependencies from Woo and WordPress core.

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/js-build-system.md)
<!-- /FEEDBACK -->

