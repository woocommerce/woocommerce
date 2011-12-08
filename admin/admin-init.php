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
	include_once( 'post-types/post-types-init.php' );
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
	
	if ( current_user_can( 'manage_woocommerce' ) ) $menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce' );
	
    add_menu_page(__('WooCommerce', 'woothemes'), __('WooCommerce', 'woothemes'), 'manage_woocommerce', 'woocommerce' , 'woocommerce_settings', $woocommerce->plugin_url() . '/assets/images/icons/menu_icon_wc.png', 55);
    add_submenu_page('woocommerce', __('WooCommerce Settings', 'woothemes'),  __('Settings', 'woothemes') , 'manage_woocommerce', 'woocommerce', 'woocommerce_settings');
    add_submenu_page('woocommerce', __('Reports', 'woothemes'),  __('Reports', 'woothemes') , 'manage_woocommerce', 'woocommerce_reports', 'woocommerce_reports');
    add_submenu_page('edit.php?post_type=product', __('Attributes', 'woothemes'), __('Attributes', 'woothemes'), 'manage_categories', 'woocommerce_attributes', 'woocommerce_attributes');
    
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
	wp_register_script( 'chosen', $woocommerce->plugin_url() . '/assets/js/chosen.jquery'.$suffix.'.js', array('jquery'), '1.0' );
	
	// Get admin screen id
    $screen = get_current_screen();
    
    // WooCommerce admin pages
    if (in_array( $screen->id, array( 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_reports', 'edit-shop_order', 'edit-shop_coupon', 'shop_coupon', 'shop_order', 'edit-product', 'product' ))) :
    
    	wp_enqueue_script( 'woocommerce_admin' );
    	wp_enqueue_script('farbtastic');
    	wp_enqueue_script('chosen');
    	wp_enqueue_script('jquery-ui-sortable');

    endif;
    
    // Edit product category pages
    if (in_array( $screen->id, array('edit-product_cat') )) :
    
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		
	endif;

	// Product/Coupon/Orders
	if (in_array( $screen->id, array( 'shop_coupon', 'shop_order', 'product' ))) :
		
		global $post;
		
		wp_enqueue_script( 'woocommerce_writepanel' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script('chosen');
		
		$woocommerce_witepanel_params = array( 
			'remove_item_notice' 			=>  __("Remove this item? If you have previously reduced this item's stock, or this order was submitted by a customer, will need to manually restore the item's stock.", 'woothemes'),
			'cart_total' 					=> __("Calculate totals based on order items, discount amount, and shipping? Note, you will need to calculate discounts before tax manually.", 'woothemes'),
			'copy_billing' 					=> __("Copy billing information to shipping information? This will remove any currently entered shipping information.", 'woothemes'),
			'load_billing' 					=> __("Load the customer's billing information? This will remove any currently entered billing information.", 'woothemes'),
			'load_shipping' 				=> __("Load the customer's shipping information? This will remove any currently entered shipping information.", 'woothemes'),
			'prices_include_tax' 			=> get_option('woocommerce_prices_include_tax'),
			'round_at_subtotal'				=> get_option( 'woocommerce_tax_round_at_subtotal' ),
			'ID' 							=>  __('ID', 'woothemes'),
			'item_name' 					=> __('Item Name', 'woothemes'),
			'quantity' 						=> __('Quantity e.g. 2', 'woothemes'),
			'cost_unit' 					=> __('Cost per unit e.g. 2.99', 'woothemes'),
			'tax_rate' 						=> __('Tax Rate e.g. 20.0000', 'woothemes'),
			'meta_name'						=> __('Meta Name', 'woothemes'),
			'meta_value'					=> __('Meta Value', 'woothemes'),
			'select_terms'					=> __('Select terms', 'woothemes'),
			'no_customer_selected'			=> __('No customer selected', 'woothemes'),
			'plugin_url' 					=> $woocommerce->plugin_url(),
			'ajax_url' 						=> admin_url('admin-ajax.php'),
			'add_order_item_nonce' 			=> wp_create_nonce("add-order-item"),
			'get_customer_details_nonce' 	=> wp_create_nonce("get-customer-details"),
			'upsell_crosssell_search_products_nonce' => wp_create_nonce("search-products"),
			'calendar_image'				=> $woocommerce->plugin_url().'/assets/images/calendar.png',
			'post_id'						=> $post->ID
		 );
					 
		wp_localize_script( 'woocommerce_writepanel', 'woocommerce_writepanel_params', $woocommerce_witepanel_params );
		
	endif;
	
	// Term ordering - only when sorting by menu_order (our custom meta)
	if (($screen->id=='edit-product_cat' || strstr($screen->id, 'edit-pa_')) && !isset($_GET['orderby'])) :
		
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
	
	wp_enqueue_style('farbtastic');
	
	do_action('woocommerce_admin_css');
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
	if ( !current_user_can( 'manage_woocommerce' ) ) return false;
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
	
	if ( !current_user_can( 'manage_woocommerce' ) ) return false;
	?>
	<style type="text/css">
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
 * Prevent non-admin access to backend
 */
if (get_option('woocommerce_lock_down_admin')=='yes') add_action('admin_init', 'woocommerce_prevent_admin_access');

function woocommerce_prevent_admin_access() {
	
	if ( is_admin() && !is_ajax() && !current_user_can('edit_posts') ) :
		wp_safe_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
		exit;
	endif;
	
}

/**
 * Get customer details via ajax
 */
add_action('wp_ajax_woocommerce_get_customer_details', 'woocommerce_get_customer_details');

function woocommerce_get_customer_details() {
	
	global $woocommerce;

	check_ajax_referer( 'get-customer-details', 'security' );

	$user_id = (int) trim(stripslashes($_POST['user_id']));
	$type_to_load = esc_attr(trim(stripslashes($_POST['type_to_load'])));
	
	$customer_data = array(
		$type_to_load . '_first_name' => get_user_meta( $user_id, $type_to_load . '_first_name', true ),
		$type_to_load . '_last_name' => get_user_meta( $user_id, $type_to_load . '_last_name', true ),
		$type_to_load . '_company' => get_user_meta( $user_id, $type_to_load . '_company', true ),
		$type_to_load . '_address_1' => get_user_meta( $user_id, $type_to_load . '_address_1', true ),
		$type_to_load . '_address_2' => get_user_meta( $user_id, $type_to_load . '_address_2', true ),
		$type_to_load . '_city' => get_user_meta( $user_id, $type_to_load . '_city', true ),
		$type_to_load . '_postcode' => get_user_meta( $user_id, $type_to_load . '_postcode', true ),
		$type_to_load . '_country' => get_user_meta( $user_id, $type_to_load . '_country', true ),
		$type_to_load . '_state' => get_user_meta( $user_id, $type_to_load . '_state', true ),
		$type_to_load . '_email' => get_user_meta( $user_id, $type_to_load . '_email', true ),
		$type_to_load . '_phone' => get_user_meta( $user_id, $type_to_load . '_phone', true ),
	);
	
	echo json_encode($customer_data);
	
	// Quit out
	die();
	
}

/**
 * Add order item via ajax
 */
add_action('wp_ajax_woocommerce_add_order_item', 'woocommerce_add_order_item');

function woocommerce_add_order_item() {
	
	global $woocommerce;

	check_ajax_referer( 'add-order-item', 'security' );
	
	global $wpdb;
	
	$index = trim(stripslashes($_POST['index']));
	$item_to_add = trim(stripslashes($_POST['item_to_add']));
	
	$post = '';
	
	// Find the item
	if (is_numeric($item_to_add)) :
		$post = get_post( $item_to_add );
	endif;
	
	if (!$post || ($post->post_type!=='product' && $post->post_type!=='product_variation')) :
		$post_id = $wpdb->get_var($wpdb->prepare("
			SELECT post_id
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
			WHERE $wpdb->postmeta.meta_key = 'sku'
			AND $wpdb->posts.post_status = 'publish'
			AND $wpdb->posts.post_type = 'shop_product'
			AND $wpdb->postmeta.meta_value = %s
			LIMIT 1
		"), $item_to_add );
		$post = get_post( $post_id );
	endif;
	
	if (!$post || ($post->post_type!=='product' && $post->post_type!=='product_variation')) :
		die();
	endif;
	
	if ($post->post_type=="product") :
		$_product = &new woocommerce_product( $post->ID );
	else :
		$_product = &new woocommerce_product_variation( $post->ID );
	endif;
	?>
	<tr class="item" rel="<?php echo $index; ?>">
		<td class="product-id">
			<img class="tips" tip="<?php
				echo '<strong>'.__('Product ID:', 'woothemes').'</strong> '. $_product->id;
				echo '<br/><strong>'.__('Variation ID:', 'woothemes').'</strong> '; if ($_product->variation_id) echo $_product->variation_id; else echo '-';
				echo '<br/><strong>'.__('Product SKU:', 'woothemes').'</strong> '; if ($_product->sku) echo $_product->sku; else echo '-';
			?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/tip.png" />
		</td>
		<td class="sku"><?php if ($_product->sku) echo $_product->sku; else echo '-'; ?></td>
		<td class="name">
			<a href="<?php echo esc_url( admin_url('post.php?post='. $_product->id .'&action=edit') ); ?>"><?php echo $_product->get_title(); ?></a>
			<?php
				if (isset($_product->variation_data)) :
					echo '<br/>' . woocommerce_get_formatted_variation( $_product->variation_data, true );
				endif;
			?>
		</td>
		<td>
			<table class="meta" cellspacing="0">
				<tfoot>
					<tr>
						<td colspan="3"><button class="add_meta button"><?php _e('Add&nbsp;meta', 'woothemes'); ?></button></td>
					</tr>
				</tfoot>
				<tbody class="meta_items"></tbody>
			</table>
		</td>
		<?php do_action('woocommerce_admin_order_item_values', $_product); ?>
		<td class="quantity"><input type="text" name="item_quantity[<?php echo $index; ?>]" placeholder="<?php _e('0', 'woothemes'); ?>" value="1" /></td>
		<td class="cost"><input type="text" name="base_item_cost[<?php echo $index; ?>]" placeholder="<?php _e('0.00', 'woothemes'); ?>" value="<?php echo esc_attr( $_product->get_price_excluding_tax( false ) ); ?>" /></td>
		<td class="cost"><input type="text" name="item_cost[<?php echo $index; ?>]" placeholder="<?php _e('0.00', 'woothemes'); ?>" value="<?php echo esc_attr( $_product->get_price_excluding_tax( false ) ); ?>" /></td>
		<td class="tax"><input type="text" name="item_tax_rate[<?php echo $index; ?>]" placeholder="<?php _e('0.0000', 'woothemes'); ?>" value="<?php echo esc_attr( $_product->get_tax_base_rate() ); ?>" /></td>
		<td class="center">
			<input type="hidden" name="item_id[<?php echo $index; ?>]" value="<?php echo esc_attr( $_product->id ); ?>" />
			<input type="hidden" name="item_name[<?php echo $index; ?>]" value="<?php echo esc_attr( $_product->get_title() ); ?>" />
			<input type="hidden" name="item_variation[<?php echo $index; ?>]" value="<?php if (isset($_product->variation_id)) echo $_product->variation_id; ?>" />
			<button type="button" class="remove_row button">&times;</button>
		</td>
	</tr>
	<?php
	
	// Quit out
	die();
	
}

/**
 * Add order note via ajax
 */
add_action('wp_ajax_woocommerce_add_order_note', 'woocommerce_add_order_note');

function woocommerce_add_order_note() {
	
	global $woocommerce;

	check_ajax_referer( 'add-order-note', 'security' );
	
	$post_id 	= (int) $_POST['post_id'];
	$note		= strip_tags(woocommerce_clean($_POST['note']));
	$note_type	= $_POST['note_type'];
	
	$is_customer_note = ($note_type=='customer') ? 1 : 0;
	
	if ($post_id>0) :
		$order = &new woocommerce_order( $post_id );
		$comment_id = $order->add_order_note( $note, $is_customer_note );
		
		echo '<li rel="'.$comment_id.'" class="note ';
		if ($is_customer_note) echo 'customer-note';
		echo '"><div class="note_content">';
		echo wpautop(wptexturize($note));
		echo '</div><p class="meta">'. sprintf(__('added %s ago', 'woothemes'), human_time_diff(current_time('timestamp'))) .' - <a href="#" class="delete_note">'.__('Delete note', 'woothemes').'</a></p>';
		echo '</li>';
		
	endif;
	
	// Quit out
	die();
}

/**
 * Delete order note via ajax
 */
add_action('wp_ajax_woocommerce_delete_order_note', 'woocommerce_delete_order_note');

function woocommerce_delete_order_note() {
	
	global $woocommerce;

	check_ajax_referer( 'delete-order-note', 'security' );
	
	$note_id 	= (int) $_POST['note_id'];
	
	if ($note_id>0) :
		wp_delete_comment( $note_id );
	endif;
	
	// Quit out
	die();
}

/**
 * Search for products for upsells/crosssells
 */
add_action('wp_ajax_woocommerce_upsell_crosssell_search_products', 'woocommerce_upsell_crosssell_search_products');

function woocommerce_upsell_crosssell_search_products() {
	
	check_ajax_referer( 'search-products', 'security' );
	
	$search = (string) urldecode(stripslashes(strip_tags($_POST['search'])));
	$name = (string) urldecode(stripslashes(strip_tags($_POST['name'])));
	
	if (empty($search)) die();
	
	if (is_numeric($search)) :
		
		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => 15,
			'post__in' => array(0, $search)
		);
		
	else :
	
		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => 15,
			's' => $search
		);
	
	endif;
	
	$posts = get_posts( $args );
	
	if ($posts) : foreach ($posts as $post) : 
		
		$SKU = get_post_meta($post->ID, 'sku', true);
		
		?>
		<li rel="<?php echo $post->ID; ?>"><button type="button" name="Add" class="button add_crosssell" title="Add"><?php _e('Cross-sell', 'woothemes'); ?> &rarr;</button><button type="button" name="Add" class="button add_upsell" title="Add"><?php _e('Up-sell', 'woothemes'); ?> &rarr;</button><strong><?php echo $post->post_title; ?></strong> &ndash; #<?php echo $post->ID; ?> <?php if (isset($SKU) && $SKU) echo 'SKU: '.$SKU; ?><input type="hidden" class="product_id" value="0" /></li>
		<?php
						
	endforeach; else : 
	
		?><li><?php _e('No products found', 'woothemes'); ?></li><?php 
		
	endif; 
	
	die();
	
}

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
	
	if( !is_object( $post ) ) return;
	
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

/**
 * Directory for uploads
 */
add_filter('upload_dir', 'woocommerce_downloads_upload_dir');

function woocommerce_downloads_upload_dir( $pathdata ) {

	if (isset($_POST['type']) && $_POST['type'] == 'downloadable_product') :
		
		// Uploading a downloadable file
		$subdir = '/woocommerce_uploads'.$pathdata['subdir'];
	 	$pathdata['path'] = str_replace($pathdata['subdir'], $subdir, $pathdata['path']);
	 	$pathdata['url'] = str_replace($pathdata['subdir'], $subdir, $pathdata['url']);
		$pathdata['subdir'] = str_replace($pathdata['subdir'], $subdir, $pathdata['subdir']);
		return $pathdata;
		
	endif;
	
	return $pathdata;

}

add_action('media_upload_downloadable_product', 'woocommerce_media_upload_downloadable_product');

function woocommerce_media_upload_downloadable_product() {
	do_action('media_upload_file');
}

/**
 * Shortcode button in post editor
 **/
add_action( 'init', 'woocommerce_add_shortcode_button' );
add_filter( 'tiny_mce_version', 'woocommerce_refresh_mce' );

function woocommerce_add_shortcode_button() {
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) return;
	if ( get_user_option('rich_editing') == 'true') :
		add_filter('mce_external_plugins', 'woocommerce_add_shortcode_tinymce_plugin');
		add_filter('mce_buttons', 'woocommerce_register_shortcode_button');
	endif;
}

function woocommerce_register_shortcode_button($buttons) {
	array_push($buttons, "|", "woocommerce_shortcodes_button");
	return $buttons;
}

function woocommerce_add_shortcode_tinymce_plugin($plugin_array) {
	global $woocommerce;
	$plugin_array['WooCommerceShortcodes'] = $woocommerce->plugin_url() . '/assets/js/admin/editor_plugin.js';
	return $plugin_array;
}

function woocommerce_refresh_mce($ver) {
	$ver += 3;
	return $ver;
}