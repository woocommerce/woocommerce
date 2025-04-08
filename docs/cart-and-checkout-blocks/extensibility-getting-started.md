---
post_title: Cart and Checkout - Extensibility getting started guide
menu_title: Extensibility - Getting started
tags: how-to
---
<!-- markdownlint-disable MD041 -->

This document is a high-level overview of the moving parts required to extend the Cart and Checkout blocks.

To get started, it is recommended to first read the [Block Development Environment](https://developer.wordpress.org/block-editor/getting-started/devenv/) documentation from WordPress and follow [Tutorial: Build your first block
](https://developer.wordpress.org/block-editor/getting-started/tutorial/).

## Example block template package

There is an example block template in the WooCommerce repository. Having this template set up while reading this document may help to understand some of the concepts discussed. See the [`@woocommerce/extend-cart-checkout-block` package documentation](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/extend-cart-checkout-block/README.md) for how to install and run the example block.

(Note: the code in the repository linked above will not be very useful alone; the code there is templating code. It will be transformed into regular JS and PHP after following the README instructions.)

## Front-end extensibility

To extend the front-end of the blocks, extensions must use JavaScript. The JavaScript files must be enqueued and loaded on the page before they will have any effect.

### Build system

Some extensions may be very simple and include only a single JavaScript file, other extensions may be complex and the code may be split into multiple files. Either way, it is recommended that the files are bundled together and minified into a single output file. If your extension has several distinct parts that only load on specific pages, bundle splitting is recommended, though that is out of scope for this document.

To set up the build system, the recommended approach is to align with WordPress and use a JavaScript package called [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/). This package contains a script called `build`. By default, this will build your scripts into a single output file that can be enqueued using `wp_enqueue_script`.

The base configuration of the `build` script in  `@wordpress/scripts` can be overridden by creating a `webpack.config.js` file in the root of your plugin. The example block shows how the base config can be extended.

#### `WooCommerceDependencyExtractionWebpackPlugin`

See [`WordPress Dependency Extraction Webpack Plugin`](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin) and 
[`WooCommerce Dependency Extraction Webpack Plugin`](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/dependency-extraction-webpack-plugin#dependency-extraction-webpack-plugin).

This Webpack plugin is used to:

- Externalize dependencies that are available as shared scripts or modules on WordPress sites.
    - This means when you import something from `@woocommerce/blocks-checkout` it resolves that path to `window.wc.wcBlocksCheckout` without you needing to change your code. It makes your code easier to read and allows these packages to be loaded onto the page once.
- Add an asset file for each entry point that declares an object with the list of WordPress script or module dependencies for the entry point. The asset file also contains the current version calculated for the current source code.

The PHP "asset file" that this plugin outputs contains information your script needs to register itself, such as dependencies and paths.

If you have written code that is built by Webpack and using the WooCommerce Dependency Extraction Webpack Plugin, there will be an asset file output for each entry point. This asset file is a PHP file that contains information about your script, specifically dependencies and version, here's an example:

```php
<?php
return array(
  'dependencies' => array(
    'react',
    'wc-settings',
    'wp-block-editor',
    'wp-blocks',
    'wp-components',
    'wp-element',
    'wp-i18n',
    'wp-primitives'
  ),
  'version' => '455da4f55e1ac73b6d34'
);
```

When enqueueing your script, using this asset file will ensure the dependencies are loaded correctly and that the client gets the most up-to-date version of your script (the version is used to ensure your script is fetched fresh, rather than from the cache).

```php
<?php
$script_path = '/build/index.js';
$script_url  = plugins_url( $script_path, __FILE__ );

$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
$script_asset      = file_exists( $script_asset_path )
  ? require $script_asset_path
  : [
    'dependencies' => [],
    'version'      => $this->get_file_version( $script_path ),
  ];

wp_register_script(
  'example-blocks-integration-handle',
  $script_url,
  $script_asset['dependencies'],
  $script_asset['version'],
  true
);
```

Please see the [Cart and Checkout – Handling scripts, styles, and data](https://developer.woocommerce.com/docs/cart-and-checkout-handling-scripts-styles-and-data/) document for more information about how to correctly register scripts using the `IntegrationInterface`.

### Creating a block

In the example block, there is a "checkout-newsletter-subscription-block" directory which contains the files needed to register an inner block in the Checkout. The example block template is only set up to import and build a single block, but the Webpack config can be modified to build multiple blocks. Doing this is not supported as part of this document, refer to the [Webpack documentation](https://webpack.js.org/concepts/) instead.

The principles covered in [Tutorial: Build your first block
](https://developer.wordpress.org/block-editor/getting-started/tutorial/) apply here too.

### Modifying existing values on the front-end

You may not need to create a block to get your extension working the way you want, for example, if your extension only modifies existing content through filters.

In this case, you could remove the block folder from the example block, modify the Webpack config file so it no longer reads from that directory, and include the code you need in the entry JavaScript file.

More information about how to use filters can be found in the [Filter Registry](../../plugins/woocommerce/client/blocks/packages/checkout/filter-registry/README.md) and [Available Filters](https://developer.woocommerce.com/docs/category/cart-and-checkout-blocks/available-filters/) documents.

### Importing WooCommerce components into your extension

Components can be imported from `@woocommerce/blocks-components` (externalized to `window.wc.blocksComponents` by `@woocommerce/dependency-extraction-webpack-plugin`). The list of available components can be seen in [the WooCommerce Storybook](https://woocommerce.github.io/woocommerce/?path=/docs/woocommerce-blocks_external-components-button--docs), listed under "WooCommerce Blocks -> External components".

An example of importing the `Button` component is:

```js
import { Button } from '@woocommerce/blocks-components';

const MyComponent = () => {
  return <div class="my-wrapper">
    <Button type="button" />
  </div>;
}
```

### Importing WooCommerce (React) hooks

Currently, none of our hooks are designed to be used externally, so trying to import hooks such as `useStoreCart` is not supported. Instead, getting the data from the [`wc/store/...`](../../plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/data-store/) data stores is preferred.

## Back-end extensibility

### Modifying information during the Checkout process

Modifying the server-side part of the Cart and Checkout blocks is possible using PHP. Some actions and filters from the shortcode cart/checkout experience will work too, but not all of them. We have a working document ([Hook alternatives document](../../plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/hook-alternatives.md)) that outlines which hooks are supported as well as alternatives.

### Extending Store API

If you need to change how the Store API works, or extend the data in the responses, see [Extending the Store API](../../plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/rest-api).
