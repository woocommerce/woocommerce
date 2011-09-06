<?php
/**
 * WooCommerce Queries
 * 
 * Handles front end queries and loops.
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */

/**
 * Query the products, applying sorting/ordering etc. This applies to the main wordpress loop
 */
add_filter( 'parse_query', 'woocommerce_parse_query' ); 
 
function woocommerce_parse_query( $q ) {
	global $woocommerce;
	
	if (is_admin()) return;
	    
	// Only apply to product categories, the product post archive, the shop page, and product tags
    if (true == $q->query_vars['suppress_filters'] || (!$q->is_tax( 'product_cat' ) && !$q->is_post_type_archive( 'product' ) && !$q->is_page( get_option('woocommerce_shop_page_id') ) && !$q->is_tax( 'product_tag' ))) return;
	
	$meta_query = (array) $q->get( 'meta_query' );
	
	// Visibility
    if ( is_search() ) $in = array( 'visible', 'search' ); else $in = array( 'visible', 'catalog' );

    $meta_query[] = array(
        'key' => 'visibility',
        'value' => $in,
        'compare' => 'IN'
    );
    
    // In stock
	if (get_option('woocommerce_hide_out_of_stock_items')=='yes') :
		 $meta_query[] = array(
	        'key' 		=> 'stock_status',
			'value' 	=> 'instock',
			'compare' 	=> '='
	    );
	endif;
	
	// Ordering
	if (isset($_POST['orderby']) && $_POST['orderby'] != '') $_SESSION['orderby'] = $_POST['orderby'];
	$current_order = (isset($_SESSION['orderby'])) ? $_SESSION['orderby'] : 'title';
	
	switch ($current_order) :
		case 'date' :
			$orderby = 'date';
			$order = 'desc';
			$meta_key = '';
		break;
		case 'price' :
			$orderby = 'meta_value_num';
			$order = 'asc';
			$meta_key = 'price';
		break;
		default :
			$orderby = 'title';
			$order = 'asc';
			$meta_key = '';
		break;
	endswitch;
	
	// Get a list of post id's which match the current filters set (in the layered nav and price filter)
	$post__in = array_unique(apply_filters('loop-shop-posts-in', array()));
	
	// Ordering query vars
	$q->set( 'orderby', $orderby );
	$q->set( 'order', $order );
	$q->set( 'meta_key', $meta_key );

	// Query vars that affect posts shown
	$q->set( 'post_type', 'product' );
	$q->set( 'meta_query', $meta_query );
    $q->set( 'post__in', $post__in );
    $q->set( 'posts_per_page', apply_filters('loop_shop_per_page', get_option('posts_per_page')) );
    
    // Store variables
    $woocommerce->query['post__in'] = $post__in;
    $woocommerce->query['meta_query'] = $meta_query;

    // We're on a shop page so queue the woocommerce_get_products_in_view function
    add_action('wp', 'woocommerce_get_products_in_view', 2);
}

/**
 * Remove parse_query so it only applies to main loop
 */
add_action('wp', 'woocommerce_remove_parse_query');

function woocommerce_remove_parse_query() {
	remove_filter( 'parse_query', 'woocommerce_parse_query' ); 
}

/**
 * Get an unpaginated list all product ID's (both filtered and unfiltered)
 */
function woocommerce_get_products_in_view() {
	
	global $wp_query, $woocommerce;
	
	$unfiltered_product_ids = array();
	
	// Get all visible posts, regardless of filters
    $products = get_posts(
		array_merge( 
			$wp_query->query,
			array(
				'post_type' => 'product',
				'numberposts' => -1,
				'post_status' => 'publish',
				'meta_query' => $woocommerce->query['meta_query']
			)
		)
	);
	
	// Add posts to array
	foreach ($products as $p) $unfiltered_product_ids[] = $p->ID;
	
	// Store the variable
	$woocommerce->query['unfiltered_product_ids'] = $unfiltered_product_ids;
	
	// Also store filtered posts ids...
	if (sizeof($woocommerce->query['post__in'])>0) :
		$woocommerce->query['filtered_product_ids'] = array_intersect($woocommerce->query['unfiltered_product_ids'], $woocommerce->query['post__in']);
	else :
		$woocommerce->query['filtered_product_ids'] = $woocommerce->query['unfiltered_product_ids'];
	endif;
	
	// And filtered post ids which just take layered nav into consideration (to find max price in the price widget)
	if (sizeof($woocommerce->query['layered_nav_post__in'])>0) :
		$woocommerce->query['layered_nav_product_ids'] = array_intersect($woocommerce->query['unfiltered_product_ids'], $woocommerce->query['layered_nav_post__in']);
	else :
		$woocommerce->query['layered_nav_product_ids'] = $woocommerce->query['unfiltered_product_ids'];
	endif;
}