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
				'key' => '_visibility',
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
				'key' => '_visibility',
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
				'key' 		=> '_visibility',
				'value' 	=> array('catalog', 'visible'),
				'compare' 	=> 'IN'
			)
		)
	);
	
	if(isset($atts['skus'])){
		$skus = explode(',', $atts['skus']);
	  	$skus = array_map('trim', $skus);
    	$args['meta_query'][] = array(
      		'key' 		=> '_sku',
      		'value' 	=> $skus,
      		'compare' 	=> 'IN'
    	);
  	}
	
	if(isset($atts['ids'])){
		$ids = explode(',', $atts['ids']);
	  	$ids = array_map('trim', $ids);
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
    	'no_found_rows' => 1,
    	'post_status' => 'publish',
    	'meta_query' => array(
			array(
				'key' => '_visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			)
		)
  	);
  
  	if(isset($atts['sku'])){
    	$args['meta_query'][] = array(
      		'key' => '_sku',
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
  	
  	global $wpdb, $woocommerce;
  	
  	if (!isset($atts['style'])) $atts['style'] = 'border:4px solid #ccc; padding: 12px;';
  	
  	if ($atts['id']) :
  		$product_data = get_post( $atts['id'] );
	elseif ($atts['sku']) :
		$product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $atts['sku']));
		$product_data = get_post( $product_id );
	else :
		return;
	endif;
	
	if ($product_data->post_type=='product') {
		
		$product = $woocommerce->setup_product_data( $product_data );
			
		if (!$product->is_visible()) continue; 
		
		ob_start();
		?>
		<p class="product" style="<?php echo $atts['style']; ?>">
		
			<?php echo $product->get_price_html(); ?>
			
			<?php woocommerce_template_loop_add_to_cart(); ?>
						
		</p><?php 
		
		return ob_get_clean();  
	
	} elseif ($product_data->post_type=='product_variation') {
		
		$product = new WC_Product( $product_data->post_parent );
		
		$GLOBALS['product'] = $product;
		
		$variation = new WC_Product_Variation( $product_data->ID );
			
		if (!$product->is_visible()) continue; 
		
		ob_start();
		?>
		<p class="product product-variation" style="<?php echo $atts['style']; ?>">
		
			<?php echo $product->get_price_html(); ?>
			
			<?php
			
			$link 	= $product->add_to_cart_url();
			
			$label 	= apply_filters('add_to_cart_text', __('Add to cart', 'woocommerce'));
			
			$link = add_query_arg( 'variation_id', $variation->variation_id, $link );
			
			foreach ($variation->variation_data as $key => $data) {
				if ($data) $link = add_query_arg( $key, $data, $link );
			}
			
			echo sprintf('<a href="%s" data-product_id="%s" class="button add_to_cart_button product_type_%s">%s</a>', esc_url( $link ), $product->id, $product->product_type, $label);
			
			?>
						
		</p><?php 
		
		return ob_get_clean();  

	}
}


/**
 * Get the add to cart URL for a product
 **/
function woocommerce_product_add_to_cart_url( $atts ){
  	if (empty($atts)) return;
  	
  	global $wpdb;
  	  	
  	if ($atts['id']) :
  		$product_data = get_post( $atts['id'] );
	elseif ($atts['sku']) :
		$product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $atts['sku']));
		$product_data = get_post( $product_id );
	else :
		return;
	endif;
	
	if ($product_data->post_type!=='product') return;
	
	$_product = new WC_Product( $product_data->ID ); 
		
	return esc_url( $_product->add_to_cart_url() );
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
				'key' => '_visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			),
			array(
				'key' => '_featured',
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
 * Show a single product page
 **/
function woocommerce_product_page_shortcode($atts){
  	if (empty($atts)) return;
	
	if (!$atts['id'] && !$atts['sku']) return;
	
  	$args = array(
    	'posts_per_page' 	=> 1,
    	'post_type'	=> 'product',
    	'post_status' => 'publish',
    	'ignore_sticky_posts'	=> 1,
    	'no_found_rows' => 1
  	);

  	if(isset($atts['sku'])){
    	$args['meta_query'][] = array(
      		'key' => '_sku',
      		'value' => $atts['sku'],
      		'compare' => '='
    	);
  	}

  	if(isset($atts['id'])){
    	$args['p'] = $atts['id'];
  	}

  	$product_query = new WP_Query($args);
  	ob_start();
  	echo '<div class="single-product">';
	woocommerce_single_product_content( $product_query );
	echo '</div>';
	wp_reset_query();
	return ob_get_clean();
}	

/**
 * Show messages
 **/
function woocommerce_messages_shortcode() {
	ob_start();
	
	woocommerce_show_messages();
	
	return ob_get_clean();
}

/**
 * Shortcode creation
 **/
add_shortcode('product', 'woocommerce_product');
add_shortcode('product_page', 'woocommerce_product_page_shortcode');
add_shortcode('product_category', 'woocommerce_product_category');
add_shortcode('add_to_cart', 'woocommerce_product_add_to_cart');
add_shortcode('add_to_cart_url', 'woocommerce_product_add_to_cart_url');
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
add_shortcode('woocommerce_messages', 'woocommerce_messages_shortcode');
