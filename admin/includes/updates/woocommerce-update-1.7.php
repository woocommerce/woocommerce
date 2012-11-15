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

// Update subcat display settings
if ( get_option( 'woocommerce_shop_show_subcategories' ) == 'yes' ) {
	if ( get_option( 'woocommerce_hide_products_when_showing_subcategories' ) == 'yes' ) {
		update_option( 'woocommerce_shop_page_display', 'subcategories' );
	} else {
		update_option( 'woocommerce_shop_page_display', 'both' );
	}
}

if ( get_option( 'woocommerce_show_subcategories' ) == 'yes' ) {
	if ( get_option( 'woocommerce_hide_products_when_showing_subcategories' ) == 'yes' ) {
		update_option( 'woocommerce_category_archive_display', 'subcategories' );
	} else {
		update_option( 'woocommerce_category_archive_display', 'both' );
	}
}

// Now its time for the massive update to line items - move them to the new DB tables
// Reverse with UPDATE `wpwc_postmeta` SET meta_key = '_order_items' WHERE meta_key = '_order_items_old'
$order_item_rows = $wpdb->get_results( $wpdb->prepare( "
	SELECT * FROM {$wpdb->postmeta}
	WHERE meta_key = '_order_items'
" ) );

foreach ( $order_item_rows as $order_item_row ) {
	
	$order_items = (array) maybe_unserialize( $order_item_row->meta_value );
	
	foreach ( $order_items as $order_item ) {
		
		if ( ! isset( $order_item['line_total'] ) && isset( $order_item['taxrate'] ) && isset( $order_item['cost'] ) ) {
			$order_item['line_tax'] 			= number_format( ( $order_item['cost'] * $order_item['qty'] ) * ( $order_item['taxrate'] / 100 ), 2, '.', '' );
			$order_item['line_total'] 			= $order_item['cost'] * $order_item['qty'];
			$order_item['line_subtotal_tax'] 	= $order_item['line_tax'];
			$order_item['line_subtotal'] 		= $order_item['line_total'];
		}
		
		$order_item['line_tax'] 			= isset( $order_item['line_tax'] ) ? $order_item['line_tax'] : 0;
		$order_item['line_total']			= isset( $order_item['line_total'] ) ? $order_item['line_total'] : 0;
		$order_item['line_subtotal_tax'] 	= isset( $order_item['line_subtotal_tax'] ) ? $order_item['line_subtotal_tax'] : 0;
		$order_item['line_subtotal'] 		= isset( $order_item['line_subtotal'] ) ? $order_item['line_subtotal'] : 0;
		
		$item_id = woocommerce_add_order_item( $order_item_row->post_id, array(
	 		'order_item_name' 		=> $order_item['name'],
	 		'order_item_type' 		=> 'line_item'
	 	) );
	 	
	 	// Add line item meta
	 	if ( $item_id ) {
		 	woocommerce_add_order_item_meta( $item_id, '_qty', absint( $order_item['qty'] ) );
		 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $order_item['tax_class'] );
		 	woocommerce_add_order_item_meta( $item_id, '_product_id', $order_item['id'] );
		 	woocommerce_add_order_item_meta( $item_id, '_variation_id', $order_item['variation_id'] );
		 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', woocommerce_format_decimal( $order_item['line_subtotal'] ) );
		 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_format_decimal( $order_item['line_subtotal_tax'] ) );
		 	woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $order_item['line_total'] ) );
		 	woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $order_item['line_tax'] ) );
		 	
		 	$meta_rows = array();
			
			// Insert meta
			if ( ! empty( $order_item['item_meta'] ) ) {
				foreach ( $order_item['item_meta'] as $key => $meta ) {
					// Backwards compatibility
					if ( is_array( $meta ) && isset( $meta['meta_name'] ) ) {
						$meta_rows[] = '(' . $item_id . ',"' . $wpdb->escape( $meta['meta_name'] ) . '","' . $wpdb->escape( $meta['meta_value'] ) . '")';
					} else {
						$meta_rows[] = '(' . $item_id . ',"' . $wpdb->escape( $key ) . '","' . $wpdb->escape( $meta ) . '")';
					}
				}
			}
			
			// Insert meta rows at once
			if ( sizeof( $meta_rows ) > 0 ) {
				$wpdb->query( $wpdb->prepare( "
					INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta ( order_item_id, meta_key, meta_value )
					VALUES " . implode( ',', $meta_rows ) . ";
				", $order_item_row->post_id ) );
			}
			
			// Delete from DB (rename)
			$wpdb->query( $wpdb->prepare( "
				UPDATE {$wpdb->postmeta}
				SET meta_key = '_order_items_old'
				WHERE meta_key = '_order_items'
				AND post_id = %d
			", $order_item_row->post_id ) );
	 	}
		
		unset( $meta_rows, $item_id, $order_item );
	}
}

// Do the same kind of update for order_taxes - move to lines
// Reverse with UPDATE `wpwc_postmeta` SET meta_key = '_order_taxes' WHERE meta_key = '_order_taxes_old'
$order_tax_rows = $wpdb->get_results( $wpdb->prepare( "
	SELECT * FROM {$wpdb->postmeta}
	WHERE meta_key = '_order_taxes'
" ) );

foreach ( $order_tax_rows as $order_tax_row ) {
	
	$order_taxes = (array) maybe_unserialize( $order_tax_row->meta_value );
	
	if ( $order_taxes ) {
		foreach( $order_taxes as $order_tax ) {
		
			if ( ! isset( $order_tax['label'] ) || ! isset( $order_tax['cart_tax'] ) || ! isset( $order_tax['shipping_tax'] ) )
				continue;
			
			$item_id = woocommerce_add_order_item( $order_tax_row->post_id, array(
		 		'order_item_name' 		=> $order_tax['label'],
		 		'order_item_type' 		=> 'tax'
		 	) );
		 	
		 	// Add line item meta
		 	if ( $item_id ) {
			 	woocommerce_add_order_item_meta( $item_id, 'compound', absint( isset( $order_tax['compound'] ) ? $order_tax['compound'] : 0 ) );
			 	woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $order_tax['cart_tax'] ) );
			 	woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $order_tax['shipping_tax'] ) );
			}
			
			// Delete from DB (rename)
			$wpdb->query( $wpdb->prepare( "
				UPDATE {$wpdb->postmeta}
				SET meta_key = '_order_taxes_old'
				WHERE meta_key = '_order_taxes'
				AND post_id = %d
			", $order_tax_row->post_id ) );
			
			unset( $tax_amount );
		}
	}
}

// Update manual counters for product categories (only counting visible products, so visibility: 'visible' or 'catalog')
// Loop through all products and put the IDs in array with each product category term id as index
$products_query_args = array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'meta_query' => array(
	 		array(
	 			'key' => '_visibility',
	 			'value' => array( 'visible', 'catalog' ),
	 			'compare' => 'IN',
	 		),
	 	),
	);

$products_query = new WP_Query( $products_query_args );

$counted_ids = array();

foreach( $products_query->posts as $visible_product ) {
	$product_terms = wp_get_post_terms( $visible_product->ID, 'product_cat', array( 'fields' => 'ids' ) );

	foreach ( $product_terms as $product_term_id ) {
		if ( ! isset( $counted_ids[ $product_term_id ] ) || ! is_array( $counted_ids[ $product_term_id ] ) ) {
			$counted_ids[ $product_term_id ] = array();
		}

		if ( ! in_array( $visible_product->ID, $counted_ids[ $product_term_id ] ) ) {
			array_push( $counted_ids[ $product_term_id ], $visible_product->ID );
		}
	}
}

update_option( 'wc_prod_cat_counts', $counted_ids );