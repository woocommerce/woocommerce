<?php
/**
 * WooCommerce Emails
 * 
 * Email handling for important shop events.
 *
 * @package		WooCommerce
 * @category	Emails
 * @author		WooThemes
 */

/**
 * Mail from name/email
 **/
function woocommerce_mail_from_name( $name ) {
	return esc_attr(get_bloginfo('name'));
}
function woocommerce_mail_from( $email ) {
	return get_option('admin_email');
}

/**
 * HTML emails from WooCommerce
 **/
function woocommerce_mail( $to, $subject, $message ) {
	
	add_filter( 'wp_mail_from', 'woocommerce_mail_from' );
	add_filter( 'wp_mail_from_name', 'woocommerce_mail_from_name' );
	add_filter( 'wp_mail_content_type', 'woocommerce_email_content_type' );
	
	// Send the mail	
	wp_mail( $to, $subject, $message );
	
	// Unhook
	remove_filter( 'wp_mail_from', 'woocommerce_mail_from' );
	remove_filter( 'wp_mail_from_name', 'woocommerce_mail_from_name' );
	remove_filter( 'wp_mail_content_type', 'woocommerce_email_content_type' );
}

/**
 * Email Header
 **/
add_action('woocommerce_email_header', 'woocommerce_email_header');

function woocommerce_email_header() {
	woocommerce_get_template('emails/email_header.php', false);
}


/**
 * Email Footer
 **/
add_action('woocommerce_email_footer', 'woocommerce_email_footer');

function woocommerce_email_footer() {
	woocommerce_get_template('emails/email_footer.php', false);
}
	
	
/**
 * HTML email type
 **/
function woocommerce_email_content_type($content_type){
	return 'text/html';
}


/**
 * Fix recieve password mail links
 **/
function woocommerce_retrieve_password_message($content){
	return htmlspecialchars($content);
}
	

/**
 * Hooks for emails
 **/
add_action('woocommerce_low_stock_notification', 'woocommerce_low_stock_notification');
add_action('woocommerce_no_stock_notification', 'woocommerce_no_stock_notification');
add_action('woocommerce_product_on_backorder_notification', 'woocommerce_product_on_backorder_notification', 1, 2);
 
 
/**
 * New order notification email template
 **/
add_action('woocommerce_order_status_pending_to_processing', 'woocommerce_new_order_notification');
add_action('woocommerce_order_status_pending_to_completed', 'woocommerce_new_order_notification');
add_action('woocommerce_order_status_pending_to_on-hold', 'woocommerce_new_order_notification');
add_action('woocommerce_order_status_failed_to_processing', 'woocommerce_new_order_notification');
add_action('woocommerce_order_status_failed_to_completed', 'woocommerce_new_order_notification');

function woocommerce_new_order_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$email_heading = __('New Customer Order', 'woothemes');
	
	$subject = sprintf(__('[%s] New Customer Order (# %s)', 'woothemes'), get_bloginfo('name'), $order_id);
	
	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/new_order.php', false);
	
	// Get contents
	$message = ob_get_clean();
	
	// Send the mail	
	woocommerce_mail( get_option('admin_email'), $subject, $message );
}


/**
 * Processing order notification email template
 **/
add_action('woocommerce_order_status_pending_to_processing', 'woocommerce_processing_order_customer_notification');
add_action('woocommerce_order_status_pending_to_on-hold', 'woocommerce_processing_order_customer_notification');
 
function woocommerce_processing_order_customer_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$order = &new woocommerce_order( $order_id );
	
	$email_heading = __('Order Received', 'woothemes');
	
	$subject = '[' . get_bloginfo('name') . '] ' . __('Order Received', 'woothemes');
	
	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/customer_processing_order.php', false);
	
	// Get contents
	$message = ob_get_clean();

	// Send the mail	
	woocommerce_mail( $order->billing_email, $subject, $message );
}


/**
 * Completed order notification email template - this one includes download links for downloadable products
 **/
add_action('woocommerce_order_status_completed', 'woocommerce_completed_order_customer_notification');
 
function woocommerce_completed_order_customer_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$order = &new woocommerce_order( $order_id );
	
	if ($order->has_downloadable_item()) :
		$email_heading = __('Order Complete/Download Links', 'woothemes');
	else :
		$email_heading = __('Order Complete', 'woothemes');
	endif;
	
	$email_heading = apply_filters('woocommerce_completed_order_customer_notification_subject', $email_heading);

	$subject = '[' . get_bloginfo('name') . '] ' . $email_heading;
	
	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/customer_completed_order.php', false);
	
	// Get contents
	$message = ob_get_clean();

	// Send the mail	
	woocommerce_mail( $order->billing_email, $subject, $message );
}


/**
 * Pay for order notification email template - this one includes a payment link
 **/
function woocommerce_pay_for_order_customer_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$order = &new woocommerce_order( $order_id );
	
	$email_heading = __('Pay for Order', 'woothemes');

	$subject = '[' . get_bloginfo('name') . '] ' . __('Pay for Order', 'woothemes');

	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/customer_pay_for_order.php', false);
	
	// Get contents
	$message = ob_get_clean();

	// Send the mail	
	woocommerce_mail( $order->billing_email, $subject, $message );
}


/**
 * Low stock notification email
 **/
function woocommerce_low_stock_notification( $product ) {
	$_product = &new woocommerce_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product low in stock', 'woothemes');
	$message = '#' . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('is low in stock.', 'woothemes');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}


/**
 * No stock notification email
 **/
function woocommerce_no_stock_notification( $product ) {
	$_product = &new woocommerce_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product out of stock', 'woothemes');
	$message = '#' . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('is out of stock.', 'woothemes');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}


/**
 * Backorder notification email
 **/
function woocommerce_product_on_backorder_notification( $product, $amount ) {
	$_product = &new woocommerce_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product Backorder', 'woothemes');
	$message = $amount . __(' units of #', 'woothemes') . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('have been backordered.', 'woothemes');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}