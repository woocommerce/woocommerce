<?php
/**
 * Admin functions for the shop_coupon post type.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Coupons
 * @version     1.6.4
 */

/**
 * Define Columns for the Coupons admin page.
 *
 * @access public
 * @param mixed $columns
 * @return array
 */
function woocommerce_edit_coupon_columns($columns){

	$columns = array();

	$columns["cb"] 			= "<input type=\"checkbox\" />";
	$columns["title"] 		= __("Code", 'woocommerce');
	$columns["type"] 		= __("Coupon type", 'woocommerce');
	$columns["amount"] 		= __("Coupon amount", 'woocommerce');
	$columns["products"]	= __("Product IDs", 'woocommerce');
	$columns["usage_limit"] = __("Usage limit", 'woocommerce');
	$columns["usage_count"] = __("Usage count", 'woocommerce');
	$columns["expiry_date"] = __("Expiry date", 'woocommerce');

	return $columns;
}

add_filter('manage_edit-shop_coupon_columns', 'woocommerce_edit_coupon_columns');


/**
 * Values for Columns on the Coupons admin page.
 *
 * @access public
 * @param mixed $column
 * @return void
 */
function woocommerce_custom_coupon_columns($column) {
	global $post, $woocommerce;

	$type 			= get_post_meta($post->ID, 'discount_type', true);
	$amount 		= get_post_meta($post->ID, 'coupon_amount', true);
	$individual_use = get_post_meta($post->ID, 'individual_use', true);
	$product_ids 	= (get_post_meta($post->ID, 'product_ids', true)) ? explode(',', get_post_meta($post->ID, 'product_ids', true)) : array();
	$usage_limit 	= get_post_meta($post->ID, 'usage_limit', true);
	$usage_count 	= (int) get_post_meta($post->ID, 'usage_count', true);
	$expiry_date 	= get_post_meta($post->ID, 'expiry_date', true);

	switch ($column) {
		case "type" :
			echo $woocommerce->get_coupon_discount_type($type);
		break;
		case "amount" :
			echo $amount;
		break;
		case "products" :
			if (sizeof($product_ids)>0) echo implode(', ', $product_ids); else echo '&ndash;';
		break;
		case "usage_limit" :
			if ($usage_limit) echo $usage_limit; else echo '&ndash;';
		break;
		case "usage_count" :
			echo $usage_count;
		break;
		case "expiry_date" :
			if ($expiry_date) echo date_i18n( 'F j, Y', strtotime( $expiry_date ) ); else echo '&ndash;';
		break;
	}
}

add_action( 'manage_shop_coupon_posts_custom_column', 'woocommerce_custom_coupon_columns', 2 );