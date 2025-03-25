# @woocommerce/extend-cart-checkout-block

This is a template to be used with `@wordpress/create-block` to create a WooCommerce Blocks extension starting point.

## Installation

You may need to install a node version manager, for example [`nvm`](https://github.com/nvm-sh/nvm) or [`fnm`](https://github.com/Schniz/fnm)

Before getting started, switch to Node v20.

e.g. `nvm install 20 && nvm use 20` or `fnm install 20 && fnm use 20`

From your `plugins` directory run:

```sh
npx @wordpress/create-block -t @woocommerce/extend-cart-checkout-block your_extension_name
```

When this has completed, go to your WordPress plugins page and activate the plugin.

Add some items to your cart and visit the Checkout block, notice there is additional data on the block that this template has added.

### Linting

You can lint the project according to the [WordPress coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/) by running `npm run lint:js`. The configuration is ultimately read from the [WooCommerce recommended eslint config](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/eslint-plugin/configs/recommended.js). To modify the rules edit the `.estintrc.js` file.

### Installing `wp-env` (optional)

`wp-env` lets you easily set up a local WordPress environment for building and testing your extension. If you want to use `wp-env`, you will need to run the following command:

```sh
nvm use && npm i -D @wordpress/env && npm set-script wp-env "wp-env"
```
