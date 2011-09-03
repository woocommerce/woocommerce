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

global $woocommerce_query;

$woocommerce_query['unfiltered_product_ids'] = array(); // Unfilted product ids (before layered nav etc)
$woocommerce_query['filtered_product_ids'] = array(); // Filted product ids (after layered nav)
$woocommerce_query['post__in'] = array(); // Product id's that match the layered nav + price filter
$woocommerce_query['meta_query'] = ''; // The meta query for the page
$woocommerce_query['layered_nav_post__in'] = array(); // posts matching layered nav only
$woocommerce_query['layered_nav_product_ids'] = array(); // Stores posts matching layered nav, so price filter can find max price in view

/**
 * Query the products, applying sorting/ordering etc. This applies to the main wordpress loop
 */
if (!is_admin()) add_filter( 'parse_query', 'woocommerce_parse_query' ); 
 
function woocommerce_parse_query( $q ) {
	
	// Apply to main loop only
    remove_filter( 'parse_query', 'woocommerce_parse_query' );
    
	// Only apply to product categories, the product post archive, the shop page, and product tags
    if (true == $q->query_vars['suppress_filters'] || (!$q->is_tax( 'product_cat' ) && !$q->is_post_type_archive( 'product' ) && !$q->is_page( get_option('woocommerce_shop_page_id') ) && !$q->is_tax( 'product_tag' ))) return;
	
	global $woocommerce_query;

	$woocommerce_query['meta_query'] = (array) $q->get( 'meta_query' );
	
	// Visibility
    if ( is_search() ) $in = array( 'visible', 'search' ); else $in = array( 'visible', 'catalog' );

    $woocommerce_query['meta_query'][] = array(
        'key' => 'visibility',
        'value' => $in,
        'compare' => 'IN'
    );
    
    // In stock
	if (get_option('woocommerce_hide_out_of_stock_items')=='yes') :
		 $woocommerce_query['meta_query'][] = array(
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
	$woocommerce_query['post__in'] = array_unique(apply_filters('loop-shop-posts-in', array()));
	
	// Ordering query vars
	$q->set( 'orderby', $orderby );
	$q->set( 'order', $order );
	$q->set( 'meta_key', $meta_key );

	// Query vars that affect posts shown
	$q->set( 'post_type', 'product' );
	$q->set( 'meta_query', $woocommerce_query['meta_query'] );
    $q->set( 'post__in', $woocommerce_query['post__in'] );
    $q->set( 'posts_per_page', apply_filters('loop_shop_per_page', get_option('posts_per_page')) );

    // We're on a shop page so queue the woocommerce_get_products_in_view function
    add_action('wp', 'woocommerce_get_products_in_view', 2);
}

/**
 * Get an unpaginated list all product ID's (both filtered and unfiltered)
 */
function woocommerce_get_products_in_view() {
	
	global $woocommerce_query, $wp_query;
	
	// Get all visible posts, regardless of filters
    $products = get_posts(
		array_merge( 
			$wp_query->query,
			array(
				'post_type' => 'product',
				'numberposts' => -1,
				'post_status' => 'publish',
				'meta_query' => $woocommerce_query['meta_query']
			)
		)
	);
	
	// Add posts to array
	foreach ($products as $p) $woocommerce_query['unfiltered_product_ids'][] = $p->ID;
	
	// Also store filtered posts ids...
	if (sizeof($woocommerce_query['post__in'])>0) :
		$woocommerce_query['filtered_product_ids'] = array_intersect($woocommerce_query['unfiltered_product_ids'], $woocommerce_query['post__in']);
	else :
		$woocommerce_query['filtered_product_ids'] = $woocommerce_query['unfiltered_product_ids'];
	endif;
	
	// And filtered post ids which just take layered nav into consideration (to find max price in the price widget)
	if (sizeof($woocommerce_query['layered_nav_post__in'])>0) :
		$woocommerce_query['layered_nav_product_ids'] = array_intersect($woocommerce_query['unfiltered_product_ids'], $woocommerce_query['layered_nav_post__in']);
	else :
		$woocommerce_query['layered_nav_product_ids'] = $woocommerce_query['unfiltered_product_ids'];
	endif;
}
 
 
/**
 * Layered Nav Init
 */
add_action('init', 'woocommerce_layered_nav_init', 1);

function woocommerce_layered_nav_init() {

	global $_chosen_attributes, $wpdb;
	
	$_chosen_attributes = array();
	
	$attribute_taxonomies = woocommerce::$attribute_taxonomies;
	if ( $attribute_taxonomies ) :
		foreach ($attribute_taxonomies as $tax) :
	    	
	    	$attribute = strtolower(sanitize_title($tax->attribute_name));
	    	$taxonomy = woocommerce::attribute_name($attribute);
	    	$name = 'filter_' . $attribute;
	    	
	    	if (isset($_GET[$name]) && taxonomy_exists($taxonomy)) $_chosen_attributes[$taxonomy] = explode(',', $_GET[$name] );
	    		
	    endforeach;    	
    endif;

}

/**
 * Layered Nav
 */
add_filter('loop-shop-posts-in', 'woocommerce_layered_nav_query');

function woocommerce_layered_nav_query( $filtered_posts ) {
	
	global $_chosen_attributes, $wpdb, $woocommerce_query;
	
	if (sizeof($_chosen_attributes)>0) :
		
		$matched_products = array();
		$filtered = false;
		
		foreach ($_chosen_attributes as $attribute => $values) :
			if (sizeof($values)>0) :
				foreach ($values as $value) :
					
					$posts = get_objects_in_term( $value, $attribute );
					if (!is_wp_error($posts) && (sizeof($matched_products)>0 || $filtered)) :
						$matched_products = array_intersect($posts, $matched_products);
					elseif (!is_wp_error($posts)) :
						$matched_products = $posts;
					endif;
					
					$filtered = true;
					
				endforeach;
			endif;
		endforeach;
		
		if ($filtered) :
			
			$woocommerce_query['layered_nav_post__in'] = $matched_products;
			$woocommerce_query['layered_nav_post__in'][] = 0;
			
			if (sizeof($filtered_posts)==0) :
				$filtered_posts = $matched_products;
				$filtered_posts[] = 0;
			else :
				$filtered_posts = array_intersect($filtered_posts, $matched_products);
				$filtered_posts[] = 0;
			endif;
			
		endif;
	
	endif;

	return (array) $filtered_posts;
}


/**
 * Price Filtering
 */
add_filter('loop-shop-posts-in', 'woocommerce_price_filter');

function woocommerce_price_filter( $filtered_posts ) {

	if (isset($_GET['max_price']) && isset($_GET['min_price'])) :
		
		$matched_products = array();
		
		$matched_products_query = get_posts(array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'price',
					'value' => array( $_GET['min_price'], $_GET['max_price'] ),
					'type' => 'NUMERIC',
					'compare' => 'BETWEEN'
				)
			)
		));

		if ($matched_products_query) :
			foreach ($matched_products_query as $product) :
				$matched_products[] = $product->ID;
				if ($product->post_parent>0) $matched_products[] = $product->post_parent;
			endforeach;
		endif;
		
		// Filter the id's
		if (sizeof($filtered_posts)==0) :
			$filtered_posts = $matched_products;
			$filtered_posts[] = 0;
		else :
			$filtered_posts = array_intersect($filtered_posts, $matched_products);
			$filtered_posts[] = 0;
		endif;
		
	endif;
	
	return (array) $filtered_posts;
}