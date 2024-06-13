<?php
/**
 * Plugin Name: WooCommerce Blocks Test Enable Experimental Features
 * Description: Enable experimental features for WooCommerce Blocks that are behind feature flags.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-enable-experimental-features
 */

/**
 * Enable experimental features
 *
 * @param array $features Array of feature slugs.
 */
function enable_experimental_features( $features ) {
	// add experimental block features
	return array_merge( $features, array( 'experimental-blocks' ) );
}

add_filter( 'woocommerce_admin_features', 'enable_experimental_features', 20, 1 );
