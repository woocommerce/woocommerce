<?php
/**
 * Update WC to 1.7
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb, $woocommerce;

// Upgrade old style files paths to support multiple file paths
$existing_file_paths = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->postmeta . " WHERE meta_key = '_file_path'" ) );

if ( $existing_file_paths ) {
	
	foreach( $existing_file_paths as $existing_file_path ) {
		
		$existing_file_path->meta_value = trim( $existing_file_path->meta_value );
		
		if ( $existing_file_path->meta_value ) 
			$file_paths = maybe_serialize( array( md5( $existing_file_path->meta_value ) => $existing_file_path->meta_value ) );
		else 
			$file_paths = '';
		
		$wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->postmeta . " SET meta_key = '_file_paths', meta_value = %s WHERE meta_id = %d", $file_paths, $existing_file_path->meta_id ) );
		
		$wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->prefix . "woocommerce_downloadable_product_permissions SET download_id = %s WHERE product_id = %d", md5( $existing_file_path->meta_value ), $existing_file_path->post_id ) );
	}
	
}

// Update table primary keys
$wpdb->query( $wpdb->prepare( "ALTER TABLE ". $wpdb->prefix . "woocommerce_downloadable_product_permissions DROP PRIMARY KEY" ) );

$wpdb->query( $wpdb->prepare( "ALTER TABLE ". $wpdb->prefix . "woocommerce_downloadable_product_permissions ADD PRIMARY KEY (  `product_id` ,  `order_id` ,  `order_key` ,  `download_id` )" ) );

// Setup default permalinks if shop page is defined
$permalinks 	= get_option( 'woocommerce_permalinks' );
$shop_page_id 	= woocommerce_get_page_id( 'shop' );

if ( empty( $permalinks ) && $shop_page_id > 0 ) {

	$base_slug 		= $shop_page_id > 0 && get_page( $shop_page_id ) ? get_page_uri( $shop_page_id ) : 'shop';
	
	$category_base 	= get_option('woocommerce_prepend_shop_page_to_urls') == "yes" ? trailingslashit( $base_slug ) : '';
	$category_slug 	= get_option('woocommerce_product_category_slug') ? get_option('woocommerce_product_category_slug') : _x( 'product-category', 'slug', 'woocommerce' );
	$tag_slug 		= get_option('woocommerce_product_tag_slug') ? get_option('woocommerce_product_tag_slug') : _x( 'product-tag', 'slug', 'woocommerce' );
	
	if ( 'yes' == get_option('woocommerce_prepend_shop_page_to_products') ) {
		$product_base = trailingslashit( $base_slug );
	} else {
		if ( ( $product_slug = get_option('woocommerce_product_slug') ) !== false && ! empty( $product_slug ) ) {
			$product_base = trailingslashit( $product_slug );
		} else {
			$product_base = trailingslashit( _x('product', 'slug', 'woocommerce') );
		}
	}
	
	if ( get_option('woocommerce_prepend_category_to_products') == 'yes' ) 
		$product_base .= trailingslashit('%product_cat%');

	$permalinks = array(
		'product_base' 		=> untrailingslashit( $product_base ),
		'category_base' 	=> untrailingslashit( $category_base . $category_slug ),
		'attribute_base' 	=> untrailingslashit( $category_base ),
		'tag_base' 			=> untrailingslashit( $category_base . $tag_slug )
	);
	
	update_option( 'woocommerce_permalinks', $permalinks );	
}