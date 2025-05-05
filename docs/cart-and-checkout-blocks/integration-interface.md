---
post_title: Cart and Checkout - Handling scripts, styles, and data
menu_title: Script, Styles, and Data Handling
tags: how-to
---

## The problem

You are an extension developer, and to allow users to interact with your extension on the client-side, you have written some CSS and JavaScript that you would like to be included on the page. Your JavaScript also relies on some server-side data, and you'd like this to be available to your scripts.

## The solution

You may use the `IntegrationRegistry` to register an `IntegrationInterface` this will be a class that will handle the enqueuing of scripts, styles, and data. You may have a different `IntegrationInterface` for each block (Mini-Cart, Cart and Checkout), or you may use the same one, it is entirely dependent on your use case.

You should use the hooks: `woocommerce_blocks_mini-cart_block_registration`. `woocommerce_blocks_cart_block_registration` and `woocommerce_blocks_checkout_block_registration`. These hooks pass an instance of [`IntegrationRegistry`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/Integrations/IntegrationRegistry.php) to the callback.

You may then use the `register` method on this object to register your `IntegrationInterface`.

## `IntegrationInterface` methods

To begin, we'll need to create our integration class, our `IntegrationInterface`. This will be a class that implements WooCommerce Blocks' interface named [`IntegrationInterface`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/Integrations/IntegrationInterface.php).

In this section, we will step through the interface's members and discuss what they are used for.

### `get_name()`

This is the `IntegrationInterface`'s way of namespacing your integration. The name chosen here should be unique to your extension. This method should return a string.

### `initialize()`

This is where any setup, or initialization for your integration should be placed. For example, you could register the scripts and styles your extension needs here. This method should not return anything.

### `get_script_handles()`

This is where the handles of any scripts you want to be enqueued on the client-side in the frontend context should be placed. This method should return an array of strings.

### `get_editor_script_handles()`

This is where the handles of any scripts you want to be enqueued on the client-side in the editor context should be placed. This method should return an array of strings.

### `get_script_data()`

This is where you can set values you want to be available to your scripts on the frontend. This method should return an associative array, the keys of which will be used to get the data using the JavaScript function `getSetting`.

The keys and values of this array should all be serializable.

## Usage example

Let's suppose we're the author of an extension: `WooCommerce Example Plugin`. We want to enqueue scripts, styles, and data on the frontend when either the Mini-Cart, Cart or Checkout blocks are being used.

We also want some data from a server-side function to be available to our front-end scripts.

You will notice that in the example below, we reference the `/build/index.asset.php` file. This is created by the [`DependencyExtractionWebpackPlugin`](https://www.npmjs.com/package/@wordpress/dependency-extraction-webpack-plugin) which creates a PHP file mapping the dependencies of your client-side scripts, so that they can be added in the `dependencies` array of `wp_register_script`.

Let's create our `IntegrationInterface`.

```php
<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class WooCommerce_Example_Plugin_Integration implements IntegrationInterface {
	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'woocommerce-example-plugin';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$script_path = '/build/index.js';
		$style_path = '/build/style-index.css';

    /**
     * The assets linked below should be a path to a file, for the sake of brevity
     * we will assume \WooCommerce_Example_Plugin_Assets::$plugin_file is a valid file path
    */
		$script_url = plugins_url( $script_path, \WooCommerce_Example_Plugin_Assets::$plugin_file );
		$style_url = plugins_url( $style_path, \WooCommerce_Example_Plugin_Assets::$plugin_file );

		$script_asset_path = dirname( \WooCommerce_Example_Plugin_Assets::$plugin_file ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_path ),
			);

		wp_enqueue_style(
			'wc-blocks-integration',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);

		wp_register_script(
			'wc-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'wc-blocks-integration',
			'woocommerce-example-plugin',
			dirname( \WooCommerce_Example_Plugin_Assets::$plugin_file ) . '/languages'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-blocks-integration' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-blocks-integration' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
	    $woocommerce_example_plugin_data = some_expensive_serverside_function();
	    return [
	        'expensive_data_calculation' => $woocommerce_example_plugin_data
        ];
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		// As above, let's assume that WooCommerce_Example_Plugin_Assets::VERSION resolves to some versioning number our
		// extension uses.
		return \WooCommerce_Example_Plugin_Assets::VERSION;
	}
}
```

As mentioned, we will need register our `IntegrationInterface` with WooCommerce Blocks, as we want our scripts to be included when either the Mini-Cart, Cart or Checkout is used, we need to register callbacks for three actions.

```php
add_action(
    'woocommerce_blocks_mini-cart_block_registration',
    function( $integration_registry ) {
        $integration_registry->register( new WooCommerce_Example_Plugin_Integration() );
    }
);
add_action(
    'woocommerce_blocks_cart_block_registration',
    function( $integration_registry ) {
        $integration_registry->register( new WooCommerce_Example_Plugin_Integration() );
    }
);
add_action(
    'woocommerce_blocks_checkout_block_registration',
    function( $integration_registry ) {
        $integration_registry->register( new WooCommerce_Example_Plugin_Integration() );
    }
);
```

Now, when we load a page containing either block, we should see the scripts we registered in `initialize` being loaded!

### Getting data added in `get_script_data`

We associated some data with the extension in the `get_script_data` method of our interface, we need to know how to get this!

In the `@woocommerce/settings` package there is a method you can import called `getSetting`. This method accepts a string. The name of the setting containing the data added in `get_script_data` is the name of your integration (i.e. the value returned by `get_name`) suffixed with `_data`. In our example it would be: `woocommerce-example-plugin_data`.

The value returned here is a plain old JavaScript object, keyed by the keys of the array returned by `get_script_data`, the values will serialized.
