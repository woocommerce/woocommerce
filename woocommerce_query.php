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
 * Find and get a specific variation
 */
function woocommerce_find_variation( $variation_data = array() ) {

	foreach ($variation_data as $key => $value) :
		
		if (!strstr($key, 'tax_')) continue;
		
		$variation_query[] = array(
			'key' 		=> $key,
			'value' 	=> array( $value ),
			'compare'	=> 'IN'
		);
		
	endforeach;
	
	// do the query
	$args = array(
		'post_type' 		=> 'product_variation',
		'orderby'			=> 'id',
		'order'				=> 'desc',
		'posts_per_page'	=> 1,
		'meta_query' 		=> $variation_query
	);
	$posts = get_posts( $args );
	
	if (!$posts) :
	
		// Wildcard search
		$variation_query = array();
		foreach ($variation_data as $key => $value) :
			
			if (!strstr($key, 'tax_')) continue;
			
			$variation_query[] = array(
				'key' 		=> $key,
				'value' 	=> array( $value, '' ),
				'compare'	=> 'IN'
			);
			
		endforeach;
		$args['meta_query'] = $variation_query;
		
		$posts = get_posts( $args );
		
	endif;
	
	if (!$posts) return false;
	
	return $posts[0]->ID;
	
}
 
/**
 * Get unfiltered list of posts in current view for use in loop + widgets
 */
function woocommerce_get_products_in_view() {
	
	global $all_post_ids;
	
	$all_post_ids = array();
	
	if (is_tax( 'product_cat' ) || is_post_type_archive('product') || is_page( get_option('woocommerce_shop_page_id') ) || is_tax( 'product_tag' )) :
	
		$all_post_ids = woocommerce_get_post_ids();
	
	endif;
	
	$all_post_ids[] = 0;

}

add_action('wp_head', 'woocommerce_get_products_in_view', 0);

/**
 * Do Filters/Layered Nav
 */
function woocommerce_filter_loop() {
	
	global $wp_query, $all_post_ids; 
	
	if (is_tax( 'product_cat' ) || is_post_type_archive('product') || is_page( get_option('woocommerce_shop_page_id') ) || is_tax( 'product_tag' )) :
		
		$filters = array();
		$filters = apply_filters('loop-shop-query', $filters);
		
		//$post_ids = woocommerce_get_post_ids();
		$post_ids = $all_post_ids;
		$post_ids = apply_filters('loop-shop-posts-in', $post_ids);
		$filters = array_merge($filters, array(
			'post__in' => $post_ids
		));
		
		$args = array_merge( $wp_query->query, $filters );
		
		query_posts( $args );
	endif;
}

add_action('wp_head', 'woocommerce_filter_loop');

/**
 * Layered Nav Init
 */
function woocommerce_layered_nav_init() {

	global $_chosen_attributes, $wpdb;
	
	$attribute_taxonomies = woocommerce::$attribute_taxonomies;
	if ( $attribute_taxonomies ) :
		foreach ($attribute_taxonomies as $tax) :
	    	
	    	$attribute = strtolower(sanitize_title($tax->attribute_name));
	    	$taxonomy = 'product_attribute_' . $attribute;
	    	$name = 'filter_' . $attribute;
	    	
	    	if (isset($_GET[$name]) && taxonomy_exists($taxonomy)) $_chosen_attributes[$taxonomy] = explode(',', $_GET[$name] );
	    		
	    endforeach;    	
    endif;

}
add_action('init', 'woocommerce_layered_nav_init', 1);

/**
 * Get post ID's to filter from
 */
function woocommerce_get_post_ids() {
	
	global $wpdb;
	
	$in = array('visible');
	if (is_search()) $in[] = 'search';
	if (!is_search()) $in[] = 'catalog';
	
	// WP Query to get all queried post ids
	
	global $wp_query;
	
	$args = array_merge(
		$wp_query->query,
		array(
			'page_id' => '',
			'posts_per_page' => -1,
			'post_type' => 'product',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'visibility',
					'value' => $in,
					'compare' => 'IN'
				)
			)
		)
	);
	$custom_query  = new WP_Query( $args );
	
	$queried_post_ids = array();
	
	foreach ($custom_query->posts as $p) $queried_post_ids[] = $p->ID;
	
	wp_reset_query();
	
	return $queried_post_ids;
}

/**
 * Layered Nav
 */
function woocommerce_layered_nav_query( $filtered_posts ) {
	
	global $_chosen_attributes, $wpdb;
	
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
			$matched_products[] = 0;
			$filtered_posts = array_intersect($filtered_posts, $matched_products);
		endif;
	
	endif;

	return $filtered_posts;
}

add_filter('loop-shop-posts-in', 'woocommerce_layered_nav_query');


/**
 * Price Filtering
 */
function woocommerce_price_filter( $filtered_posts ) {

	if (isset($_GET['max_price']) && isset($_GET['min_price'])) :
		
		$matched_products = array( 0 );
		
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
			),
			'tax_query' => array(
				array(
					'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => 'grouped',
					'operator' => 'NOT IN'
				)
			)
		));

		if ($matched_products_query) :

			foreach ($matched_products_query as $product) :
				$matched_products[] = $product->ID;
			endforeach;
			
		endif;
		
		// Get grouped product ids
		$grouped_products = get_objects_in_term( get_term_by('slug', 'grouped', 'product_type')->term_id, 'product_type' );
		
		if ($grouped_products) foreach ($grouped_products as $grouped_product) :
			
			$children = get_children( 'post_parent='.$grouped_product.'&post_type=product' );
			
			if ($children) foreach ($children as $product) :
				$price = get_post_meta( $product->ID, 'price', true);

				if ($price<=$_GET['max_price'] && $price>=$_GET['min_price']) :
					
					$matched_products[] = $grouped_product;
				
					break;
					
				endif;
			endforeach;
		
		endforeach;
		
		$filtered_posts = array_intersect($matched_products, $filtered_posts);
		
	endif;
	
	return $filtered_posts;	
}

add_filter('loop-shop-posts-in', 'woocommerce_price_filter');