<?php
/**
 * Functions used for displaying the WooCommerce dashboard widgets
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

// Only hook in admin parts if the user has admin access
if (current_user_can('manage_woocommerce')) :
	add_action('right_now_content_table_end', 'woocommerce_content_right_now');
	add_action('right_now_table_end', 'woocommerce_right_now');
	add_action('wp_dashboard_setup', 'woocommerce_init_dashboard_widgets' );
	add_action('admin_footer', 'woocommmerce_dashboard_sales_js');
endif;

/**
 * Right now widget hooks/content
 */
function woocommerce_content_right_now() {
	
	global $woocommerce;
	?>
	</table>
	<p class="sub woocommerce_sub"><?php _e('Shop Content', 'woothemes'); ?></p>
	<table>
		<tr>
			<td class="first b"><a href="edit.php?post_type=product"><?php
				$num_posts = wp_count_posts( 'product' );
				$num = number_format_i18n( $num_posts->publish );
				echo $num;
			?></a></td>
			<td class="t"><a href="edit.php?post_type=product"><?php _e('Products', 'woothemes'); ?></a></td>
		</tr>
		<tr>
			<td class="first b"><a href="edit-tags.php?taxonomy=product_cat&post_type=product"><?php
				echo wp_count_terms('product_cat');
			?></a></td>
			<td class="t"><a href="edit-tags.php?taxonomy=product_cat&post_type=product"><?php _e('Product Categories', 'woothemes'); ?></a></td>
		</tr>
		<tr>
			<td class="first b"><a href="edit-tags.php?taxonomy=product_tag&post_type=product"><?php
				echo wp_count_terms('product_tag');
			?></a></td>
			<td class="t"><a href="edit-tags.php?taxonomy=product_tag&post_type=product"><?php _e('Product Tags', 'woothemes'); ?></a></td>
		</tr>
		<tr>
			<td class="first b"><a href="admin.php?page=woocommerce_attributes"><?php 
				echo sizeof($woocommerce->get_attribute_taxonomies());
			?></a></td>
			<td class="t"><a href="admin.php?page=woocommerce_attributes"><?php _e('Attribute taxonomies', 'woothemes'); ?></a></td>
		</tr>
	<?php
}

function woocommerce_right_now() {

	$woocommerce_orders = &new woocommerce_orders();
	?>
	</table>
	<p class="sub woocommerce_sub"><?php _e('Orders', 'woothemes'); ?></p>
	<table>
		<tr>
			<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=pending"><span class="total-count"><?php echo $woocommerce_orders->pending_count; ?></span></a></td>
			<td class="last t"><a class="pending" href="edit.php?post_type=shop_order&shop_order_status=pending"><?php _e('Pending', 'woothemes'); ?></a></td>
		</tr>
		<tr>
			<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=on-hold"><span class="total-count"><?php echo $woocommerce_orders->on_hold_count; ?></span></a></td>
			<td class="last t"><a class="onhold" href="edit.php?post_type=shop_order&shop_order_status=on-hold"><?php _e('On-Hold', 'woothemes'); ?></a></td>
		</tr>
		<tr>
			<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=processing"><span class="total-count"><?php echo $woocommerce_orders->processing_count; ?></span></a></td>
			<td class="last t"><a class="processing" href="edit.php?post_type=shop_order&shop_order_status=processing"><?php _e('Processing', 'woothemes'); ?></a></td>
		</tr>
		<tr>
			<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=completed"><span class="total-count"><?php echo $woocommerce_orders->completed_count; ?></span></a></td>
			<td class="last t"><a class="complete" href="edit.php?post_type=shop_order&shop_order_status=completed"><?php _e('Completed', 'woothemes'); ?></a></td>
		</tr>
	<?php
}

/**
 * Dashboard Widgets - init
 */
function woocommerce_init_dashboard_widgets() {

	global $current_month_offset;
						
	$current_month_offset = (int) date('m');
	
	if (isset($_GET['month'])) $current_month_offset = (int) $_GET['month'];
	
	$sales_heading = '';
	
	if ($current_month_offset!=date('m')) : 
		$sales_heading .= '<a href="index.php?month='.($current_month_offset+1).'" class="next">'.date('F', strtotime('01-'.($current_month_offset+1).'-2011')).__('  &rarr;', 'woothemes').'</a>';
	endif;
	
	$sales_heading .= '<a href="index.php?month='.($current_month_offset-1).'" class="previous">'.__('&larr; ', 'woothemes').date('F', strtotime('01-'.($current_month_offset-1).'-2011')).'</a><span>'.__('Monthly Sales', 'woothemes').'</span>';

	wp_add_dashboard_widget('woocommmerce_dashboard_sales', $sales_heading, 'woocommmerce_dashboard_sales');
	wp_add_dashboard_widget('woocommmerce_dashboard_recent_orders', __('WooCommerce recent orders', 'woothemes'), 'woocommmerce_dashboard_recent_orders');
	wp_add_dashboard_widget('woocommmerce_dashboard_recent_reviews', __('WooCommerce recent reviews', 'woothemes'), 'woocommmerce_dashboard_recent_reviews');
} 
				     		
/**
 * Recent orders widget
 */
function woocommmerce_dashboard_recent_orders() {

	$args = array(
	    'numberposts'     => 8,
	    'orderby'         => 'post_date',
	    'order'           => 'DESC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' 
	);
	$orders = get_posts( $args );
	if ($orders) :
		echo '<ul class="recent-orders">';
		foreach ($orders as $order) :
			
			$this_order = &new woocommerce_order( $order->ID );
			
			echo '
			<li>
				<span class="order-status '.sanitize_title($this_order->status).'">'.ucwords($this_order->status).'</span> <a href="'.admin_url('post.php?post='.$order->ID).'&action=edit">'.date_i18n('l jS \of F Y h:i:s A', strtotime($this_order->order_date)).'</a><br />
				<small>'.sizeof($this_order->items).' '._n('item', 'items', sizeof($this_order->items), 'woothemes').' <span class="order-cost">'.__('Total: ', 'woothemes').woocommerce_price($this_order->order_total).'</span></small>
			</li>';

		endforeach;
		echo '</ul>';
	endif;
} 

/**
 * Recent reviews widget
 */
function woocommmerce_dashboard_recent_reviews() {
	global $wpdb;
	$comments = $wpdb->get_results("SELECT *, SUBSTRING(comment_content,1,100) AS comment_excerpt
	FROM $wpdb->comments
	LEFT JOIN $wpdb->posts ON ($wpdb->comments.comment_post_ID = $wpdb->posts.ID)
	WHERE comment_approved = '1' 
	AND comment_type = '' 
	AND post_password = ''
	AND post_type = 'product'
	ORDER BY comment_date_gmt DESC
	LIMIT 5" );
	
	if ($comments) : 
		echo '<ul>';
		foreach ($comments as $comment) :
			
			echo '<li>';
			
			echo get_avatar($comment->comment_author, '32');
			
			$rating = get_comment_meta( $comment->comment_ID, 'rating', true );
			
			echo '<div class="star-rating" title="'.$rating.'">
				<span style="width:'.($rating*10).'px">'.$rating.' '.__('out of 5', 'woothemes').'</span></div>';
				
			echo '<h4 class="meta"><a href="'.get_permalink($comment->ID).'#comment-'.$comment->comment_ID .'">'.$comment->post_title.'</a> reviewed by ' .strip_tags($comment->comment_author) .'</h4>';
			echo '<blockquote>'.strip_tags($comment->comment_excerpt).' [...]</blockquote></li>';
			
		endforeach;
		echo '</ul>';
	else :
		echo '<p>'.__('There are no product reviews yet.', 'woothemes').'</p>';
	endif;
}

/**
 * Orders this month filter function
 */
function orders_this_month( $where = '' ) {
	global $current_month_offset;
	
	$month = $current_month_offset;
	$year = (int) date('Y');
	
	$first_day = strtotime("{$year}-{$month}-01");
	$last_day = strtotime('-1 second', strtotime('+1 month', $first_day));
	
	$after = date('Y-m-d', $first_day);
	$before = date('Y-m-d', $last_day);
	
	$where .= " AND post_date > '$after'";
	$where .= " AND post_date < '$before'";
	
	return $where;
}
	
/**
 * Sales widget
 */
function woocommmerce_dashboard_sales() {
		
	?><div id="placeholder" style="width:100%; height:300px; position:relative;"></div><?php
}

/**
 * Sales widget javascript
 */
function woocommmerce_dashboard_sales_js() {
	
	global $woocommerce;
	
	$screen = get_current_screen();
	
	if (!$screen || $screen->id!=='dashboard') return;
	
	global $current_month_offset;
	
	// Get orders to display in widget
	add_filter( 'posts_where', 'orders_this_month' );

	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'DESC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' ,
	    'suppress_filters' => false
	);
	$orders = get_posts( $args );
	
	$order_counts = array();
	$order_amounts = array();
		
	// Blank date ranges to begin
	$month = $current_month_offset;
	$year = (int) date('Y');
	
	$first_day = strtotime("{$year}-{$month}-01");
	$last_day = strtotime('-1 second', strtotime('+1 month', $first_day));

	if ((date('m') - $current_month_offset)==0) :
		$up_to = date('d', strtotime('NOW'));
	else :
		$up_to = date('d', $last_day);
	endif;
	$count = 0;
	
	while ($count < $up_to) :
		
		$time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $first_day))).'000';
		
		$order_counts[$time] = 0;
		$order_amounts[$time] = 0;

		$count++;
	endwhile;
	
	if ($orders) :
		foreach ($orders as $order) :
			
			$order_data = &new woocommerce_order($order->ID);
			
			if ($order_data->status=='cancelled' || $order_data->status=='refunded') continue;
			
			$time = strtotime(date('Ymd', strtotime($order->post_date))).'000';
			
			if (isset($order_counts[$time])) :
				$order_counts[$time]++;
			else :
				$order_counts[$time] = 1;
			endif;
			
			if (isset($order_amounts[$time])) :
				$order_amounts[$time] = $order_amounts[$time] + $order_data->order_total;
			else :
				$order_amounts[$time] = (float) $order_data->order_total;
			endif;
			
		endforeach;
	endif;
	
	remove_filter( 'posts_where', 'orders_this_month' );
	
	/* Script variables */
	$params = array( 
		'currency_symbol' 				=> get_woocommerce_currency_symbol() 
	);
	
	$order_counts_array = array();
	foreach ($order_counts as $key => $count) :
		$order_counts_array[] = array($key, $count);
	endforeach;
	
	$order_amounts_array = array();
	foreach ($order_amounts as $key => $amount) :
		$order_amounts_array[] = array($key, $amount);
	endforeach;
	
	$order_data = array( 'order_counts' => $order_counts_array, 'order_amounts' => $order_amounts_array );

	$params['order_data'] = json_encode($order_data);	
	
	// Queue scripts
	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
	
	wp_register_script( 'woocommerce_dashboard_sales', $woocommerce->plugin_url() . '/assets/js/admin/dashboard_sales'.$suffix.'.js', 'jquery', '1.0' );
	wp_register_script( 'flot', $woocommerce->plugin_url() . '/assets/js/admin/jquery.flot'.$suffix.'.js', 'jquery', '1.0' );
	wp_register_script( 'flot-resize', $woocommerce->plugin_url() . '/assets/js/admin/jquery.flot.resize'.$suffix.'.js', 'jquery', '1.0' );
	
	wp_localize_script( 'woocommerce_dashboard_sales', 'params', $params );
	
	wp_print_scripts('flot');
	wp_print_scripts('flot-resize');
	wp_print_scripts('woocommerce_dashboard_sales');
	
}
