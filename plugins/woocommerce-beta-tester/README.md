# WooCommerce Beta Tester

A plugin that makes it easy to test out pre-releases such as betas release candidates and even final releases. It also comes with WooCommerce Admin Test Helper that helps test WooCommerce Admin functionalities.

## Installation

You can either install the latest version from [wp.org](https://wordpress.org/plugins/woocommerce-beta-tester/) or symlink this directory by running `ln -s ./ :path-to-your-wp-plugin-directory/woocommerce-beta-tester`

## Development

To get started, run the following commands:

```text
pnpm --filter=@woocommerce/plugin-woocommerce-beta-tester install
pnpm --filter=@woocommerce/plugin-woocommerce-beta-tester start
```

See [wp-scripts](https://github.com/WordPress/gutenberg/tree/trunk/packages/scripts) for more usage information.

## Usage

You can get to the settings and features from your top admin bar under the name WC Beta Tester.

For more information about WooCommerce Admin Test Helper usage, click [here](./EXTENDING-WC-ADMIN-HELPER.md).

Run `./bin/build-zip.sh` to make a zip file.
