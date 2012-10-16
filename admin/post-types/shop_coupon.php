<?php
/**
 * Admin functions for the shop_coupon post type.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Coupons
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
	$columns["title"] 		= __( 'Code', 'woocommerce' );
	$columns["type"] 		= __( 'Coupon type', 'woocommerce' );
	$columns["amount"] 		= __( 'Coupon amount', 'woocommerce' );
	$columns["description"] = __( 'Description', 'woocommerce' );
	$columns["products"]	= __( 'Product IDs', 'woocommerce' );
	$columns["usage"] 		= __( 'Usage / Limit', 'woocommerce' );
	$columns["expiry_date"] = __( 'Expiry date', 'woocommerce' );

	return $columns;
}

add_filter( 'manage_edit-shop_coupon_columns', 'woocommerce_edit_coupon_columns' );


/**
 * Values for Columns on the Coupons admin page.
 *
 * @access public
 * @param mixed $column
 * @return void
 */
function woocommerce_custom_coupon_columns( $column ) {
	global $post, $woocommerce;

	switch ( $column ) {
		case "type" :
			echo esc_html( $woocommerce->get_coupon_discount_type( get_post_meta( $post->ID, 'discount_type', true ) ) );
		break;
		case "amount" :
			echo esc_html( get_post_meta( $post->ID, 'coupon_amount', true ) );
		break;
		case "products" :
			$product_ids = get_post_meta( $post->ID, 'product_ids', true );
			$product_ids = $product_ids ? array_map( 'absint', explode( ',', $product_ids ) ) : array();
			if ( sizeof( $product_ids ) > 0 ) 
				echo esc_html( implode( ', ', $product_ids ) ); 
			else 
				echo '&ndash;';
		break;
		case "usage_limit" :
			$usage_limit = get_post_meta( $post->ID, 'usage_limit', true );
			
			if ( $usage_limit ) 
				echo esc_html( $usage_limit ); 
			else 
				echo '&ndash;';
		break;
		case "usage" :
			$usage_count = absint( get_post_meta( $post->ID, 'usage_count', true ) );
			$usage_limit = esc_html( get_post_meta($post->ID, 'usage_limit', true) );
			
			if ( $usage_limit ) 
				printf( __( '%s / %s', 'woocommerce' ), $usage_count, $usage_limit );
			else
				printf( __( '%s / &infin;', 'woocommerce' ), $usage_count );
		break;
		case "expiry_date" :
			$expiry_date = get_post_meta($post->ID, 'expiry_date', true);
			
			if ( $expiry_date ) 
				echo esc_html( date_i18n( 'F j, Y', strtotime( $expiry_date ) ) ); 
			else 
				echo '&ndash;';
		break;
		case "description" :
			echo wp_kses_post( $post->post_excerpt );
		break;
	}
}

add_action( 'manage_shop_coupon_posts_custom_column', 'woocommerce_custom_coupon_columns', 2 );