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
 * Front page archive/shop template applied to main loop
 */
if (!function_exists('woocommerce_front_page_archive')) {
	function woocommerce_front_page_archive( $query ) {
			
		global $paged, $woocommerce, $wp_the_query, $wp_query;
		
		if ( defined('SHOP_IS_ON_FRONT') ) :
		
			wp_reset_query();
			
			// Only apply to front_page
			if ( $query === $wp_the_query ) :
				
				if (get_query_var('paged')) :
					$paged = get_query_var('paged'); 
				else :
					$paged = (get_query_var('page')) ? get_query_var('page') : 1;
				endif;
	
				// Filter the query
				add_filter( 'parse_query', array( &$woocommerce->query, 'parse_query') );
				
				// Query the products
				$wp_query->query( array( 'page_id' => '', 'p' => '', 'post_type' => 'product', 'paged' => $paged ) );
				
				// get products in view (for use by widgets)
				$woocommerce->query->get_products_in_view();
				
				// Remove the query manipulation
				remove_filter( 'parse_query', array( &$woocommerce->query, 'parse_query') ); 
				remove_action('loop_start', 'woocommerce_front_page_archive', 1);
	
			endif;
		
		endif;
	}
}
add_action('loop_start', 'woocommerce_front_page_archive', 1);

/**
 * Detect frontpage shop and fix pagination on static front page
 **/
function woocommerce_front_page_archive_paging_fix() {
		
	if ( is_front_page() && is_page( get_option('woocommerce_shop_page_id') )) :
		
		if (get_query_var('paged')) :
			$paged = get_query_var('paged'); 
		else :
			$paged = (get_query_var('page')) ? get_query_var('page') : 1;
		endif;
			
		query_posts( array( 'page_id' => get_option('woocommerce_shop_page_id'), 'is_paged' => true, 'paged' => $paged ) );
		
		define('SHOP_IS_ON_FRONT', true);
		
	endif;
}
add_action('wp', 'woocommerce_front_page_archive_paging_fix', 1);

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
	
	if (is_woocommerce() || is_checkout() || is_cart() || is_account_page() || get_page(get_option('woocommerce_order_tracking_page_id')) || get_page(get_option('woocommerce_thanks_page_id'))) $woocommerce_body_classes[] = 'woocommerce-page';
	
}
add_action('wp_head', 'woocommerce_page_body_classes');

function woocommerce_body_class($classes) {
	
	global $woocommerce_body_classes;
	
	$woocommerce_body_classes = (array) $woocommerce_body_classes;
	
	$classes = array_merge($classes, $woocommerce_body_classes);
	
	return $classes;
}
add_filter('body_class','woocommerce_body_class');

/**
 * Fix active class in nav for shop page
 **/
function woocommerce_nav_menu_item_classes( $menu_items, $args ) {
	
	if (!is_woocommerce()) return $menu_items;
	
	$shop_page 		= (int) get_option('woocommerce_shop_page_id');
	$page_for_posts = (int) get_option( 'page_for_posts' );

	foreach ( (array) $menu_items as $key => $menu_item ) :

		$classes = (array) $menu_item->classes;

		// Unset active class for blog page
		if ( $page_for_posts == $menu_item->object_id ) :
			$menu_items[$key]->current = false;
			unset( $classes[ array_search('current_page_parent', $classes) ] );
			unset( $classes[ array_search('current-menu-item', $classes) ] );

		// Set active state if this is the shop page link
		elseif ( is_shop() && $shop_page == $menu_item->object_id ) :
			$menu_items[$key]->current = true;
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';
		
		endif;

		$menu_items[$key]->classes = array_unique( $classes );
	
	endforeach;

	return $menu_items;
}
add_filter( 'wp_nav_menu_objects',  'woocommerce_nav_menu_item_classes', 2, 20 );

/**
 * Fix active class in wp_list_pages for shop page
 *
 * Suggested by jessor - https://github.com/woothemes/woocommerce/issues/177
 **/
function woocommerce_list_pages($pages){
    global $post;

    if (is_woocommerce() || is_cart() || is_checkout() || is_page(get_option('woocommerce_thanks_page_id'))) {
        $pages = str_replace( 'current_page_parent', '', $pages); // remove current_page_parent class from any item
        $shop_page = 'page-item-' . get_option('woocommerce_shop_page_id'); // find shop_page_id through woocommerce options
        
        if (is_shop()) :
        	$pages = str_replace($shop_page, $shop_page . ' current_page_item', $pages); // add current_page_item class to shop page
    	else :
    		$pages = str_replace($shop_page, $shop_page . ' current_page_parent', $pages); // add current_page_parent class to shop page
    	endif;
    }
    return $pages;
}

add_filter('wp_list_pages', 'woocommerce_list_pages');

/**
 * Add logout link to my account menu
 **/
add_filter( 'wp_nav_menu_items', 'woocommerce_nav_menu_items', 10, 2 );

function woocommerce_nav_menu_items( $items, $args ) {
	
	if ( get_option('woocommerce_menu_logout_link')=='yes' && strstr($items, get_permalink(get_option('woocommerce_myaccount_page_id'))) && is_user_logged_in() ) :
		$items .= '<li><a href="'. wp_logout_url() .'">'.__('Logout', 'woothemes').'</a></li>';
	endif;
	
    return $items;
}
