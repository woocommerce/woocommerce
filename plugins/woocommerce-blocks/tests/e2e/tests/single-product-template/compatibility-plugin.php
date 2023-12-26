<?php
/**
 * Plugin Name: Compatibility Layer Plugin
 * Description: Adds custom content to the Shop page with Product Collection included
 * @package     WordPress
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
	'woocommerce_sidebar',
	function () {
		echo '<p data-testid="woocommerce_sidebar">
			Hook: woocommerce_sidebar
		</p>';
	}
);

add_action(
	'woocommerce_before_single_product',
	function () {
		echo '<p data-testid="woocommerce_before_single_product">
			Hook: woocommerce_before_single_product
		</p>';
	}
);

add_action(
	'woocommerce_before_single_product_summary',
	function () {
		echo '<p data-testid="woocommerce_before_single_product_summary">
			Hook: woocommerce_before_single_product_summary
		</p>';
	}
);

add_action(
	'woocommerce_single_product_summary',
	function () {
		echo '<p data-testid="woocommerce_single_product_summary">
			Hook: woocommerce_single_product_summary
		</p>';
	}
);

add_action(
	'woocommerce_before_add_to_cart_button',
	function () {
		echo '<p data-testid="woocommerce_before_add_to_cart_button">
			Hook: woocommerce_before_add_to_cart_button
		</p>';
	}
);


add_action(
	'woocommerce_product_meta_start',
	function () {
		echo '<p data-testid="woocommerce_product_meta_start">
			Hook: woocommerce_product_meta_start
		</p>';
	}
);

add_action(
	'woocommerce_product_meta_end',
	function () {
		echo '<p data-testid="woocommerce_product_meta_end">
			Hook: woocommerce_product_meta_end
		</p>';
	}
);

add_action(
	'woocommerce_share',
	function () {
		echo '<p data-testid="woocommerce_share">
			Hook: woocommerce_share
		</p>';
	}
);

add_action(
	'woocommerce_after_single_product_summary',
	function () {
		echo '<p data-testid="woocommerce_after_single_product_summary">
			Hook: woocommerce_after_single_product_summary
		</p>';
	}
);

add_action(
	'woocommerce_after_single_product',
	function () {
		echo '<p data-testid="woocommerce_after_single_product">
			Hook: woocommerce_after_single_product
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
