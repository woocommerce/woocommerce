<?php
/**
 * Plugin Name: WooCommerce Blocks Test Product Collection Compatibility Layer
 * Description: Adds custom content to the Shop page with Product Collection included
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-product-collection-compatibility-layer
 */

add_action(
	'woocommerce_before_main_content',
	function () {
		echo '<p data-testid="woocommerce_before_main_content">
			Hook: woocommerce_before_main_content
		</p>';
	}
);

add_action(
	'woocommerce_after_main_content',
	function () {
		echo '<p data-testid="woocommerce_after_main_content">
			Hook: woocommerce_after_main_content
		</p>';
	}
);
add_action(
	'woocommerce_before_shop_loop_item_title',
	function () {
		echo '<p data-testid="woocommerce_before_shop_loop_item_title">
			Hook: woocommerce_before_shop_loop_item_title
		</p>';
	}
);

add_action(
	'woocommerce_shop_loop_item_title',
	function () {
		echo '<p data-testid="woocommerce_shop_loop_item_title">
			Hook: woocommerce_shop_loop_item_title
		</p>';
	}
);

add_action(
	'woocommerce_after_shop_loop_item_title',
	function () {
		echo '<p data-testid="woocommerce_after_shop_loop_item_title">
			Hook: woocommerce_after_shop_loop_item_title
		</p>';
	}
);

add_action(
	'woocommerce_before_shop_loop_item',
	function () {
		echo '<p data-testid="woocommerce_before_shop_loop_item">
			Hook: woocommerce_before_shop_loop_item
		</p>';
	}
);

add_action(
	'woocommerce_after_shop_loop_item',
	function () {
		echo '<p data-testid="woocommerce_after_shop_loop_item">
			Hook: woocommerce_after_shop_loop_item
		</p>';
	}
);

add_action(
	'woocommerce_before_shop_loop',
	function () {
		echo '<p data-testid="woocommerce_before_shop_loop">
			Hook: woocommerce_before_shop_loop
		</p>';
	}
);

add_action(
	'woocommerce_after_shop_loop',
	function () {
		echo '<p data-testid="woocommerce_after_shop_loop">
			Hook: woocommerce_after_shop_loop
		</p>';
	}
);

add_action(
	'woocommerce_no_products_found',
	function () {
		echo '<p data-testid="woocommerce_no_products_found">
			Hook: woocommerce_no_products_found
		</p>';
	}
);
