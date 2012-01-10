<?php
/**
 * Admin functions for the shop_product post type
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

/**
 * Duplicate a product action
 *
 * Based on 'Duplicate Post' (http://www.lopo.it/duplicate-post-plugin/) by Enrico Battocchi
 */
add_action('admin_action_duplicate_product', 'woocommerce_duplicate_product_action');

function woocommerce_duplicate_product_action() {

	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'duplicate_post_save_as_new_page' == $_REQUEST['action'] ) ) ) {
		wp_die(__('No product to duplicate has been supplied!', 'woocommerce'));
	}

	// Get the original page
	$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
	check_admin_referer( 'woocommerce-duplicate-product_' . $id );
	$post = woocommerce_get_product_to_duplicate($id);

	// Copy the page and insert it
	if (isset($post) && $post!=null) {
		$new_id = woocommerce_create_duplicate_from_product($post);

		// If you have written a plugin which uses non-WP database tables to save
		// information about a page you can hook this action to dupe that data.
		do_action( 'woocommerce_duplicate_product', $new_id, $post );

		// Redirect to the edit screen for the new draft page
		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
		exit;
	} else {
		wp_die(__('Product creation failed, could not find original product:', 'woocommerce') . ' ' . $id);
	}
}

/**
 * Duplicate a product link on products list
 */
add_filter('post_row_actions', 'woocommerce_duplicate_product_link_row',10,2);
add_filter('page_row_actions', 'woocommerce_duplicate_product_link_row',10,2);
	
function woocommerce_duplicate_product_link_row($actions, $post) {
	
	if (function_exists('duplicate_post_plugin_activation')) return $actions;
	
	if (!current_user_can('manage_woocommerce')) return $actions;
	
	if ($post->post_type!='product') return $actions;
	
	$actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=duplicate_product&amp;post=' . $post->ID ), 'woocommerce-duplicate-product_' . $post->ID ) . '" title="' . __("Make a duplicate from this product", 'woocommerce')
		. '" rel="permalink">' .  __("Duplicate", 'woocommerce') . '</a>';

	return $actions;
}

/**
 *  Duplicate a product link on edit screen
 */
add_action( 'post_submitbox_start', 'woocommerce_duplicate_product_post_button' );

function woocommerce_duplicate_product_post_button() {
	global $post;
	
	if (function_exists('duplicate_post_plugin_activation')) return;
	
	if (!current_user_can('manage_woocommerce')) return;
	
	if( !is_object( $post ) ) return;
	
	if ($post->post_type!='product') return;
	
	if ( isset( $_GET['post'] ) ) :
		$notifyUrl = wp_nonce_url( admin_url( "admin.php?action=duplicate_product&post=" . $_GET['post'] ), 'woocommerce-duplicate-product_' . $_GET['post'] );
		?>
		<div id="duplicate-action"><a class="submitduplicate duplication" href="<?php echo esc_url( $notifyUrl ); ?>"><?php _e('Copy to a new draft', 'woocommerce'); ?></a></div>
		<?php
	endif;
}

/**
 * Get a product from the database
 */
function woocommerce_get_product_to_duplicate($id) {
	global $wpdb;
	$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
	if ($post->post_type == "revision"){
		$id = $post->post_parent;
		$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
	}
	return $post[0];
}

/**
 * Function to create the duplicate
 */
function woocommerce_create_duplicate_from_product($post, $parent = 0) {
	global $wpdb;

	$new_post_author 	= wp_get_current_user();
	$new_post_date 		= current_time('mysql');
	$new_post_date_gmt 	= get_gmt_from_date($new_post_date);
	
	if ($parent>0) :
		$post_parent		= $parent;
		$suffix 			= '';
		$post_status     	= 'publish';
	else :
		$post_parent		= $post->post_parent;
		$post_status     	= 'draft';
		$suffix 			= __(" (Copy)", 'woocommerce');
	endif;
	
	$new_post_type 		= $post->post_type;
	$post_content    	= str_replace("'", "''", $post->post_content);
	$post_content_filtered = str_replace("'", "''", $post->post_content_filtered);
	$post_excerpt    	= str_replace("'", "''", $post->post_excerpt);
	$post_title      	= str_replace("'", "''", $post->post_title).$suffix;
	$post_name       	= str_replace("'", "''", $post->post_name);
	$comment_status  	= str_replace("'", "''", $post->comment_status);
	$ping_status     	= str_replace("'", "''", $post->ping_status);

	// Insert the new template in the post table
	$wpdb->query(
			"INSERT INTO $wpdb->posts
			(post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, post_type, comment_status, ping_status, post_password, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_mime_type)
			VALUES
			('$new_post_author->ID', '$new_post_date', '$new_post_date_gmt', '$post_content', '$post_content_filtered', '$post_title', '$post_excerpt', '$post_status', '$new_post_type', '$comment_status', '$ping_status', '$post->post_password', '$post->to_ping', '$post->pinged', '$new_post_date', '$new_post_date_gmt', '$post_parent', '$post->menu_order', '$post->post_mime_type')");

	$new_post_id = $wpdb->insert_id;

	// Copy the taxonomies
	woocommerce_duplicate_post_taxonomies($post->ID, $new_post_id, $post->post_type);

	// Copy the meta information
	woocommerce_duplicate_post_meta($post->ID, $new_post_id);
	
	// Copy the children (variations)
	if ( $children_products =& get_children( 'post_parent='.$post->ID.'&post_type=product_variation' ) ) :

		if ($children_products) foreach ($children_products as $child) :
			
			woocommerce_create_duplicate_from_product(woocommerce_get_product_to_duplicate($child->ID), $new_post_id);
			
		endforeach;

	endif;

	return $new_post_id;
}

/**
 * Copy the taxonomies of a post to another post
 */
function woocommerce_duplicate_post_taxonomies($id, $new_id, $post_type) {
	global $wpdb;
	$taxonomies = get_object_taxonomies($post_type); //array("category", "post_tag");
	foreach ($taxonomies as $taxonomy) {
		$post_terms = wp_get_object_terms($id, $taxonomy);
		for ($i=0; $i<count($post_terms); $i++) {
			wp_set_object_terms($new_id, $post_terms[$i]->slug, $taxonomy, true);
		}
	}
}

/**
 * Copy the meta information of a post to another post
 */
function woocommerce_duplicate_post_meta($id, $new_id) {
	global $wpdb;
	$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$id");

	if (count($post_meta_infos)!=0) {
		$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
		foreach ($post_meta_infos as $meta_info) {
			$meta_key = $meta_info->meta_key;
			$meta_value = addslashes($meta_info->meta_value);
			$sql_query_sel[]= "SELECT $new_id, '$meta_key', '$meta_value'";
		}
		$sql_query.= implode(" UNION ALL ", $sql_query_sel);
		$wpdb->query($sql_query);
	}
}

/**
 * Columns for Products page
 **/
add_filter('manage_edit-product_columns', 'woocommerce_edit_product_columns');

function woocommerce_edit_product_columns($columns){
	
	$columns = array();
	
	$columns["cb"] = "<input type=\"checkbox\" />";
	$columns["thumb"] = __("Image", 'woocommerce');
	
	$columns["title"] = __("Name", 'woocommerce');
	if( get_option('woocommerce_enable_sku', true) == 'yes' ) $columns["sku"] = __("ID", 'woocommerce');
	$columns["product_type"] = __("Type", 'woocommerce');
	
	$columns["product_cat"] = __("Categories", 'woocommerce');
	$columns["product_tags"] = __("Tags", 'woocommerce');
	$columns["featured"] = __("Featured", 'woocommerce');
	
	if (get_option('woocommerce_manage_stock')=='yes') :
		$columns["is_in_stock"] = __("In Stock?", 'woocommerce');
	endif;
	
	$columns["price"] = __("Price", 'woocommerce');
	$columns["product_date"] = __("Date", 'woocommerce');
	
	return $columns;
}


/**
 * Custom Columns for Products page
 **/
add_action('manage_product_posts_custom_column', 'woocommerce_custom_product_columns', 2);

function woocommerce_custom_product_columns($column) {
	global $post, $woocommerce;
	$product = new woocommerce_product($post->ID);

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
			if ( $sku = get_post_meta( $post->ID, '_sku', true )) :
				echo '#'.$post->ID.' - SKU: ' . $sku;	
			else :
				echo '#'.$post->ID;
			endif;
		break;
		case "featured" :
			$url = wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-feature-product&product_id=' . $post->ID) );
			echo '<a href="'.$url.'" title="'.__('Change', 'woocommerce') .'">';
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
				echo $product->get_total_stock().__(' in stock', 'woocommerce');
			endif;
		break;
		case "product_type" :
			echo ucwords($product->product_type);
		break;
		case "product_date" :
			if ( '0000-00-00 00:00:00' == $post->post_date ) :
				$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
				$time_diff = 0;
			else :
				$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ) );
				$m_time = $post->post_date;
				$time = get_post_time( 'G', true, $post );

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago', 'woocommerce' ), human_time_diff( $time ) );
				else
					$h_time = mysql2date( __( 'Y/m/d', 'woocommerce' ), $m_time );
			endif;

			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post ) . '</abbr><br />';
			
			if ( 'publish' == $post->post_status ) :
				_e( 'Published', 'woocommerce' );
			elseif ( 'future' == $post->post_status ) :
				if ( $time_diff > 0 ) :
					echo '<strong class="attention">' . __( 'Missed schedule', 'woocommerce' ) . '</strong>';
				else :
					_e( 'Scheduled', 'woocommerce' );
				endif;
			else :
				_e( 'Last Modified', 'woocommerce' );
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
				'meta_key' 	=> '_stock',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'price' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_price',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'featured' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_featured',
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
 * Filter products by category, uses slugs for option values. Code adapted by Andrew Benbow - chromeorange.co.uk
 **/
add_action('restrict_manage_posts','woocommerce_products_by_category');

function woocommerce_products_by_category() {
	global $typenow, $wp_query;
    if ($typenow=='product') :
    	woocommerce_product_dropdown_categories();
    endif;
}


/**
 * Filter products by type
 **/
add_action('restrict_manage_posts', 'woocommerce_products_by_type');

function woocommerce_products_by_type() {
    global $typenow, $wp_query;
    if ($typenow=='product') :
    	
    	// Types
		$terms = get_terms('product_type');
		$output = "<select name='product_type' id='dropdown_product_type'>";
		$output .= '<option value="">'.__('Show all product types', 'woocommerce').'</option>';
		foreach($terms as $term) :
			$output .="<option value='$term->slug' ";
			if ( isset( $wp_query->query['product_type'] ) ) $output .=selected($term->slug, $wp_query->query['product_type'], false);
			$output .=">".ucfirst($term->name)." ($term->count)</option>";
		endforeach;
		$output .="</select>";
		
		// Downloadable/virtual
		$output .= "<select name='product_subtype' id='dropdown_product_subtype'>";
		$output .= '<option value="">'.__('Show all sub-types', 'woocommerce').'</option>';
		
		$output .="<option value='downloadable' ";
		if ( isset( $_GET['product_subtype'] ) ) $output .= selected('downloadable', $_GET['product_subtype'], false);
		$output .=">".__('Downloadable', 'woocommerce')."</option>";
		
		$output .="<option value='virtual' ";
		if ( isset( $_GET['product_subtype'] ) ) $output .= selected('virtual', $_GET['product_subtype'], false);
		$output .=">".__('Virtual', 'woocommerce')."</option>";

		$output .="</select>";
		
		echo $output;
    endif;
}

add_filter( 'parse_query', 'woocommerce_products_subtype_query' );

function woocommerce_products_subtype_query($query) {
	global $typenow, $wp_query;
    if ($typenow=='product' && isset($_GET['product_subtype']) && $_GET['product_subtype']) :
    	if ($_GET['product_subtype']=='downloadable') :
        	$query->query_vars['meta_value'] 	= 'yes';
        	$query->query_vars['meta_key'] 		= '_downloadable';
        endif;
        if ($_GET['product_subtype']=='virtual') :
        	$query->query_vars['meta_value'] 	= 'yes';
        	$query->query_vars['meta_key'] 		= '_virtual';
        endif;
	endif;
}

/**
 * Search by SKU or ID for products. Adapted from code by BenIrvin (Admin Search by ID)
 */
if (is_admin()) :
	add_action( 'parse_request', 'woocommerce_admin_product_search' );
	add_filter( 'get_search_query', 'woocommerce_admin_product_search_label' );
endif;

function woocommerce_admin_product_search( $wp ) {
    global $pagenow, $wpdb;
	
	if( 'edit.php' != $pagenow ) return;
	if( !isset( $wp->query_vars['s'] ) ) return;
	if ($wp->query_vars['post_type']!='product') return;

	if( '#' == substr( $wp->query_vars['s'], 0, 1 ) ) :

		$id = absint( substr( $wp->query_vars['s'], 1 ) );
			
		if( !$id ) return; 
		
		unset( $wp->query_vars['s'] );
		$wp->query_vars['p'] = $id;
		
	elseif( 'SKU:' == substr( $wp->query_vars['s'], 0, 4 ) ) :
		
		$sku = trim( substr( $wp->query_vars['s'], 4 ) );
			
		if( !$sku ) return; 
		
		$id = $wpdb->get_var('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key="_sku" AND meta_value LIKE "%'.$sku.'%";');
		
		if( !$id ) return; 

		unset( $wp->query_vars['s'] );
		$wp->query_vars['p'] = $id;
		$wp->query_vars['sku'] = $sku;
		
	endif;
}

function woocommerce_admin_product_search_label($query) {
	global $pagenow, $typenow, $wp;

    if ( 'edit.php' != $pagenow ) return $query;
    if ( $typenow!='product' ) return $query;
	
	$s = get_query_var( 's' );
	if ($s) return $query;
	
	$sku = get_query_var( 'sku' );
	if($sku) {
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		return sprintf(__("[%s with SKU of %s]", 'woocommerce'), $post_type->labels->singular_name, $sku);
	}
	
	$p = get_query_var( 'p' );
	if ($p) {
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		return sprintf(__("[%s with ID of %d]", 'woocommerce'), $post_type->labels->singular_name, $p);
	}
	
	return $query;
}