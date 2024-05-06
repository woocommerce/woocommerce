# JavaScript Build System <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Environment variables](#environment-variables)
-   [Babel](#babel)
-   [External scripts](#external-scripts)
-   [Aliases](#aliases)
-   [Styling](#styling)
-   [Relevant files](#relevant-files)

WooCommerce Blocks uses Webpack to build the files that will be consumed by browsers. There are several different Webpack configs in order to build files for different contexts of the plugin. They can all be found in [`webpack.config.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/6da64165025e7a2afc1782e4b278d72536e7b754/webpack.config.js#L162-L169), but this is a quick summary:

-   `CoreConfig`: config for shared libraries like settings, blocks data or some HOCs and context.
-   `MainConfig`: config that builds the JS files used by blocks in the editor and is responsible for registering the blocks in Gutenberg.
-   `FrontendConfig`: config that builds the JS files used by blocks in the store frontend.
-   `PaymentsConfig`: config that builds the JS files used by payment methods in the Cart and Checkout blocks.
-   `ExtensionsConfig`: config that builds extension integrations.
-   `StylingConfig`: config that builds CSS files. You can read more about it in the page [CSS build system](css-build-system.md).

Details on each config can be found in [`webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-configs.js). Entry points are declared in [`webpack-entries.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-entries.js).

## Environment variables

Different builds are generated depending on the variable `NODE_ENV`. It can have two values: `production` or `development`. Production builds include tree-shaking, minification and other performance enhancements, while development builds are focused on development and include source maps. You can read more about [environment variables](https://webpack.js.org/guides/environment-variables/) in Webpack docs.

## Babel

Most of our code is transpiled by Babel. This allows us to use the latest JavaScript technologies without affecting browser support.

Some of the Babel plugins we use can be found in [`webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-configs.js).

## External scripts

Several scripts are loaded as externals. That means that they can be imported in WooCommerce Blocks as any other package, but instead of being bundled in our built files, they are loaded from an external file provided by WordPress (or Gutenberg, if enabled).

[`@wordpress/dependency-extraction-webpack-plugin`](https://developer.wordpress.org/block-editor/packages/packages-dependency-extraction-webpack-plugin/) is used to automatize dependency extraction for common WP scripts.

In practice, that means the dependency version is:

-   deterministic when running WooCommerce Blocks in isolation (e.g. unit tests). In this case, the dependency will have a version as stated in `package.json`,
-   non-deterministic when run in the WordPress ecosystem. Depending on the WordPress or Gutenberg version the user has installed, the version of external dependencies may also vary.

[`@wordpress/dependency-extraction-webpack-plugin`](https://developer.wordpress.org/block-editor/packages/packages-dependency-extraction-webpack-plugin/) script is applied to each of the build processes: Core, Main, Frontend, Payments, Extensions.

### `wordpress-components` alias

`@wordpress/components` is one of the dependencies that gets externalized by the plugin. You can see the external `components.min.js` file being loaded on the Editor pages containing blocks that depend on the `@wordpress/components`, while the code is not bundled within WooCommerce Blocks files.

For optimization purposes, there's a `wordpress-components` alias created, which is used in the frontend code. Thanks to that, the dependency omits the Dependency Extraction Plugin and behaves like any other dependency: it's tree-shaken and only the necessary code is bundled with the block. That allows us to avoid loading the whole `@wordpress/components` on the frontend pages.

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

## Relevant files

Webpack config is split between several files:

-   [`webpack.config.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/webpack.config.js): Top level webpack config.
-   [`bin/webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-configs.js): Code for generating each build config. This most closely resembles a classic webpack config - if you're looking for something, start here.
-   [`bin/webpack-entries.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-entries.js): Code for generating [webpack `entry` definitions](https://webpack.js.org/concepts/entry-points/) and mapping source files to entry points. If you're adding a new block or module to the build, start here.
-   [`bin/webpack-helpers.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-helpers.js): Includes utils to load external code at run time, e.g. some dependencies from Woo and WordPress core.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/javascript-build-system.md)

<!-- /FEEDBACK -->

