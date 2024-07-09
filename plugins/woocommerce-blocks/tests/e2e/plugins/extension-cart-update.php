<?php
/**
 * Plugin Name: WooCommerce Blocks Test extensionCartUpdate
 * Description: Adds an extensionCartUpdate endpoint.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-extension-cart-update
 */

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

add_action(
	'woocommerce_init',
	function () {
		$extend = StoreApi::container()->get( ExtendSchema::class );
		if (
			is_callable(
				array(
					$extend,
					'register_update_callback',
				)
			)
		) {
			$extend->register_update_callback(
				array(
					'namespace' => 'woocommerce-blocks-test-extension-cart-update',
					'callback'  => function ( $data ) {},
				)
			);
		}
	}
);
