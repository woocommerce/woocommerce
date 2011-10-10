<?php
/**
 * Functions used for custom post types in admin 
 *
 * These functions control columns in admin, and other admin interface bits.
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
	$columns["expiry_date"] = __("Expiry date", 'woothemes');

	return $columns;
}


/**
 * Custom Columns for Coupons page
 **/
add_action('manage_shop_coupon_posts_custom_column', 'woocommerce_custom_coupon_columns', 2);

function woocommerce_custom_coupon_columns($column) {
	global $post, $woocommerce;
	
	$type 			= get_post_meta($post->ID, 'discount_type', true);
	$amount 		= get_post_meta($post->ID, 'coupon_amount', true);
	$individual_use = get_post_meta($post->ID, 'individual_use', true);
	$product_ids 	= (get_post_meta($post->ID, 'product_ids', true)) ? explode(',', get_post_meta($post->ID, 'product_ids', true)) : array();
	$usage_limit 	= get_post_meta($post->ID, 'usage_limit', true);
	$usage_count 	= (int) get_post_meta($post->ID, 'usage_count', true);
	$expiry_date 	= get_post_meta($post->ID, 'expiry_date', true);

	switch ($column) {
		case "type" :
			echo $woocommerce->get_coupon_discount_type($type);			
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
		case "expiry_date" :
			if ($expiry_date) echo date('F j, Y', strtotime($expiry_date)); else echo '&ndash;';
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
	if( get_option('woocommerce_enable_sku', true) == 'yes' ) $columns["sku"] = __("ID", 'woothemes');
	$columns["product_type"] = __("Type", 'woothemes');
	
	$columns["product_cat"] = __("Categories", 'woothemes');
	$columns["product_tags"] = __("Tags", 'woothemes');
	$columns["featured"] = __("Featured", 'woothemes');
	
	if (get_option('woocommerce_manage_stock')=='yes') :
		$columns["is_in_stock"] = __("In Stock?", 'woothemes');
	endif;
	
	$columns["price"] = __("Price", 'woothemes');
	$columns["product_date"] = __("Date", 'woothemes');
	
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
		case "price":
			echo $product->get_price_html();	
		break;
		case "product_cat" :
			if (!$terms = get_the_term_list($post->ID, 'product_cat', '', ', ','')) echo '<span class="na">&ndash;</span>'; else echo $terms;
		break;
		case "product_tags" :
			if (!$terms = get_the_term_list($post->ID, 'product_tag', '', ', ','')) echo '<span class="na">&ndash;</span>'; else echo $terms;
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
		case "is_in_stock" :
			if ( !$product->is_type( 'grouped' ) && $product->is_in_stock() ) :
				echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success.gif" alt="yes" /> ';
			else :
				echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success-off.gif" alt="no" /> ';
			endif;
			if ( $product->managing_stock() ) :
				echo $product->stock.__(' in stock', 'woothemes');
			endif;
		break;
		case "product_type" :
			echo ucwords($product->product_type);
		break;
		case "product_date" :
			if ( '0000-00-00 00:00:00' == $post->post_date ) :
				$t_time = $h_time = __( 'Unpublished' );
				$time_diff = 0;
			else :
				$t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
				$m_time = $post->post_date;
				$time = get_post_time( 'G', true, $post );

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
				else
					$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
			endif;

			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post ) . '</abbr><br />';
			
			if ( 'publish' == $post->post_status ) :
				_e( 'Published' );
			elseif ( 'future' == $post->post_status ) :
				if ( $time_diff > 0 ) :
					echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
				else :
					_e( 'Scheduled' );
				endif;
			else :
				_e( 'Last Modified' );
			endif;

			if ( $this_data = $product->visibility ) :
				echo '<br />' . ucfirst($this_data);	
			endif;
			
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
		'is_in_stock' 	=> 'inventory',
		'price'			=> 'price',
		'featured'		=> 'featured',
		'sku'			=> 'sku',
		'product_date'	=> 'date'
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
    if ($typenow=='product') :
		$terms = get_terms('product_cat');
		$output = "<select name='product_cat' id='dropdown_product_cat'>";
		$output .= '<option value="">'.__('Show all categories', 'woothemes').'</option>';
		foreach($terms as $term) :
			if ( isset( $wp_query->query['product_cat'] ) ) :
				$output .="<option value='$term->slug' ".selected($term->slug, $wp_query->query['product_cat'], false).">$term->name ($term->count)</option>";
			endif;
		endforeach;
		$output .="</select>";
		echo $output;
    endif;
}

/**
 * Filter products by type
 **/
add_action('restrict_manage_posts', 'woocommerce_products_by_type');

function woocommerce_products_by_type() {
    global $typenow, $wp_query;
    if ($typenow=='product') :
		$terms = get_terms('product_type');
		$output = "<select name='product_type' id='dropdown_product_type'>";
		$output .= '<option value="">'.__('Show all types', 'woothemes').'</option>';
		foreach($terms as $term) :
			$output .="<option value='$term->slug' ";
			if ( isset( $wp_query->query['product_type'] ) ) $output .=selected($term->slug, $wp_query->query['product_type'], false);
			$output .=">$term->name ($term->count)</option>";
		endforeach;
		$output .="</select>";
		echo $output;
    endif;
}

/**
 * Add functionality to the image uploader on product pages to exlcude an image
 **/
add_filter('attachment_fields_to_edit', 'woocommerce_exclude_image_from_product_page_field', 1, 2);
add_filter('attachment_fields_to_save', 'woocommerce_exclude_image_from_product_page_field_save', 1, 2);

function woocommerce_exclude_image_from_product_page_field( $fields, $object ) {
	
	if (!$object->post_parent) return $fields;
	
	$parent = get_post( $object->post_parent );
	
	if ($parent->post_type!=='product') return $fields;
	
	$exclude_image = (int) get_post_meta($object->ID, '_woocommerce_exclude_image', true);
	
	$label = __('Exclude image', 'woothemes');
	
	$html = '<input type="checkbox" '.checked($exclude_image, 1, false).' name="attachments['.$object->ID.'][woocommerce_exclude_image]" id="attachments['.$object->ID.'][woocommerce_exclude_image" />';
	
	$fields['woocommerce_exclude_image'] = array(
			'label' => $label,
			'input' => 'html',
			'html' =>  $html,
			'value' => '',
			'helps' => __('Enabling this option will hide it from the product page image gallery.', 'woothemes')
	);
	
	return $fields;
}

function woocommerce_exclude_image_from_product_page_field_save( $post, $attachment ) {

	if (isset($_REQUEST['attachments'][$post['ID']]['woocommerce_exclude_image'])) 
		update_post_meta($post['ID'], '_woocommerce_exclude_image', 1);
	else 
		update_post_meta($post['ID'], '_woocommerce_exclude_image', 0);
				
	return $post;
				
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
	$columns["billing_address"] = __("Billing", 'woothemes');
	$columns["shipping_address"] = __("Shipping", 'woothemes');
	$columns["total_cost"] = __("Order Total", 'woothemes');
	$columns["order_date"] = __("Date", 'woothemes');
	
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
			
			if ($order->user_id) $user_info = get_userdata($order->user_id);
			
			if (isset($user_info) && $user_info) : 
			                    	
            	$user = '<a href="user-edit.php?user_id=' . esc_attr( $user_info->ID ) . '">';
            	
            	if ($user_info->first_name || $user_info->last_name) $user .= $user_info->first_name.' '.$user_info->last_name;
            	else $user .= esc_html( $user_info->display_name );
            	
            	$user .= '</a>';

           	else : 
           		$user = __('Guest', 'woothemes');
           	endif;
           	
           	echo '<small class="meta">' . __('Customer: ', 'woothemes') . $user . '</small>';
           	
           	if ($order->billing_email) :
        		echo '<small class="meta">'.__('Email: ', 'woothemes').'<a href="' . esc_url( 'mailto:'.$order->billing_email ).'">'.esc_html( $order->billing_email ).'</a></small>';
        	endif;
        	if ($order->billing_phone) :
        		echo '<small class="meta">'.__('Tel: ', 'woothemes'). esc_html( $order->billing_phone ) . '</small>';
        	endif;
						
		break;
		case "billing_address" :
			echo '<strong>'.esc_html( $order->billing_first_name . ' ' . $order->billing_last_name );
        	if ($order->billing_company) echo ', '.esc_html( $order->billing_company );
        	echo '</strong><br/>';
        	echo '<a target="_blank" href="' . esc_url( 'http://maps.google.co.uk/maps?&q='.urlencode($order->formatted_billing_address).'&z=16' ) . '">'.esc_html( $order->formatted_billing_address ).'</a>';
        	
        	if ($order->payment_method) :
        		echo '<small class="meta">' . __('Paid via ', 'woothemes') . esc_html( $order->payment_method ) . '</small>';
        	endif;
        	
		break;
		case "shipping_address" :
			if ($order->formatted_shipping_address) :
            	echo '<strong>'.esc_html( $order->shipping_first_name . ' ' . $order->shipping_last_name );
            	if ($order->shipping_company) : echo ', '.esc_html( $order->shipping_company ); endif;
            	echo '</strong><br/>';
            	echo '<a target="_blank" href="' . esc_url( 'http://maps.google.co.uk/maps?&q='.urlencode($order->formatted_shipping_address).'&z=16' ) .'">'.esc_html( $order->formatted_shipping_address ).'</a>';
        	else :
        		echo '&ndash;';
        	endif;
        	
        	if ($order->shipping_method) :
        		echo '<small class="meta">' . __('Shipped via ', 'woothemes') . esc_html( $order->shipping_method ) . '</small>';
        	endif;
		break;
		case "total_cost" :
			echo woocommerce_price($order->order_total);
		break;
		case "order_date" :
			if ( '0000-00-00 00:00:00' == $post->post_date ) :
				$t_time = $h_time = __( 'Unpublished' );
				$time_diff = 0;
			else :
				$t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
				$m_time = $post->post_date;
				$time = get_post_time( 'G', true, $post );

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
				else
					$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
			endif;

			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post ) . '</abbr>';
			
		break;
	}
}


/**
 * Order page filters
 **/
add_filter('views_edit-shop_order', 'woocommerce_custom_order_views');

function woocommerce_custom_order_views( $views ) {

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
 * Order page bulk actions
 **/
add_filter( 'bulk_actions-edit-shop_order', 'woocommerce_bulk_actions' );

function woocommerce_bulk_actions( $actions ) {
	
	if (isset($actions['edit'])) unset($actions['edit']);

	return $actions;
}

/**
 * Filter orders by status
 **/
add_action('restrict_manage_posts','woocommerce_orders_by_status');

function woocommerce_orders_by_status() {
    global $typenow, $wp_query;
    if ($typenow=='shop_order') :
		$terms = get_terms('shop_order_status');
		$output = "<select name='shop_order_status' id='dropdown_shop_order_status'>";
		$output .= '<option value="">'.__('Show all statuses', 'woothemes').'</option>';
		foreach($terms as $term) :
			$output .="<option value='$term->slug' ";
			if ( isset( $wp_query->query['shop_order_status'] ) ) $output .=selected($term->slug, $wp_query->query['shop_order_status'], false);
			$output .=">$term->name ($term->count)</option>";
		endforeach;
		$output .="</select>";
		echo $output;
    endif;
}

/**
 * Filter orders by customer
 **/
add_action('restrict_manage_posts', 'woocommerce_orders_by_customer');

function woocommerce_orders_by_customer() {
    global $typenow, $wp_query;
    if ($typenow=='shop_order') :
		
		$users_query = new WP_User_Query( array( 
			'fields' => 'all', 
			//'role' => 'customer', 
			'orderby' => 'display_name'
			) );
		$users = $users_query->get_results();
		if ($users) :
		
			$output = "<select name='_customer_user' id='dropdown_customers'>";
			$output .= '<option value="">'.__('Show all customers', 'woothemes').'</option>';
			foreach($users as $user) :
				$output .="<option value='$user->ID' ";
				if ( isset( $_GET['_customer_user'] ) ) $output .=selected($user->ID, $_GET['_customer_user'], false);
				$output .=">$user->display_name</option>";
			endforeach;
			$output .="</select>";
			echo $output;
		
		endif;
    endif;
}

/**
 * Filter orders by customer query
 **/
add_filter( 'request', 'woocommerce_orders_by_customer_query' );

function woocommerce_orders_by_customer_query( $vars ) {
	global $typenow, $wp_query;
    if ($typenow=='shop_order' && isset( $_GET['_customer_user'] ) && $_GET['_customer_user']>0) :
    
		$vars['meta_key'] = '_customer_user';
		$vars['meta_value'] = (int) $_GET['_customer_user'];
		
	endif;
	
	return $vars;
}

/**
 * Make order columns sortable
 * https://gist.github.com/906872
 **/
add_filter("manage_edit-shop_order_sortable_columns", 'woocommerce_custom_shop_order_sort');

function woocommerce_custom_shop_order_sort($columns) {
	$custom = array(
		'order_title'	=> 'ID',
		'order_total'	=> 'order_total',
		'order_date'	=> 'date'
	);
	return wp_parse_args($custom, $columns);
}


/**
 * Order column orderby/request
 **/
add_filter( 'request', 'woocommerce_custom_shop_order_orderby' );

function woocommerce_custom_shop_order_orderby( $vars ) {
	global $typenow, $wp_query;
    if ($typenow!='shop_order') return $vars;
    
    // Sorting
	if (isset( $vars['orderby'] )) :
		if ( 'order_total' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_order_total',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
	endif;
	
	return $vars;
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


/**
 * Feature a product from admin
 */
function woocommerce_feature_product() {

	if( !is_admin() ) die;
	
	if( !current_user_can('edit_posts') ) wp_die( __('You do not have sufficient permissions to access this page.') );
	
	if( !check_admin_referer()) wp_die( __('You have taken too long. Please go back and retry.', 'woothemes') );
	
	$post_id = isset($_GET['product_id']) && (int)$_GET['product_id'] ? (int)$_GET['product_id'] : '';
	
	if(!$post_id) die;
	
	$post = get_post($post_id);
	if(!$post) die;
	
	if($post->post_type !== 'product') die;
	
	$product = new woocommerce_product($post->ID);

	if ($product->is_featured()) update_post_meta($post->ID, 'featured', 'no');
	else update_post_meta($post->ID, 'featured', 'yes');
	
	$sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
	wp_safe_redirect( $sendback );

}
add_action('wp_ajax_woocommerce-feature-product', 'woocommerce_feature_product');
