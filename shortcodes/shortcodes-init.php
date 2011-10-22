<?php
/**
 * Shortcodes init
 * 
 * Init main shortcodes, and add a few others such as recent products.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
 
include_once('shortcode-cart.php');
include_once('shortcode-checkout.php');
include_once('shortcode-my_account.php');
include_once('shortcode-order_tracking.php');
include_once('shortcode-pay.php');
include_once('shortcode-thankyou.php');

/**
 * Recent Products shortcode
 **/
function woocommerce_recent_products( $atts ) {
	
	global $woocommerce_loop;
	
	extract(shortcode_atts(array(
		'per_page' 	=> '12',
		'columns' 	=> '4',
		'orderby' => 'date',
		'order' => 'desc'
	), $atts));
	
	$woocommerce_loop['columns'] = $columns;
	
	$args = array(
		'post_type'	=> 'product',
		'post_status' => 'publish',
		'ignore_sticky_posts'	=> 1,
		'posts_per_page' => $per_page,
		'orderby' => $orderby,
		'order' => $order,
		'meta_query' => array(
			array(
				'key' => 'visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			)
		)
	);
	
	query_posts($args);
	ob_start();
	woocommerce_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	
	return ob_get_clean();
}

/**
 * List multiple products shortcode
 **/
function woocommerce_products($atts){
	global $woocommerce_loop;
	
  	if (empty($atts)) return;
  
	extract(shortcode_atts(array(
		'columns' 	=> '4',
	  	'orderby'   => 'title',
	  	'order'     => 'asc'
		), $atts));
		
	$woocommerce_loop['columns'] = $columns;
	
  	$args = array(
		'post_type'	=> 'product',
		'post_status' => 'publish',
		'ignore_sticky_posts'	=> 1,
		'orderby' => $orderby,
		'order' => $order,
		'meta_query' => array(
			array(
				'key' => 'visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			)
		)
	);
	
	if(isset($atts['skus'])){
		$skus = explode(',', $atts['skus']);
	  	array_walk($skus, create_function('&$val', '$val = trim($val);'));
    	$args['meta_query'][] = array(
      		'key' => 'sku',
      		'value' => $skus,
      		'compare' => 'IN'
    	);
  	}
	
	if(isset($atts['ids'])){
		$ids = explode(',', $atts['ids']);
	  	array_walk($ids, create_function('&$val', '$val = trim($val);'));
    	$args['post__in'] = $ids;
	}
	
  	query_posts($args);
	
  	ob_start();
	woocommerce_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	return ob_get_clean();
}

/**
 * Display a single prodcut
 **/
function woocommerce_product($atts){
  	if (empty($atts)) return;
  
  	$args = array(
    	'post_type' => 'product',
    	'posts_per_page' => 1,
    	'post_status' => 'publish',
    	'meta_query' => array(
			array(
				'key' => 'visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			)
		)
  	);
  
  	if(isset($atts['sku'])){
    	$args['meta_query'][] = array(
      		'key' => 'sku',
      		'value' => $atts['sku'],
      		'compare' => '='
    	);
  	}
  
  	if(isset($atts['id'])){
    	$args['p'] = $atts['id'];
  	}
  
  	query_posts($args);
	
  	ob_start();
	woocommerce_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	return ob_get_clean();  
}

/**
 * Output featured products
 **/
function woocommerce_featured_products( $atts ) {
	
	global $woocommerce_loop;
	
	extract(shortcode_atts(array(
		'per_page' 	=> '12',
		'columns' 	=> '4',
		'orderby' => 'date',
		'order' => 'desc'
	), $atts));
	
	$woocommerce_loop['columns'] = $columns;
	
	$args = array(
		'post_type'	=> 'product',
		'post_status' => 'publish',
		'ignore_sticky_posts'	=> 1,
		'posts_per_page' => $per_page,
		'orderby' => $orderby,
		'order' => $order,
		'meta_query' => array(
			array(
				'key' => 'visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			),
			array(
				'key' => 'featured',
				'value' => 'yes'
			)
		)
	);
	query_posts($args);
	ob_start();
	woocommerce_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	
	return ob_get_clean();
}

/**
 * Shortcode creation
 **/
add_shortcode('product', 'woocommerce_product');
add_shortcode('products', 'woocommerce_products');
add_shortcode('recent_products', 'woocommerce_recent_products');
add_shortcode('featured_products', 'woocommerce_featured_products');
add_shortcode('woocommerce_cart', 'get_woocommerce_cart');
add_shortcode('woocommerce_checkout', 'get_woocommerce_checkout');
add_shortcode('woocommerce_order_tracking', 'get_woocommerce_order_tracking');
add_shortcode('woocommerce_my_account', 'get_woocommerce_my_account');
add_shortcode('woocommerce_edit_address', 'get_woocommerce_edit_address');
add_shortcode('woocommerce_change_password', 'get_woocommerce_change_password');
add_shortcode('woocommerce_view_order', 'get_woocommerce_view_order');
add_shortcode('woocommerce_pay', 'get_woocommerce_pay');
add_shortcode('woocommerce_thankyou', 'get_woocommerce_thankyou');
