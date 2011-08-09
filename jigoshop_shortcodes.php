<?php
foreach(glob( dirname(__FILE__)."/shortcodes/*.php" ) as $filename) include_once($filename);

### Recent Products #########################################################

function jigoshop_recent_products( $atts ) {
	
	global $columns, $per_page;
	
	extract(shortcode_atts(array(
		'per_page' 	=> '12',
		'columns' 	=> '4',
		'orderby' => 'date',
		'order' => 'desc'
	), $atts));
	
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
	jigoshop_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	
	return ob_get_clean();
}

### Multiple Products #########################################################

function jigoshop_products($atts){
	global $columns;
	
  	if (empty($atts)) return;
  
	extract(shortcode_atts(array(
		'columns' 	=> '4',
	  	'orderby'   => 'title',
	  	'order'     => 'asc'
		), $atts));
	
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
	jigoshop_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	return ob_get_clean();
}

### Single Product ############################################################

function jigoshop_product($atts){
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
	jigoshop_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	return ob_get_clean();  
}

### Featured Products #########################################################

function jigoshop_featured_products( $atts ) {
	
	global $columns, $per_page;
	
	extract(shortcode_atts(array(
		'per_page' 	=> '12',
		'columns' 	=> '4',
		'orderby' => 'date',
		'order' => 'desc'
	), $atts));
	
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
	jigoshop_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	
	return ob_get_clean();
}

### Shortcodes #########################################################

add_shortcode('product', 'jigoshop_product');
add_shortcode('products', 'jigoshop_products');

add_shortcode('recent_products', 'jigoshop_recent_products');
add_shortcode('featured_products', 'jigoshop_featured_products');

add_shortcode('jigoshop_cart', 'get_jigoshop_cart');
add_shortcode('jigoshop_checkout', 'get_jigoshop_checkout');
add_shortcode('jigoshop_order_tracking', 'get_jigoshop_order_tracking');
add_shortcode('jigoshop_my_account', 'get_jigoshop_my_account');
add_shortcode('jigoshop_edit_address', 'get_jigoshop_edit_address');
add_shortcode('jigoshop_change_password', 'get_jigoshop_change_password');
add_shortcode('jigoshop_view_order', 'get_jigoshop_view_order');
add_shortcode('jigoshop_pay', 'get_jigoshop_pay');
add_shortcode('jigoshop_thankyou', 'get_jigoshop_thankyou');
