<?php
/**
 * WooCommerce Admin
 * 
 * Main admin file which loads all settings panels and sets up admin menus.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

include_once( 'admin-install.php' );

function woocommerce_admin_init() {
	include_once( 'admin-settings-forms.php' );
	include_once( 'admin-settings.php' );
	include_once( 'admin-attributes.php' );
	include_once( 'admin-dashboard.php' );
	include_once( 'admin-import.php' );
	include_once( 'admin-post-types.php' );
	include_once( 'admin-reports.php' );
	include_once( 'admin-taxonomies.php' );
	include_once( 'writepanels/writepanels-init.php' );	
}
add_action('admin_init', 'woocommerce_admin_init');

/**
 * Admin Menus
 * 
 * Sets up the admin menus in wordpress.
 */
function woocommerce_admin_menu() {
	global $menu, $woocommerce;
	
	$menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce' );
	
    add_menu_page(__('WooCommerce'), __('WooCommerce'), 'manage_woocommerce', 'woocommerce' , 'woocommerce_settings', $woocommerce->plugin_url() . '/assets/images/icons/menu_icons.png', 55);
    add_submenu_page('woocommerce', __('General Settings', 'woothemes'),  __('Settings', 'woothemes') , 'manage_woocommerce', 'woocommerce', 'woocommerce_settings');
    add_submenu_page('woocommerce', __('Reports', 'woothemes'),  __('Reports', 'woothemes') , 'manage_woocommerce', 'woocommerce_reports', 'woocommerce_reports');
    add_submenu_page('edit.php?post_type=product', __('Attributes', 'woothemes'), __('Attributes', 'woothemes'), 'manage_woocommerce', 'woocommerce_attributes', 'woocommerce_attributes');
    
    $print_css_on = array( 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_reports', 'product_page_woocommerce_attributes', 'edit-tags.php', 'edit.php', 'index.php', 'post-new.php', 'post.php' );
    
    foreach ($print_css_on as $page) add_action( 'admin_print_styles-'. $page, 'woocommerce_admin_css' ); 
}
add_action('admin_menu', 'woocommerce_admin_menu', 9);

/**
 * Admin Scripts
 */
function woocommerce_admin_scripts() {
	global $woocommerce;
	
	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
	
	// Register scripts
	wp_register_script( 'woocommerce_admin', $woocommerce->plugin_url() . '/assets/js/admin/woocommerce_admin'.$suffix.'.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-core'), '1.0' );
	wp_register_script( 'jquery-ui-datepicker',  $woocommerce->plugin_url() . '/assets/js/admin/ui-datepicker.js', array('jquery','jquery-ui-core'), '1.0' );
	wp_register_script( 'woocommerce_writepanel', $woocommerce->plugin_url() . '/assets/js/admin/write-panels'.$suffix.'.js', array('jquery', 'jquery-ui-datepicker') );
	
	// Get admin screen id
    $screen = get_current_screen();
    
    // WooCommerce admin pages
    if (in_array( $screen->id, array( 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_reports', 'edit-shop_order', 'edit-shop_coupon', 'shop_coupon', 'shop_order', 'edit-product', 'product' ))) :
    
    	wp_enqueue_script( 'woocommerce_admin' );
    
    endif;
    
    // Edit product category pages
    if (in_array( $screen->id, array('edit-product_cat') )) :
    
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		
	endif;

	// Product/Coupon/Orders
	if (in_array( $screen->id, array( 'shop_coupon', 'shop_order', 'product' ))) :
		
		wp_enqueue_script( 'woocommerce_writepanel' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		
		$woocommerce_witepanel_params = array( 
			'remove_item_notice' 			=>  __("Remove this item? If you have previously reduced this item's stock, or this order was submitted by a customer, will need to manually restore the item's stock.", 'woothemes'),
			'cart_total' 					=> __("Calc totals based on order items, discount amount, and shipping?", 'woothemes'),
			'copy_billing' 					=> __("Copy billing information to shipping information? This will remove any currently entered shipping information.", 'woothemes'),
			'prices_include_tax' 			=> get_option('woocommerce_prices_include_tax'),
			'ID' 							=>  __('ID', 'woothemes'),
			'item_name' 					=> __('Item Name', 'woothemes'),
			'quantity' 						=> __('Quantity e.g. 2', 'woothemes'),
			'cost_unit' 					=> __('Cost per unit e.g. 2.99', 'woothemes'),
			'tax_rate' 						=> __('Tax Rate e.g. 20.0000', 'woothemes'),
			'meta_name'						=> __('Meta Name', 'woothemes'),
			'meta_value'					=> __('Meta Value', 'woothemes'),
			'select_terms'					=> __('Select terms', 'woothemes'),
			'plugin_url' 					=> $woocommerce->plugin_url(),
			'ajax_url' 						=> admin_url('admin-ajax.php'),
			'add_order_item_nonce' 			=> wp_create_nonce("add-order-item"),
			'upsell_crosssell_search_products_nonce' => wp_create_nonce("search-products"),
			'calendar_image'				=> $woocommerce->plugin_url().'/assets/images/calendar.png'
		 );
					 
		wp_localize_script( 'woocommerce_writepanel', 'woocommerce_writepanel_params', $woocommerce_witepanel_params );
		
	endif;
	
	// Term ordering
	if ($screen->id=='edit-product_cat' || strstr($screen->id, 'edit-pa_')) :
		
		wp_register_script( 'woocommerce_term_ordering', $woocommerce->plugin_url() . '/assets/js/admin/term-ordering.js', array('jquery-ui-sortable') );
		wp_enqueue_script( 'woocommerce_term_ordering' );
		
		$taxonomy = (isset($_GET['taxonomy'])) ? $_GET['taxonomy'] : '';
		
		$woocommerce_term_order_params = array( 
			'taxonomy' 			=>  $taxonomy
		 );
					 
		wp_localize_script( 'woocommerce_term_ordering', 'woocommerce_term_ordering_params', $woocommerce_term_order_params );
		
	endif;

	// Reports pages
    if ($screen->id=='woocommerce_page_woocommerce_reports') :

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'flot', $woocommerce->plugin_url() . '/assets/js/admin/jquery.flot'.$suffix.'.js', 'jquery', '1.0' );
		wp_enqueue_script( 'flot-resize', $woocommerce->plugin_url() . '/assets/js/admin/jquery.flot.resize'.$suffix.'.js', array('jquery', 'flot'), '1.0' );
	
	endif;
}
add_action('admin_enqueue_scripts', 'woocommerce_admin_scripts');


/**
 * Queue admin CSS
 */
function woocommerce_admin_css() {
	global $woocommerce, $typenow, $post;

	if ($typenow=='post' && isset($_GET['post']) && !empty($_GET['post'])) $typenow = $post->post_type;
	
	if ( $typenow=='' || $typenow=="product" || $typenow=="shop_order" || $typenow=="shop_coupon" ) :
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
		wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
	endif;
}

/**
 * Order admin menus
 */
function woocommerce_admin_menu_order( $menu_order ) {

	// Initialize our custom order array
	$woocommerce_menu_order = array();

	// Get the index of our custom separator
	$woocommerce_separator = array_search( 'separator-woocommerce', $menu_order );
	
	// Get index of product menu
	$woocommerce_product = array_search( 'edit.php?post_type=product', $menu_order );

	// Loop through menu order and do some rearranging
	foreach ( $menu_order as $index => $item ) :

		if ( ( ( 'woocommerce' ) == $item ) ) :
			$woocommerce_menu_order[] = 'separator-woocommerce';
			$woocommerce_menu_order[] = $item;
			$woocommerce_menu_order[] = 'edit.php?post_type=product';
			unset( $menu_order[$woocommerce_separator] );
			unset( $menu_order[$woocommerce_product] );
		elseif ( !in_array( $item, array( 'separator-woocommerce' ) ) ) :
			$woocommerce_menu_order[] = $item;
		endif;

	endforeach;
	
	// Return order
	return $woocommerce_menu_order;
}
add_action('menu_order', 'woocommerce_admin_menu_order');

function woocommerce_admin_custom_menu_order() {
	if ( !current_user_can( 'manage_options' ) ) return false;
	return true;
}
add_action('custom_menu_order', 'woocommerce_admin_custom_menu_order');

/**
 * Admin Head
 * 
 * Outputs some styles in the admin <head> to show icons on the woocommerce admin pages
 */
function woocommerce_admin_head() {
	global $woocommerce;
	?>
	<style type="text/css">
		#toplevel_page_woocommerce .wp-menu-image{background:url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/icons/menu_icons.png) no-repeat 0px -32px !important;}
		#toplevel_page_woocommerce .wp-menu-image img{display:none;}
		#toplevel_page_woocommerce:hover .wp-menu-image,#toplevel_page_woocommerce.wp-has-current-submenu .wp-menu-image{background:url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/icons/menu_icons.png) no-repeat 0px 0px !important;}
		#menu-posts-product .wp-menu-image{background:url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/icons/menu_icons.png) no-repeat -35px -32px !important;}
		#menu-posts-product:hover .wp-menu-image,#menu-posts-product.wp-has-current-submenu .wp-menu-image{background:url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/icons/menu_icons.png) no-repeat -35px 0px !important;}
		#menu-posts-shop_order .wp-menu-image{background:url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/icons/menu_icons.png) no-repeat -70px -32px !important;}
		#menu-posts-shop_order:hover .wp-menu-image,#menu-posts-shop_order.wp-has-current-submenu .wp-menu-image{background:url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/icons/menu_icons.png) no-repeat -70px 0px !important;}
		
		<?php if ( isset($_GET['taxonomy']) && $_GET['taxonomy']=='product_cat' ) : ?>
			.icon32-posts-product { background-position: -243px -5px !important; }
		<?php elseif ( isset($_GET['taxonomy']) && $_GET['taxonomy']=='product_tag' ) : ?>
			.icon32-posts-product { background-position: -301px -5px !important; }
		<?php endif; ?>
	</style>
	<?php
}
add_action('admin_head', 'woocommerce_admin_head');

/**
 * Ajax request handling for categories ordering
 */
function woocommerce_term_ordering() {
	global $wpdb;
	
	$id = (int) $_POST['id'];
	$next_id  = isset($_POST['nextid']) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
	$taxonomy = isset($_POST['thetaxonomy']) ? esc_attr( $_POST['thetaxonomy'] ) : null;
	$term = get_term_by('id', $id, $taxonomy);
	
	if( !$id || !$term || !$taxonomy ) die(0);
	
	woocommerce_order_terms( $term, $next_id, $taxonomy );
	
	$children = get_terms($taxonomy, "child_of=$id&menu_order=ASC&hide_empty=0");
	
	if( $term && sizeof($children) ) {
		echo 'children';
		die;	
	}
}
add_action('wp_ajax_woocommerce-term-ordering', 'woocommerce_term_ordering');

/**
 * Search by SKU or ID for products. Adapted from code by BenIrvin (Admin Search by ID)
 */
if (is_admin()) :
	add_action('parse_request', 'woocommerce_admin_product_search');
	add_filter( 'get_search_query', 'woocommerce_admin_id_search_label' );
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
		
		$id = $wpdb->get_var('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key="sku" AND meta_value LIKE "%'.$sku.'%";');
		
		if( !$id ) return; 

		unset( $wp->query_vars['s'] );
		$wp->query_vars['p'] = $id;
		$wp->query_vars['sku'] = $sku;
		
	endif;
}

function woocommerce_admin_id_search_label($query) {
	global $pagenow;

    if( 'edit.php' != $pagenow ) return;
	
	$s =  get_query_var( 's' );
	if($s) return $query;
	
	$sku = get_query_var( 'sku' );
	if($sku) {
		global $wp;
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		
		return sprintf(__("[%s with SKU of %s]", 'woothemes'), $post_type->labels->singular_name, $sku);
	}
	
	$p = get_query_var( 'p' );
	if($p) {
		global $wp;
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		
		return sprintf(__("[%s with ID of %d]", 'woothemes'), $post_type->labels->singular_name, $p);
	}
	
	return $query;
}

add_filter('query_vars', 'woocommerce_add_sku_var');
function woocommerce_add_sku_var($public_query_vars) {
	$public_query_vars[] = 'sku';
	return $public_query_vars;
}



/**
 * Duplicate a product action
 *
 * Based on 'Duplicate Post' (http://www.lopo.it/duplicate-post-plugin/) by Enrico Battocchi
 */
add_action('admin_action_duplicate_product', 'woocommerce_duplicate_product_action');

function woocommerce_duplicate_product_action() {

	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'duplicate_post_save_as_new_page' == $_REQUEST['action'] ) ) ) {
		wp_die(__('No product to duplicate has been supplied!', 'woothemes'));
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
		wp_die(__('Product creation failed, could not find original product:', 'woothemes') . ' ' . $id);
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
	
	$actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=duplicate_product&amp;post=' . $post->ID ), 'woocommerce-duplicate-product_' . $post->ID ) . '" title="' . __("Make a duplicate from this product", 'woothemes')
		. '" rel="permalink">' .  __("Duplicate", 'woothemes') . '</a>';

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
	
	if ($post->post_type!='product') return;
	
	if ( isset( $_GET['post'] ) ) :
		$notifyUrl = wp_nonce_url( admin_url( "admin.php?action=duplicate_product&post=" . $_GET['post'] ), 'woocommerce-duplicate-product_' . $_GET['post'] );
		?>
		<div id="duplicate-action"><a class="submitduplicate duplication"
			href="<?php echo esc_url( $notifyUrl ); ?>"><?php _e('Copy to a new draft', 'woothemes'); ?></a>
		</div>
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
		$suffix 			= __(" (Copy)", 'woothemes');
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
 * Deleting products sync
 * 
 * Removes variations etc belonging to a deleted post
 */
add_action('delete_post', 'woocommerce_delete_product_sync', 10);

function woocommerce_delete_product_sync( $id ) {
	
	if (!current_user_can('delete_posts')) return;
	
	if ( $id > 0 ) :
	
		if ( $children_products =& get_children( 'post_parent='.$id.'&post_type=product_variation' ) ) :
	
			if ($children_products) :
			
				foreach ($children_products as $child) :
					
					wp_delete_post( $child->ID, true );
					
				endforeach;
			
			endif;
	
		endif;
	
	endif;
	
}