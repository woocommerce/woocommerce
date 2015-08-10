<?php
/**
 * Update WC to 2.4.1
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Updates
 * @version  2.4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$update_variations = $wpdb->get_results( "
	SELECT DISTINCT posts.ID AS variation_id, posts.post_parent AS variation_parent
	FROM {$wpdb->posts} as posts
	LEFT OUTER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_stock_status'
	WHERE posts.post_type = 'product_variation'
	AND postmeta.meta_value IS NULL
" );

$rates = WC_Tax::get_rates();
$transient_version = WC_Cache_Helper::get_transient_version( 'product' );

foreach ( $update_variations as $variation ) {
	$parent_stock_status = get_post_meta( $variation->variation_parent, '_stock_status', true );
	add_post_meta( $variation->variation_id, '_stock_status', $parent_stock_status ? $parent_stock_status : 'instock', true );
	delete_transient( 'wc_product_children_' . $variation->variation_parent );
	delete_transient( 'wc_var_prices' . md5( json_encode( array(
		$variation->variation_parent,
		$rates,
		$transient_version
	) ) ) );
}
