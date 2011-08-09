<?php

### Templates ##################################################################
/*
 * Templates are in the 'templates' folder. jigoshop looks for theme 
 * overides in /theme/jigoshop/ by default  but this can be overwritten with JIGOSHOP_TEMPLATE_URL
*/
################################################################################

function jigoshop_template_loader( $template ) {
	
	if ( is_single() && get_post_type() == 'product' ) {
		
		jigoshop_add_body_class( array( 'jigoshop', 'jigoshop-product' ) );
		
		$template = locate_template( array( 'single-product.php', JIGOSHOP_TEMPLATE_URL . 'single-product.php' ) );
		
		if ( ! $template ) $template = jigoshop::plugin_path() . '/templates/single-product.php';
		
	}
	elseif ( is_tax('product_cat') ) {
		
		jigoshop_add_body_class( array( 'jigoshop', 'jigoshop-product_cat' ) );
		
		$template = locate_template(  array( 'taxonomy-product_cat.php', JIGOSHOP_TEMPLATE_URL . 'taxonomy-product_cat.php' ) );
		
		if ( ! $template ) $template = jigoshop::plugin_path() . '/templates/taxonomy-product_cat.php';
	}
	elseif ( is_tax('product_tag') ) {
		
		jigoshop_add_body_class( array( 'jigoshop', 'jigoshop-product_tag' ) );
		
		$template = locate_template( array( 'taxonomy-product_tag.php', JIGOSHOP_TEMPLATE_URL . 'taxonomy-product_tag.php' ) );
		
		if ( ! $template ) $template = jigoshop::plugin_path() . '/templates/taxonomy-product_tag.php';
	}
	elseif ( is_post_type_archive('product') ||  is_page( get_option('jigoshop_shop_page_id') )) {

		jigoshop_add_body_class( array( 'jigoshop', 'jigoshop-products' ) );
		
		$template = locate_template( array( 'archive-product.php', JIGOSHOP_TEMPLATE_URL . 'archive-product.php' ) );
		
		if ( ! $template ) $template = jigoshop::plugin_path() . '/templates/archive-product.php';
		
	}
	
	return $template;

}
add_filter( 'template_include', 'jigoshop_template_loader' );

################################################################################
// Get template part (for templates like loop)
################################################################################

function jigoshop_get_template_part( $slug, $name = '' ) {
	if ($name=='shop') :
		if (!locate_template(array( 'loop-shop.php', JIGOSHOP_TEMPLATE_URL . 'loop-shop.php' ))) :
			load_template( jigoshop::plugin_path() . '/templates/loop-shop.php',false );
			return;
		endif;
	endif;
	get_template_part( JIGOSHOP_TEMPLATE_URL . $slug, $name );
}

################################################################################
// Get the reviews template (comments)
################################################################################

function jigoshop_comments_template($template) {
		
	if(get_post_type() !== 'product') return $template;
	
	if (file_exists( STYLESHEETPATH . '/' . JIGOSHOP_TEMPLATE_URL . 'single-product-reviews.php' ))
		return STYLESHEETPATH . '/' . JIGOSHOP_TEMPLATE_URL . 'single-product-reviews.php'; 
	else
		return jigoshop::plugin_path() . '/templates/single-product-reviews.php';
}

add_filter('comments_template', 'jigoshop_comments_template' );


################################################################################
// Get other templates (e.g. product attributes)
################################################################################

function jigoshop_get_template($template_name, $require_once = true) {
	if (file_exists( STYLESHEETPATH . '/' . JIGOSHOP_TEMPLATE_URL . $template_name )) load_template( STYLESHEETPATH . '/' . JIGOSHOP_TEMPLATE_URL . $template_name, $require_once ); 
	elseif (file_exists( STYLESHEETPATH . '/' . $template_name )) load_template( STYLESHEETPATH . '/' . $template_name , $require_once); 
	else load_template( jigoshop::plugin_path() . '/templates/' . $template_name , $require_once);
}

################################################################################
// Get other templates (e.g. product attributes) - path
################################################################################

function jigoshop_get_template_file_url($template_name, $ssl = false) {
	if (file_exists( STYLESHEETPATH . '/' . JIGOSHOP_TEMPLATE_URL . $template_name )) 
		$return = get_bloginfo('template_url') . '/' . JIGOSHOP_TEMPLATE_URL . $template_name; 
	elseif (file_exists( STYLESHEETPATH . '/' . $template_name )) 
		$return = get_bloginfo('template_url') . '/' . $template_name; 
	else 
		$return = jigoshop::plugin_url() . '/templates/' . $template_name;
	
	if (get_option('jigoshop_force_ssl_checkout')=='yes' || is_ssl()) :
		if ($ssl) $return = str_replace('http:', 'https:', $return);
	endif;
	
	return $return;
}
