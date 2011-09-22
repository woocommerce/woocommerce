<?php
/**
 * WooCommerce Templates
 * 
 * Handles template usage so that we can use our own templates instead of the theme's.
 *
 * Templates are in the 'templates' folder. woocommerce looks for theme 
 * overides in /theme/woocommerce/ by default  but this can be overwritten with WOOCOMMERCE_TEMPLATE_URL
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */
function woocommerce_template_loader( $template ) {
	global $woocommerce;
	
	if ( is_single() && get_post_type() == 'product' ) {
		
		$template = locate_template( array( 'single-product.php', WOOCOMMERCE_TEMPLATE_URL . 'single-product.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/single-product.php';
		
	}
	elseif ( is_tax('product_cat') ) {
		
		$template = locate_template(  array( 'taxonomy-product_cat.php', WOOCOMMERCE_TEMPLATE_URL . 'taxonomy-product_cat.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/taxonomy-product_cat.php';
	}
	elseif ( is_tax('product_tag') ) {
		
		$template = locate_template( array( 'taxonomy-product_tag.php', WOOCOMMERCE_TEMPLATE_URL . 'taxonomy-product_tag.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/taxonomy-product_tag.php';
	}
	elseif ( is_post_type_archive('product') ||  is_page( get_option('woocommerce_shop_page_id') )) {

		$template = locate_template( array( 'archive-product.php', WOOCOMMERCE_TEMPLATE_URL . 'archive-product.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/archive-product.php';
		
	}
	
	return $template;

}
add_filter( 'template_include', 'woocommerce_template_loader' );

/**
 * Get template part (for templates like loop)
 */
function woocommerce_get_template_part( $slug, $name = '' ) {
	global $woocommerce;
	if ($name=='shop') :
		if (!locate_template(array( 'loop-shop.php', WOOCOMMERCE_TEMPLATE_URL . 'loop-shop.php' ))) :
			load_template( $woocommerce->plugin_path() . '/templates/loop-shop.php',false );
			return;
		endif;
	endif;
	get_template_part( WOOCOMMERCE_TEMPLATE_URL . $slug, $name );
}

/**
 * Get the reviews template (comments)
 */
function woocommerce_comments_template($template) {
	global $woocommerce;
		
	if(get_post_type() !== 'product') return $template;
	
	if (file_exists( STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . 'single-product-reviews.php' ))
		return STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . 'single-product-reviews.php'; 
	else
		return $woocommerce->plugin_path() . '/templates/single-product-reviews.php';
}

add_filter('comments_template', 'woocommerce_comments_template' );


/**
 * Get other templates (e.g. product attributes)
 */
function woocommerce_get_template($template_name, $require_once = true) {
	global $woocommerce;
	if (file_exists( STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . $template_name )) load_template( STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . $template_name, $require_once ); 
	elseif (file_exists( STYLESHEETPATH . '/' . $template_name )) load_template( STYLESHEETPATH . '/' . $template_name , $require_once); 
	else load_template( $woocommerce->plugin_path() . '/templates/' . $template_name , $require_once);
}

/**
 * Get other templates (e.g. product attributes) - path
 */
function woocommerce_get_template_file_url($template_name, $ssl = false) {
	global $woocommerce;
	if (file_exists( STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . $template_name )) 
		$return = get_bloginfo('template_url') . '/' . WOOCOMMERCE_TEMPLATE_URL . $template_name; 
	elseif (file_exists( STYLESHEETPATH . '/' . $template_name )) 
		$return = get_bloginfo('template_url') . '/' . $template_name; 
	else 
		$return = $woocommerce->plugin_url() . '/templates/' . $template_name;
	
	if (get_option('woocommerce_force_ssl_checkout')=='yes' || is_ssl()) :
		if ($ssl) $return = str_replace('http:', 'https:', $return);
	endif;
	
	return $return;
}


/**
 * Front page archive/shop template
 */
if (!function_exists('woocommerce_front_page_archive')) {
	function woocommerce_front_page_archive() {
			
		global $paged;
		
		if ( is_front_page() && is_page( get_option('woocommerce_shop_page_id') )) :
			
			if ( get_query_var('paged') ) {
			    $paged = get_query_var('paged');
			} else if ( get_query_var('page') ) {
			    $paged = get_query_var('page');
			} else {
			    $paged = 1;
			}
			
			add_filter( 'parse_query', 'woocommerce_parse_query' ); 
			
			query_posts( array( 'page_id' => '', 'post_type' => 'product', 'paged' => $paged ) );
			
			define('SHOP_IS_ON_FRONT', true);

		endif;
	}
}
add_action('wp', 'woocommerce_front_page_archive', 1);


/**
 * Add Body classes based on page/template
 **/
global $woocommerce_body_classes;

function woocommerce_page_body_classes() {
	
	global $woocommerce_body_classes;
	
	$woocommerce_body_classes = (array) $woocommerce_body_classes;
	
	$woocommerce_body_classes[] = 'theme-' . strtolower( get_current_theme() );
	
	if (is_woocommerce()) $woocommerce_body_classes[] = 'woocommerce';
	
	if (is_checkout()) $woocommerce_body_classes[] = 'woocommerce-checkout';
	
	if (is_cart()) $woocommerce_body_classes[] = 'woocommerce-cart';
	
	if (is_account_page()) $woocommerce_body_classes[] = 'woocommerce-account';
}
add_action('wp_head', 'woocommerce_page_body_classes');

function woocommerce_body_class($classes) {
	
	global $woocommerce_body_classes;
	
	$woocommerce_body_classes = (array) $woocommerce_body_classes;
	
	$classes = array_merge($classes, $woocommerce_body_classes);
	
	return $classes;
}
add_filter('body_class','woocommerce_body_class');
