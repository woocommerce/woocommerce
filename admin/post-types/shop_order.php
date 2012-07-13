<?php
/**
 * Admin functions for the shop_order post type
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
 
/**
 * Disable auto-save
 **/
add_action('admin_print_scripts', 'woocommerce_disable_autosave_for_orders');

function woocommerce_disable_autosave_for_orders(){
    global $post;
    
    if($post && get_post_type($post->ID) === 'shop_order'){
        wp_dequeue_script('autosave');
    }
}

/**
 * Columns for order page
 **/
add_filter('manage_edit-shop_order_columns', 'woocommerce_edit_order_columns');

function woocommerce_edit_order_columns($columns){
	global $woocommerce;
	
	$columns = array();
	
	$columns["cb"] = "<input type=\"checkbox\" />";
	$columns["order_status"] = __("Status", 'woocommerce');
	$columns["order_title"] = __("Order", 'woocommerce');
	$columns["billing_address"] = __("Billing", 'woocommerce');
	$columns["shipping_address"] = __("Shipping", 'woocommerce');
	$columns["total_cost"] = __("Order Total", 'woocommerce');
	$columns["order_comments"] = '<img alt="' . esc_attr__( 'Order Notes', 'woocommerce' ) . '" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" class="tips" data-tip="' . __("Order Notes", 'woocommerce') . '" />';
	$columns["note"] = '<img src="' . $woocommerce->plugin_url() . '/assets/images/note_head.png" alt="' . __("Customer Notes", 'woocommerce') . '" class="tips" data-tip="' . __("Customer Notes", 'woocommerce') . '" />';
	$columns["order_date"] = __("Date", 'woocommerce');
	$columns["order_actions"] = __("Actions", 'woocommerce');
	
	return $columns;
}

/**
 * Custom Columns for order page
 **/
add_action('manage_shop_order_posts_custom_column', 'woocommerce_custom_order_columns', 2);

function woocommerce_custom_order_columns($column) {

	global $post, $woocommerce;
	$order = new WC_Order( $post->ID );
	
	switch ($column) {
		case "order_status" :
			
			printf( '<mark class="%s">%s</mark>', sanitize_title($order->status), __($order->status, 'woocommerce') );
			
		break;
		case "order_title" :
			
			if ($order->user_id) $user_info = get_userdata($order->user_id);
			
			if (isset($user_info) && $user_info) : 
			                    	
            	$user = '<a href="user-edit.php?user_id=' . esc_attr( $user_info->ID ) . '">';
            	
            	if ($user_info->first_name || $user_info->last_name) $user .= $user_info->first_name.' '.$user_info->last_name;
            	else $user .= esc_html( $user_info->display_name );
            	
            	$user .= '</a>';

           	else : 
           		$user = __('Guest', 'woocommerce');
           	endif;
           	
           	echo '<a href="'.admin_url('post.php?post='.$post->ID.'&action=edit').'"><strong>'.sprintf( __('Order %s', 'woocommerce'), $order->get_order_number() ).'</strong></a> ' . __('made by', 'woocommerce') . ' ' . $user;
           	
           	if ($order->billing_email) :
        		echo '<small class="meta">'.__('Email:', 'woocommerce') . ' ' . '<a href="' . esc_url( 'mailto:'.$order->billing_email ).'">'.esc_html( $order->billing_email ).'</a></small>';
        	endif;
        	if ($order->billing_phone) :
        		echo '<small class="meta">'.__('Tel:', 'woocommerce') . ' ' . esc_html( $order->billing_phone ) . '</small>';
        	endif;
						
		break;
		case "billing_address" :
			if ($order->get_formatted_billing_address()) :
			
        		echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q='.urlencode( $order->get_billing_address() ).'&z=16' ) . '">'. preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_billing_address()) .'</a>';
        	else :
        		echo '&ndash;';
        	endif;
        	
        	if ($order->payment_method_title) :
        		echo '<small class="meta">' . __('Via', 'woocommerce') . ' ' . esc_html( $order->payment_method_title ) . '</small>';
        	endif;
        	
		break;
		case "shipping_address" :
			if ($order->get_formatted_shipping_address()) :
            	
            	echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q='.urlencode( $order->get_shipping_address() ).'&z=16' ) .'">'. preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_shipping_address()) .'</a>';
        	else :
        		echo '&ndash;';
        	endif;
        	
        	if ($order->shipping_method_title) :
        		echo '<small class="meta">' . __('Via', 'woocommerce') . ' ' . esc_html( $order->shipping_method_title ) . '</small>';
        	endif;
		break;
		case "total_cost" :
			echo $order->get_formatted_order_total();
		break;
		case "order_date" :
		
			if ( '0000-00-00 00:00:00' == $post->post_date ) :
				$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
			else :
				$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );
				
				$gmt_time = strtotime($post->post_date_gmt);
				$time_diff = current_time('timestamp', 1) - $gmt_time;
				
				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago', 'woocommerce' ), human_time_diff( $gmt_time, current_time('timestamp', 1) ) );
				else
					$h_time = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
			endif;

			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post ) . '</abbr>';
			
		break;
		case "order_actions" :
			
			?><p>
				<?php if (in_array($order->status, array('pending', 'on-hold'))) : ?><a class="button" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-mark-order-processing&order_id=' . $post->ID), 'woocommerce-mark-order-processing' ); ?>"><?php _e('Processing', 'woocommerce'); ?></a><?php endif; ?>
				<?php if (in_array($order->status, array('pending', 'on-hold', 'processing'))) : ?><a class="button" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-mark-order-complete&order_id=' . $post->ID), 'woocommerce-mark-order-complete' ); ?>"><?php _e('Complete', 'woocommerce'); ?></a><?php endif; ?>
				<a class="button" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit'); ?>"><?php _e('View', 'woocommerce'); ?></a>
			</p><?php
			
		break;
		case "note" :
			
			if ($order->customer_note) 
				echo '<img src="'.$woocommerce->plugin_url().'/assets/images/note.png" alt="yes" class="tips" data-tip="'. __('Yes', 'woocommerce') .'" />';
			else 
				echo '<img src="'.$woocommerce->plugin_url().'/assets/images/note-off.png" alt="no" class="tips" data-tip="'. __('No', 'woocommerce') .'" />';
			
		break;
		case "order_comments" :
			
			echo '<div class="post-com-count-wrapper">
				<a href="'. admin_url('post.php?post='.$post->ID.'&action=edit') .'" class="post-com-count"><span class="comment-count">'. $post->comment_count .'</span></a>			
				</div>';
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
		$output .= '<option value="">'.__('Show all statuses', 'woocommerce').'</option>';
		foreach($terms as $term) :
			$output .="<option value='$term->slug' ";
			if ( isset( $wp_query->query['shop_order_status'] ) ) $output .=selected($term->slug, $wp_query->query['shop_order_status'], false);
			$output .=">".__($term->name, 'woocommerce')." ($term->count)</option>";
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
			$output .= '<option value="">'.__('Show all customers', 'woocommerce').'</option>';
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
	unset($columns['comments']);
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
	
	$search_fields = apply_filters( 'woocommerce_shop_order_search_fields', array(
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
	) );
	
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