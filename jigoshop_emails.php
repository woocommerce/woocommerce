<?php

/**
 * Hooks for emails
 **/
add_action('jigoshop_low_stock_notification', 'jigoshop_low_stock_notification');
add_action('jigoshop_no_stock_notification', 'jigoshop_no_stock_notification');
add_action('jigoshop_product_on_backorder_notification', 'jigoshop_product_on_backorder_notification', 1, 2);
 
 
/**
 * New order notification email template
 **/
add_action('order_status_pending_to_processing', 'jigoshop_new_order_notification');
add_action('order_status_pending_to_completed', 'jigoshop_new_order_notification');
add_action('order_status_pending_to_on-hold', 'jigoshop_new_order_notification');

function jigoshop_new_order_notification( $order_id ) {
	
	$order = &new jigoshop_order( $order_id );

	$subject = sprintf(__('[%s] New Customer Order (# %s)','jigoshop'), get_bloginfo('name'), $order->id);
			
	$message = __("You have received an order from ",'jigoshop') . $order->billing_first_name . ' ' . $order->billing_last_name . __(". Their order is as follows:",'jigoshop') . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('ORDER #: ','jigoshop') . $order->id . '' . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message 	.= $order->email_order_items_list( false, true );
	
	if ($order->customer_note) :
		$message .= PHP_EOL . __('Note:','jigoshop') .$order->customer_note . PHP_EOL;
	endif;

	$message .= PHP_EOL . __('Subtotal:','jigoshop') . "\t\t\t" . $order->get_subtotal_to_display() . PHP_EOL;
	if ($order->order_shipping > 0) $message .= __('Shipping:','jigoshop') . "\t\t\t" . $order->get_shipping_to_display() . PHP_EOL;
	if ($order->order_discount > 0) $message .= __('Discount:','jigoshop') . "\t\t\t" . jigoshop_price($order->order_discount) . PHP_EOL;
	if ($order->get_total_tax() > 0) $message .= __('Tax:','jigoshop') . "\t\t\t\t\t" . jigoshop_price($order->get_total_tax()) . PHP_EOL;
	$message .= __('Total:','jigoshop') . "\t\t\t\t" . jigoshop_price($order->order_total) . ' - via ' . ucwords($order->payment_method) . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('CUSTOMER DETAILS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	if ($order->billing_email) $message .= __('Email:','jigoshop') . "\t\t\t\t" . $order->billing_email . PHP_EOL;
	if ($order->billing_phone) $message .= __('Tel:','jigoshop') . "\t\t\t\t\t" . $order->billing_phone . PHP_EOL;
	
	$message .= PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('BILLING ADDRESS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message .= $order->billing_first_name . ' ' . $order->billing_last_name . PHP_EOL;
	if ($order->billing_company) $message .= $order->billing_company . PHP_EOL;
	$message .= $order->formatted_billing_address . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('SHIPPING ADDRESS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message .= $order->shipping_first_name . ' ' . $order->shipping_last_name . PHP_EOL;
	if ($order->shipping_company) $message .= $order->shipping_company . PHP_EOL;
	$message .= $order->formatted_shipping_address . PHP_EOL . PHP_EOL;
	
	$message = html_entity_decode( strip_tags( $message ) );
	
	wp_mail( get_option('admin_email'), $subject, $message );
}


/**
 * Processing order notification email template
 **/
add_action('order_status_pending_to_processing', 'jigoshop_processing_order_customer_notification');
add_action('order_status_pending_to_on-hold', 'jigoshop_processing_order_customer_notification');
 
function jigoshop_processing_order_customer_notification( $order_id ) {
	
	$order = &new jigoshop_order( $order_id );

	$subject = '[' . get_bloginfo('name') . '] ' . __('Order Received','jigoshop');
	
	$message 	 = __("Thank you, we are now processing your order. Your order's details are below:",'jigoshop') . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message 	.= __('ORDER #: ','jigoshop') . $order->id . '' . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message 	.= $order->email_order_items_list();
	
	if ($order->customer_note) :
		$message .= PHP_EOL . __('Note:','jigoshop') .$order->customer_note . PHP_EOL;
	endif;

	$message .= PHP_EOL . __('Subtotal:','jigoshop') . "\t\t\t" . $order->get_subtotal_to_display() . PHP_EOL;
	if ($order->order_shipping > 0) $message .= __('Shipping:','jigoshop') . "\t\t\t" . $order->get_shipping_to_display() . PHP_EOL;
	if ($order->order_discount > 0) $message .= __('Discount:','jigoshop') . "\t\t\t" . jigoshop_price($order->order_discount) . PHP_EOL;
	if ($order->get_total_tax() > 0) $message .= __('Tax:','jigoshop') . "\t\t\t\t\t" . jigoshop_price($order->get_total_tax()) . PHP_EOL;
	$message .= __('Total:','jigoshop') . "\t\t\t\t" . jigoshop_price($order->order_total) . ' - via ' . ucwords($order->payment_method) . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('CUSTOMER DETAILS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	if ($order->billing_email) $message .= __('Email:','jigoshop') . "\t\t\t\t" . $order->billing_email . PHP_EOL;
	if ($order->billing_phone) $message .= __('Tel:','jigoshop') . "\t\t\t\t\t" . $order->billing_phone . PHP_EOL;
	
	$message .= PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('BILLING ADDRESS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message .= $order->billing_first_name . ' ' . $order->billing_last_name . PHP_EOL;
	if ($order->billing_company) $message .= $order->billing_company . PHP_EOL;
	$message .= $order->formatted_billing_address . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('SHIPPING ADDRESS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message .= $order->shipping_first_name . ' ' . $order->shipping_last_name . PHP_EOL;
	if ($order->shipping_company) $message .= $order->shipping_company . PHP_EOL;
	$message .= $order->formatted_shipping_address . PHP_EOL . PHP_EOL;
	
	$message = html_entity_decode( strip_tags( $message ) );

	wp_mail( $order->billing_email, $subject, $message );
}


/**
 * Completed order notification email template - this one includes download links for downloadable products
 **/
add_action('order_status_completed', 'jigoshop_completed_order_customer_notification');
 
function jigoshop_completed_order_customer_notification( $order_id ) {
	
	$order = &new jigoshop_order( $order_id );

	$subject = '[' . get_bloginfo('name') . '] ' . __('Order Complete','jigoshop');
	
	$message 	 = __("Your order is complete. Your order's details are below:",'jigoshop') . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message 	.= __('ORDER #: ','jigoshop') . $order->id . '' . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message 	.= $order->email_order_items_list( true );
	
	if ($order->customer_note) :
		$message .= PHP_EOL . __('Note:','jigoshop') .$order->customer_note . PHP_EOL;
	endif;

	$message .= PHP_EOL . __('Subtotal:','jigoshop') . "\t\t\t" . $order->get_subtotal_to_display() . PHP_EOL;
	if ($order->order_shipping > 0) $message .= __('Shipping:','jigoshop') . "\t\t\t" . $order->get_shipping_to_display() . PHP_EOL;
	if ($order->order_discount > 0) $message .= __('Discount:','jigoshop') . "\t\t\t" . jigoshop_price($order->order_discount) . PHP_EOL;
	if ($order->get_total_tax() > 0) $message .= __('Tax:','jigoshop') . "\t\t\t\t\t" . jigoshop_price($order->get_total_tax()) . PHP_EOL;
	$message .= __('Total:','jigoshop') . "\t\t\t\t" . jigoshop_price($order->order_total) . ' - via ' . ucwords($order->payment_method) . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('CUSTOMER DETAILS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	if ($order->billing_email) $message .= __('Email:','jigoshop') . "\t\t\t\t" . $order->billing_email . PHP_EOL;
	if ($order->billing_phone) $message .= __('Tel:','jigoshop') . "\t\t\t\t\t" . $order->billing_phone . PHP_EOL;
	
	$message .= PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('BILLING ADDRESS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message .= $order->billing_first_name . ' ' . $order->billing_last_name . PHP_EOL;
	if ($order->billing_company) $message .= $order->billing_company . PHP_EOL;
	$message .= $order->formatted_billing_address . PHP_EOL . PHP_EOL;
	
	$message 	.= '=====================================================================' . PHP_EOL;
	$message .= __('SHIPPING ADDRESS','jigoshop') . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message .= $order->shipping_first_name . ' ' . $order->shipping_last_name . PHP_EOL;
	if ($order->shipping_company) $message .= $order->shipping_company . PHP_EOL;
	$message .= $order->formatted_shipping_address . PHP_EOL . PHP_EOL;
	
	$message = html_entity_decode( strip_tags( $message ) );

	wp_mail( $order->billing_email, $subject, $message );
}


/**
 * Pay for order notification email template - this one includes a payment link
 **/
function jigoshop_pay_for_order_customer_notification( $order_id ) {
	
	$order = &new jigoshop_order( $order_id );

	$subject = '[' . get_bloginfo('name') . '] ' . __('Pay for Order','jigoshop');
	
	$customer_message = sprintf( __("An order has been created for you on &ldquo;%s&rdquo;. To pay for this order please use the following link: %s",'jigoshop') . PHP_EOL . PHP_EOL, get_bloginfo('name'), $order->get_checkout_payment_url() );
	
	$message 	 = '=====================================================================' . PHP_EOL;
	$message 	.= __('ORDER #: ','jigoshop') . $order->id . '' . PHP_EOL;
	$message 	.= '=====================================================================' . PHP_EOL;
	
	$message 	.= $order->email_order_items_list();
	
	if ($order->customer_note) :
		$message .= PHP_EOL . __('Note:','jigoshop') .$order->customer_note . PHP_EOL;
	endif;

	$message .= PHP_EOL . __('Subtotal:','jigoshop') . "\t\t\t" . $order->get_subtotal_to_display() . PHP_EOL;
	if ($order->order_shipping > 0) $message .= __('Shipping:','jigoshop') . "\t\t\t" . $order->get_shipping_to_display() . PHP_EOL;
	if ($order->order_discount > 0) $message .= __('Discount:','jigoshop') . "\t\t\t" . jigoshop_price($order->order_discount) . PHP_EOL;
	if ($order->get_total_tax() > 0) $message .= __('Tax:','jigoshop') . "\t\t\t\t\t" . jigoshop_price($order->get_total_tax()) . PHP_EOL;
	$message .= __('Total:','jigoshop') . "\t\t\t\t" . jigoshop_price($order->order_total) . ' - via ' . ucwords($order->payment_method) . PHP_EOL . PHP_EOL;
	
	$customer_message = html_entity_decode( strip_tags( $customer_message.$message ) );

	wp_mail( $order->billing_email, $subject, $customer_message );
}


/**
 * Low stock notification email
 **/
function jigoshop_low_stock_notification( $product ) {
	$_product = &new jigoshop_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product low in stock','jigoshop');
	$message = '#' . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('is low in stock.', 'jigoshop');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}


/**
 * No stock notification email
 **/
function jigoshop_no_stock_notification( $product ) {
	$_product = &new jigoshop_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product out of stock','jigoshop');
	$message = '#' . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('is out of stock.', 'jigoshop');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}


/**
 * Backorder notification email
 **/
function jigoshop_product_on_backorder_notification( $product, $amount ) {
	$_product = &new jigoshop_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product Backorder','jigoshop');
	$message = $amount . __(' units of #', 'jigoshop') . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('have been backordered.', 'jigoshop');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}