<?php

function get_jigoshop_checkout( $atts ) {
	return jigoshop::shortcode_wrapper('jigoshop_checkout', $atts);
}

function jigoshop_checkout( $atts ) {

	if (!defined('JIGOSHOP_CHECKOUT')) define('JIGOSHOP_CHECKOUT', true);
	
	if (sizeof(jigoshop_cart::$cart_contents)==0) :
		wp_redirect(get_permalink(get_option('jigoshop_cart_page_id')));
		exit;
	endif;
	
	$non_js_checkout = (isset($_POST['update_totals']) && $_POST['update_totals']) ? true : false;
	
	$_checkout = jigoshop_checkout::instance();
	
	$_checkout->process_checkout();
	
	$result = jigoshop_cart::check_cart_item_stock();
	
	if (is_wp_error($result)) jigoshop::add_error( $result->get_error_message() );
	
	if ( jigoshop::error_count()==0 && $non_js_checkout) jigoshop::add_message( __('The order totals have been updated. Please confirm your order by pressing the Place Order button at the bottom of the page.', 'jigoshop') );
	
	jigoshop::show_messages();
	
	jigoshop_get_template('checkout/form.php', false);
	
}