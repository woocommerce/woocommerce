# @woocommerce/extend-cart-checkout-block

This is a template to be used with `@wordpress/create-block` to create a WooCommerce Blocks extension starting point.

## Installation

From your `plugins` directory run:

```sh
npx @wordpress/create-block -t @woocommerce/extend-cart-checkout-block your_extension_name
```

When this has completed, go to your WordPress plugins page and activate the plugin.

Add some items to your cart and visit the Checkout block, notice there is additional data on the block that this template has added.

### Installing `wp-prettier` (optional)

WooCommerce Blocks uses `wp-prettier` to format the JS files. If you want to use `wp-prettier`, you will need to run the following command:

```sh
nvm use && npm i --D "prettier@npm:wp-prettier@latest"
```

### Installing `wp-env` (optional)

`wp-env` lets you easily set up a local WordPress environment for building and testing your extension. If you want to use `wp-env`, you will need to run the following command:

```sh
nvm use && npm i -D @wordpress/env && npm set-script wp-env "wp-env"
```
