<?php
/**
 * Admin functions for the shop_order post type
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
 
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
	$columns["order_actions"] = __("Actions", 'woothemes');
	
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
			
			echo sprintf( __('<mark class="%s">%s</mark>', 'woothemes'), sanitize_title($order->status), __($order->status, 'woothemes') );
			
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
           	
           	echo '<small class="meta">' . __('Customer:', 'woothemes') . ' ' . $user . '</small>';
           	
           	if ($order->billing_email) :
        		echo '<small class="meta">'.__('Email:', 'woothemes') . ' ' . '<a href="' . esc_url( 'mailto:'.$order->billing_email ).'">'.esc_html( $order->billing_email ).'</a></small>';
        	endif;
        	if ($order->billing_phone) :
        		echo '<small class="meta">'.__('Tel:', 'woothemes') . ' ' . esc_html( $order->billing_phone ) . '</small>';
        	endif;
						
		break;
		case "billing_address" :
			echo '<strong>'.esc_html( $order->billing_first_name . ' ' . $order->billing_last_name );
        	if ($order->billing_company) echo ', '.esc_html( $order->billing_company );
        	echo '</strong><br/>';
        	echo '<a target="_blank" href="' . esc_url( 'http://maps.google.co.uk/maps?&q='.urlencode($order->formatted_billing_address).'&z=16' ) . '">'.esc_html( $order->formatted_billing_address ).'</a>';
        	
        	if ($order->payment_method_title) :
        		echo '<small class="meta">' . __('Paid via', 'woothemes') . ' ' . esc_html( $order->payment_method_title ) . '</small>';
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
        	
        	if ($order->shipping_method_title) :
        		echo '<small class="meta">' . __('Shipped via', 'woothemes') . ' ' . esc_html( $order->shipping_method_title ) . '</small>';
        	endif;
		break;
		case "total_cost" :
			echo woocommerce_price($order->order_total);
		break;
		case "order_date" :
			if ( '0000-00-00 00:00:00' == $post->post_date ) :
				$t_time = $h_time = __( 'Unpublished', 'woothemes' );
				$time_diff = 0;
			else :
				$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woothemes' ) );
				$m_time = $post->post_date;
				$time = get_post_time( 'G', true, $post );

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago', 'woothemes' ), human_time_diff( $time ) );
				else
					$h_time = mysql2date( __( 'Y/m/d', 'woothemes' ), $m_time );
			endif;

			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post ) . '</abbr>';
			
		break;
		case "order_actions" :
			
			?><p>
				<?php if (in_array($order->status, array('pending', 'on-hold'))) : ?><a class="button" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-mark-order-processing&order_id=' . $post->ID) ); ?>"><?php _e('Processing', 'woothemes'); ?></a><?php endif; ?>
				<?php if (in_array($order->status, array('pending', 'on-hold', 'processing'))) : ?><a class="button" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-mark-order-complete&order_id=' . $post->ID) ); ?>"><?php _e('Complete', 'woothemes'); ?></a><?php endif; ?>
				<a class="button" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit'); ?>"><?php _e('View', 'woothemes'); ?></a>
			</p><?php
			
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
			$output .=">".__($term->name, 'woothemes')." ($term->count)</option>";
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
 * Order custom field search
 **/
if (is_admin()) :
	add_filter( 'parse_query', 'woocommerce_shop_order_search_custom_fields' );
	add_filter( 'get_search_query', 'woocommerce_shop_order_search_label' );
endif;

function woocommerce_shop_order_search_custom_fields( $wp ) {
	global $pagenow, $wpdb;
   
	if( 'edit.php' != $pagenow ) return $wp;
	if( !isset( $wp->query_vars['s'] ) || !$wp->query_vars['s'] ) return $wp;
	if ($wp->query_vars['post_type']!='shop_order') return $wp;
	
	$search_fields = array(
		'_order_key',
		'_billing_first_name',
		'_billing_last_name',
		'_billing_company', 
		'_billing_address_1', 
		'_billing_address_2',
		'_billing_city',
		'_billing_postcode', 
		'_billing_country',
		'_billing_state',
		'_billing_email',
		'_order_items',
		'_billing_phone'
	);
	
	// Query matching custom fields - this seems faster than meta_query
	$post_ids = $wpdb->get_col($wpdb->prepare('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key IN ('.'"'.implode('","', $search_fields).'"'.') AND meta_value LIKE "%%%s%%"', esc_attr($_GET['s']) ));
	
	// Query matching excerpts and titles
	$post_ids = array_merge($post_ids, $wpdb->get_col($wpdb->prepare('
		SELECT '.$wpdb->posts.'.ID 
		FROM '.$wpdb->posts.' 
		LEFT JOIN '.$wpdb->postmeta.' ON '.$wpdb->posts.'.ID = '.$wpdb->postmeta.'.post_id
		LEFT JOIN '.$wpdb->users.' ON '.$wpdb->postmeta.'.meta_value = '.$wpdb->users.'.ID
		WHERE 
			post_excerpt 	LIKE "%%%1$s%%" OR
			post_title 		LIKE "%%%1$s%%" OR
			(
				meta_key		= "_customer_user" AND
				(
					user_login		LIKE "%%%1$s%%" OR
					user_nicename	LIKE "%%%1$s%%" OR
					user_email		LIKE "%%%1$s%%" OR
					display_name	LIKE "%%%1$s%%"
				)
			)
		', 
		esc_attr($_GET['s']) 
		)));
	
	// Add ID
	$search_order_id = str_replace('Order #', '', $_GET['s']);
	if (is_numeric($search_order_id)) $post_ids[] = $search_order_id;
	
	// Add blank ID so not all results are returned if the search finds nothing
	$post_ids[] = 0;
	
	// Remove s - we don't want to search order name
	unset( $wp->query_vars['s'] );
	
	// so we know we're doing this
	$wp->query_vars['shop_order_search'] = true;
	
	// Search by found posts
	$wp->query_vars['post__in'] = $post_ids;
}

function woocommerce_shop_order_search_label($query) {
	global $pagenow, $typenow;

    if( 'edit.php' != $pagenow ) return $query;
    if ( $typenow!='shop_order' ) return $query;
	if ( !get_query_var('shop_order_search')) return $query;
	
	return $_GET['s'];
}

/**
 * Query vars for custom searches
 **/
add_filter('query_vars', 'woocommerce_add_custom_query_var');

function woocommerce_add_custom_query_var($public_query_vars) {
	$public_query_vars[] = 'sku';
	$public_query_vars[] = 'shop_order_search';
	return $public_query_vars;
}