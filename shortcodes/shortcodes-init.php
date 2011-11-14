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
 * Shortcode button in post editor
 **/
add_action( 'init', 'woocommerce_add_shortcode_button' );
add_filter( 'tiny_mce_version', 'woocommerce_refresh_mce' );

function woocommerce_add_shortcode_button() {
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) return;
	if ( get_user_option('rich_editing') == 'true') :
		add_filter('mce_external_plugins', 'woocommerce_add_shortcode_tinymce_plugin');
		add_filter('mce_buttons', 'woocommerce_register_shortcode_button');
	endif;
}

function woocommerce_register_shortcode_button($buttons) {
	array_push($buttons, "|", "woocommerce_shortcodes_button");
	return $buttons;
}

function woocommerce_add_shortcode_tinymce_plugin($plugin_array) {
	global $woocommerce;
	$plugin_array['WooCommerceShortcodes'] = $woocommerce->plugin_url() . '/assets/js/admin/editor_plugin.js';
	return $plugin_array;
}

function woocommerce_refresh_mce($ver) {
	$ver += 3;
	return $ver;
}

/**
 * List products in a category shortcode
 **/
function woocommerce_product_category($atts){
	global $woocommerce_loop;
	
  	if (empty($atts)) return;
  
	extract(shortcode_atts(array(
		'per_page' 		=> '12',
		'columns' 		=> '4',
	  	'orderby'   	=> 'title',
	  	'order'     	=> 'asc',
	  	'category'		=> ''
		), $atts));
		
	if (!$category) return;
		
	$woocommerce_loop['columns'] = $columns;
	
  	$args = array(
		'post_type'	=> 'product',
		'post_status' => 'publish',
		'ignore_sticky_posts'	=> 1,
		'orderby' => $orderby,
		'order' => $order,
		'posts_per_page' => $per_page,
		'meta_query' => array(
			array(
				'key' => 'visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			)
		),
		'tax_query' => array(
	    	array(
		    	'taxonomy' => 'product_cat',
				'terms' => array( esc_attr($category) ),
				'field' => 'slug',
				'operator' => 'IN'
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
 * Display a single prodcut price + cart button
 **/
function woocommerce_product_add_to_cart($atts){
  	if (empty($atts)) return;
  	
  	global $wpdb;
  	
  	if (!$atts['style']) $atts['style'] = 'border:4px solid #ccc; padding: 12px;';
  	
  	if ($atts['id']) :
  		$product_data = get_post( $atts['id'] );
	elseif ($atts['sku']) :
		$product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='sku' AND meta_value='%s' LIMIT 1", $atts['sku']));
		$product_data = get_post( $product_id );
	else :
		return;
	endif;
	
	if ($product_data->post_type!=='product') return;
	
	$_product = &new woocommerce_product( $product_data->ID ); 
		
	if (!$_product->is_visible()) continue; 
	
	ob_start();
	?>
	<p class="product" style="<?php echo $atts['style']; ?>">
	
		<?php echo $_product->get_price_html(); ?>
		
		<?php woocommerce_template_loop_add_to_cart( $product_data, $_product ); ?>
					
	</p><?php 
	
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
add_shortcode('product_category', 'woocommerce_product_category');
add_shortcode('product', 'woocommerce_product');
add_shortcode('add_to_cart', 'woocommerce_product_add_to_cart');
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
