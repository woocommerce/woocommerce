<?php
/**
 * Plugin Name: Enable Experimental Features
 * Description: Utility designed for E2E testing purposes. It activates experimental features in WooCommerce.
 * @package Automattic\WooCommerce\E2EPlaywright
 */

function enable_experimental_features( $features ) {
	$features['product-variation-management'] = true;
	return $features;
}

add_filter( 'woocommerce_admin_get_feature_config', 'enable_experimental_features' );
