<?php
/**
 * Functions used for custom post types in admin 
 *
 * These functions control columns in admin, and other admin interface bits 
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
 
/**
 * Columns for Coupons page
 **/
add_filter('manage_edit-shop_coupon_columns', 'woocommerce_edit_coupon_columns');

function woocommerce_edit_coupon_columns($columns){
	
	$columns = array();
	
	$columns["cb"] 			= "<input type=\"checkbox\" />";
	$columns["title"] 		= __("Code", 'woothemes');
	$columns["type"] 		= __("Coupon type", 'woothemes');
	$columns["amount"] 		= __("Coupon amount", 'woothemes');
	$columns["products"]	= __("Product IDs", 'woothemes');
	$columns["usage_limit"] = __("Usage limit", 'woothemes');
	$columns["usage_count"] = __("Usage count", 'woothemes');

	return $columns;
}


/**
 * Custom Columns for Coupons page
 **/
add_action('manage_shop_coupon_posts_custom_column', 'woocommerce_custom_coupon_columns', 2);

function woocommerce_custom_coupon_columns($column) {
	global $post;
	
	$type 			= get_post_meta($post->ID, 'discount_type', true);
	$amount 		= get_post_meta($post->ID, 'coupon_amount', true);
	$individual_use = get_post_meta($post->ID, 'individual_use', true);
	$product_ids 	= (get_post_meta($post->ID, 'product_ids', true)) ? explode(',', get_post_meta($post->ID, 'product_ids', true)) : array();
	$usage_limit 	= get_post_meta($post->ID, 'usage_limit', true);
	$usage_count 	= (int) get_post_meta($post->ID, 'usage_count', true);

	switch ($column) {
		case "type" :
			echo $type;
		break;
		case "amount" :
			echo $amount;
		break;
		case "products" :
			if (sizeof($product_ids)>0) echo implode(', ', $product_ids); else echo '&ndash;';
		break;
		case "usage_limit" :
			if ($usage_limit) echo $usage_limit; else echo '&ndash;';
		break;
		case "usage_count" :
			echo $usage_count;
		break;
	}
}


/**
 * Columns for Variations page
 **/
add_filter('manage_edit-product_variation_columns', 'woocommerce_edit_variation_columns');

function woocommerce_edit_variation_columns($columns){
	
	$columns = array();
	
	$columns["cb"] = "<input type=\"checkbox\" />";
	$columns["thumb"] = __("Thumb", 'woothemes');
	$columns["id"] = __("ID", 'woothemes');
	$columns["title"] = __("Name", 'woothemes');
	$columns["parent"] = __("Parent Product", 'woothemes');

	return $columns;
}


/**
 * Custom Columns for Variations page
 **/
add_action('manage_product_variation_posts_custom_column', 'woocommerce_custom_variation_columns', 2);

function woocommerce_custom_variation_columns($column) {
	global $post;
	$product = &new woocommerce_product($post->ID);

	switch ($column) {
		case "thumb" :
			if (has_post_thumbnail($post->ID)) :
				echo get_the_post_thumbnail($post->ID, 'shop_thumbnail');
			endif;
		break;
		case "id" :
			echo '#'.$post->ID;
		break;
		case "parent" :
			if ($post->post_parent) :
				$parent = get_post( $post->post_parent );
				echo '#'.$parent->ID.' &mdash; <a href="'.admin_url('post.php?post='.$parent->ID.'&action=edit').'">'.$parent->post_title.'</a>';
			endif;
		break;
	}
}


/**
 * Columns for Products page
 **/
add_filter('manage_edit-product_columns', 'woocommerce_edit_product_columns');

function woocommerce_edit_product_columns($columns){
	
	$columns = array();
	
	$columns["cb"] = "<input type=\"checkbox\" />";
	$columns["thumb"] = __("Image", 'woothemes');
	$columns["title"] = __("Name", 'woothemes');
	$columns["product_type"] = __("Type", 'woothemes');
	$columns["sku"] = __("ID/SKU", 'woothemes');
	$columns["product_cat"] = __("Category", 'woothemes');
	$columns["product_tags"] = __("Tags", 'woothemes');
	$columns["visibility"] = __("Visibility", 'woothemes');
	$columns["featured"] = __("Featured", 'woothemes');
	
	if (get_option('woocommerce_manage_stock')=='yes') :
		$columns["is_in_stock"] = __("In Stock?", 'woothemes');
		$columns["inventory"] = __("Inventory", 'woothemes');
	endif;
	
	$columns["price"] = __("Price", 'woothemes');
	$columns["date"] = __("Date", 'woothemes');
	
	return $columns;
}


/**
 * Custom Columns for Products page
 **/
add_action('manage_product_posts_custom_column', 'woocommerce_custom_product_columns', 2);

function woocommerce_custom_product_columns($column) {
	global $post, $woocommerce;
	$product = &new woocommerce_product($post->ID);

	switch ($column) {
		case "thumb" :
			if (has_post_thumbnail($post->ID)) :
				echo get_the_post_thumbnail($post->ID, 'shop_thumbnail');
			endif;
		break;
		case "summary" :
			echo $post->post_excerpt;
		break;
		case "price":
			echo $product->get_price_html();	
		break;
		case "product_cat" :
			echo get_the_term_list($post->ID, 'product_cat', '', ', ','');
		break;
		case "product_tags" :
			echo get_the_term_list($post->ID, 'product_tag', '', ', ','');
		break;
		case "sku" :
			if ( $sku = get_post_meta( $post->ID, 'sku', true )) :
				echo '#'.$post->ID.' - SKU: ' . $sku;	
			else :
				echo '#'.$post->ID;
			endif;
		break;
		case "featured" :
			$url = wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-feature-product&product_id=' . $post->ID) );
			echo '<a href="'.$url.'" title="'.__('Change', 'woothemes') .'">';
			if ($product->is_featured()) echo '<a href="'.$url.'"><img src="'.$woocommerce->plugin_url().'/assets/images/success.gif" alt="yes" />';
			else echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success-off.gif" alt="no" />';
			echo '</a>';
		break;
		case "visibility" :
			if ( $this_data = $product->visibility ) :
				echo $this_data;	
			else :
				echo '<span class="na">&ndash;</span>';
			endif;
		break;
		case "is_in_stock" :
			if ( !$product->is_type( 'grouped' ) && $product->is_in_stock() ) echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success.gif" alt="yes" />';
			else echo '<span class="na">&ndash;</span>';
		break;
		case "inventory" :
			if ( $product->managing_stock() ) :
				echo $product->stock.' in stock';	
			else :
				echo '<span class="na">&ndash;</span>';
			endif;
		break;
		case "product_type" :
			echo ucwords($product->product_type);
		break;
	}
}


/**
 * Make product columns sortable
 * https://gist.github.com/906872
 **/
add_filter("manage_edit-product_sortable_columns", 'woocommerce_custom_product_sort');

function woocommerce_custom_product_sort($columns) {
	$custom = array(
		'inventory' 	=> 'inventory',
		'price'			=> 'price',
		'featured'		=> 'featured',
		'sku'			=> 'sku'
	);
	return wp_parse_args($custom, $columns);
}


/**
 * Product column orderby
 * http://scribu.net/wordpress/custom-sortable-columns.html#comment-4732
 **/
add_filter( 'request', 'woocommerce_custom_product_orderby' );

function woocommerce_custom_product_orderby( $vars ) {
	if (isset( $vars['orderby'] )) :
		if ( 'inventory' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> 'stock',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'price' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> 'price',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'featured' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> 'featured',
				'orderby' 	=> 'meta_value'
			) );
		endif;
		if ( 'sku' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'orderby' 	=> 'id'
			) );
		endif;
	endif;
	
	return $vars;
}

/**
 * Filter products by category
 **/
add_action('restrict_manage_posts','woocommerce_products_by_category');

function woocommerce_products_by_category() {
    global $typenow, $wp_query;
    if ($typenow=='product') {

			$terms = get_terms('product_cat');
			$output = "<select name='product_cat' id='dropdown_product_cat'>";
			$output .= '<option value="">'.__('Show all categories', 'woothemes').'</option>';
			foreach($terms as $term){
				$output .="<option value='$term->slug' ".selected($term->slug, $wp_query->query['product_cat'], false).">$term->name ($term->count)</option>";
			}
			$output .="</select>";
			echo $output;

    }
}


/**
 * Columns for order page
 **/
add_filter('manage_edit-shop_order_columns', 'woocommerce_edit_order_columns');

function woocommerce_edit_order_columns($columns){
	
	$columns = array();
	
	$columns["cb"] = "<input type=\"checkbox\" />";
	$columns["order_status"] = __("Status", 'woothemes');
	$columns["order_title"] = __("Order", 'woothemes');
	$columns["customer"] = __("Customer", 'woothemes');
	$columns["billing_address"] = __("Billing", 'woothemes');
	$columns["shipping_address"] = __("Shipping", 'woothemes');
	$columns["total_cost"] = __("Order Total", 'woothemes');
	
	return $columns;
}


/**
 * Custom Columns for order page
 **/
add_action('manage_shop_order_posts_custom_column', 'woocommerce_custom_order_columns', 2);

function woocommerce_custom_order_columns($column) {

	global $post;
	$order = &new woocommerce_order( $post->ID );
	
	switch ($column) {
		case "order_status" :
			
			echo sprintf( __('<mark class="%s">%s</mark>', 'woothemes'), sanitize_title($order->status), $order->status );
			
		break;
		case "order_title" :
			
			echo '<a href="'.admin_url('post.php?post='.$post->ID.'&action=edit').'">'.sprintf( __('Order #%s', 'woothemes'), $post->ID ).'</a> ';
			
			echo '<time title="'.date_i18n('c', strtotime($post->post_date)).'">'.date_i18n('F j, Y, g:i a', strtotime($post->post_date)).'</time>';
			
		break;
		case "customer" :
		
			if ($order->user_id) $user_info = get_userdata($order->user_id);
			
			if (isset($user_info) && $user_info) : 
			                    	
            	echo '<a href="user-edit.php?user_id='.$user_info->ID.'">';
            	
            	if ($user_info->first_name || $user_info->last_name) echo $user_info->first_name.' '.$user_info->last_name;
            	else echo $user_info->display_name;
            	
            	echo '</a>';

           	else : 
           		_e('Guest', 'woothemes');
           	endif;
           	
           	if ($order->billing_email) :
        		echo '<small class="meta">'.__('Email: ', 'woothemes').'<a href="mailto:'.$order->billing_email.'">'.$order->billing_email.'</a></small>';
        	endif;
        	if ($order->billing_phone) :
        		echo '<small class="meta">'.__('Tel: ', 'woothemes'). $order->billing_phone . '</small>';
        	endif;
		
		break;
		case "billing_address" :
			echo '<strong>'.$order->billing_first_name . ' ' . $order->billing_last_name;
        	if ($order->billing_company) echo ', '.$order->billing_company;
        	echo '</strong><br/>';
        	echo '<a target="_blank" href="http://maps.google.co.uk/maps?&q='.urlencode($order->formatted_billing_address).'&z=16">'.$order->formatted_billing_address.'</a>';
        	
        	if ($order->payment_method) :
        		echo '<small class="meta">' . __('Paid via ', 'woo themes') . $order->payment_method . '</small>';
        	endif;
        	
		break;
		case "shipping_address" :
			if ($order->formatted_shipping_address) :
            	echo '<strong>'.$order->shipping_first_name . ' ' . $order->shipping_last_name;
            	if ($order->shipping_company) : echo ', '.$order->shipping_company; endif;
            	echo '</strong><br/>';
            	echo '<a target="_blank" href="http://maps.google.co.uk/maps?&q='.urlencode($order->formatted_shipping_address).'&z=16">'.$order->formatted_shipping_address.'</a>';
        	else :
        		echo '&ndash;';
        	endif;
        	
        	if ($order->shipping_method) :
        		echo '<small class="meta">' . __('Shipped via ', 'woothemes') . $order->shipping_method . '</small>';
        	endif;
		break;
		case "total_cost" :
			echo woocommerce_price($order->order_total);
		break;
	}
}


/**
 * Order page filters
 **/
add_filter('views_edit-shop_order', 'woocommerce_custom_order_views');

function woocommerce_custom_order_views( $views ) {
	
	$woocommerce_orders = &new woocommerce_orders();
	
	$pending = (isset($_GET['shop_order_status']) && $_GET['shop_order_status']=='pending') ? 'current' : '';
	$onhold = (isset($_GET['shop_order_status']) && $_GET['shop_order_status']=='on-hold') ? 'current' : '';
	$processing = (isset($_GET['shop_order_status']) && $_GET['shop_order_status']=='processing') ? 'current' : '';
	$completed = (isset($_GET['shop_order_status']) && $_GET['shop_order_status']=='completed') ? 'current' : '';
	$cancelled = (isset($_GET['shop_order_status']) && $_GET['shop_order_status']=='cancelled') ? 'current' : '';
	$refunded = (isset($_GET['shop_order_status']) && $_GET['shop_order_status']=='refunded') ? 'current' : '';
	
	$views['pending'] = '<a class="'.$pending.'" href="?post_type=shop_order&amp;shop_order_status=pending">Pending <span class="count">('.$woocommerce_orders->pending_count.')</span></a>';
	$views['onhold'] = '<a class="'.$onhold.'" href="?post_type=shop_order&amp;shop_order_status=on-hold">On-Hold <span class="count">('.$woocommerce_orders->on_hold_count.')</span></a>';
	$views['processing'] = '<a class="'.$processing.'" href="?post_type=shop_order&amp;shop_order_status=processing">Processing <span class="count">('.$woocommerce_orders->processing_count.')</span></a>';
	$views['completed'] = '<a class="'.$completed.'" href="?post_type=shop_order&amp;shop_order_status=completed">Completed <span class="count">('.$woocommerce_orders->completed_count.')</span></a>';
	$views['cancelled'] = '<a class="'.$cancelled.'" href="?post_type=shop_order&amp;shop_order_status=cancelled">Cancelled <span class="count">('.$woocommerce_orders->cancelled_count.')</span></a>';
	$views['refunded'] = '<a class="'.$refunded.'" href="?post_type=shop_order&amp;shop_order_status=refunded">Refunded <span class="count">('.$woocommerce_orders->refunded_count.')</span></a>';
	
	if ($pending || $onhold || $processing || $completed || $cancelled || $refunded) :
		
		$views['all'] = str_replace('current', '', $views['all']);
		
	endif;	

	unset($views['publish']);
	
	if (isset($views['trash'])) :
		$trash = $views['trash'];
		unset($views['draft']);
		unset($views['trash']);
		$views['trash'] = $trash;
	endif;
	
	return $views;
}


/**
 * Order page actions
 **/
add_filter( 'post_row_actions', 'woocommerce_remove_row_actions', 10, 1 );

function woocommerce_remove_row_actions( $actions ) {
    if( get_post_type() === 'shop_order' ) :
        unset( $actions['view'] );
        unset( $actions['inline hide-if-no-js'] );
    endif;
    return $actions;
}


/**
 * Order page views
 **/
add_filter( 'bulk_actions-edit-shop_order', 'woocommerce_bulk_actions' );

function woocommerce_bulk_actions( $actions ) {
	
	if (isset($actions['edit'])) unset($actions['edit']);
	
	return $actions;
}


/**
 * Order messages
 **/
add_filter( 'post_updated_messages', 'woocommerce_post_updated_messages' );

function woocommerce_post_updated_messages( $messages ) {
	if( get_post_type() === 'shop_order' ) :
    	
    	$messages['post'][1] = sprintf( __('Order updated.', 'woothemes') );
    	$messages['post'][4] = sprintf( __('Order updated.', 'woothemes') );
		$messages['post'][6] = sprintf( __('Order published.', 'woothemes') );
		
		$messages['post'][8] = sprintf( __('Order submitted.', 'woothemes') );
		$messages['post'][10] = sprintf( __('Order draft updated.', 'woothemes') );
	
   	endif;
    return $messages;
}
