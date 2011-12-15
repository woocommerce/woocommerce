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

/**
 * Admin Menus
 * 
 * Sets up the admin menus in wordpress.
 */
add_action('admin_menu', 'woocommerce_admin_menu', 9);

function woocommerce_admin_menu() {
	global $menu, $woocommerce;
	
	if ( current_user_can( 'manage_woocommerce' ) ) $menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce' );
	
    add_menu_page(__('WooCommerce', 'woothemes'), __('WooCommerce', 'woothemes'), 'manage_woocommerce', 'woocommerce' , 'woocommerce_settings_page', $woocommerce->plugin_url() . '/assets/images/icons/menu_icon_wc.png', 55);
    add_submenu_page('woocommerce', __('WooCommerce Settings', 'woothemes'),  __('Settings', 'woothemes') , 'manage_woocommerce', 'woocommerce', 'woocommerce_settings_page');
    add_submenu_page('woocommerce', __('Reports', 'woothemes'),  __('Reports', 'woothemes') , 'manage_woocommerce', 'woocommerce_reports', 'woocommerce_reports_page');
    add_submenu_page('edit.php?post_type=product', __('Attributes', 'woothemes'), __('Attributes', 'woothemes'), 'manage_categories', 'woocommerce_attributes', 'woocommerce_attributes_page');
    
    $print_css_on = array( 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_reports', 'product_page_woocommerce_attributes', 'edit-tags.php', 'edit.php', 'index.php', 'post-new.php', 'post.php' );
    
    foreach ($print_css_on as $page) add_action( 'admin_print_styles-'. $page, 'woocommerce_admin_css' ); 
}

/**
 * Admin Includes - loaded conditionally
 */
add_action('admin_init', 'woocommerce_admin_init');

function woocommerce_admin_init() {
	global $pagenow;
	
	include_once( 'woocommerce-admin-hooks.php' );
	include_once( 'woocommerce-admin-functions.php' );

	if ( $pagenow=='index.php' ) :
		include_once( 'woocommerce-admin-dashboard.php' );
	elseif ( $pagenow=='import.php' ) :
		include_once( 'woocommerce-admin-import.php' );
	elseif ( $pagenow=='post-new.php' || $pagenow=='post.php' || $pagenow=='edit.php' ) :
		include_once( 'post-types/post-types-init.php' );
	elseif ( $pagenow=='edit-tags.php' ) :
		include_once( 'woocommerce-admin-taxonomies.php' );
	endif;
}

/**
 * Includes for admin pages - only load functions when needed
 */
function woocommerce_settings_page() {
	include_once( 'woocommerce-admin-settings-forms.php' );
	include_once( 'woocommerce-admin-settings.php' );
	woocommerce_settings();
}
function woocommerce_reports_page() {
	include_once( 'woocommerce-admin-reports.php' );
	woocommerce_reports();
}
function woocommerce_attributes_page() {
	include_once( 'woocommerce-admin-attributes.php' );
	woocommerce_attributes();
}

/**
 * Installation functions
 */
function activate_woocommerce() {
	include_once( 'woocommerce-admin-install.php' );
	update_option( "woocommerce_installed", 1 );
	update_option( 'skip_install_woocommerce_pages', 0 );
	do_install_woocommerce();
}
function install_woocommerce() {
	include_once( 'woocommerce-admin-install.php' );
	do_install_woocommerce();
}

/**
 * Admin Scripts
 */
function woocommerce_admin_scripts() {
	global $woocommerce, $pagenow, $post;
	
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

	if ($typenow=='post' && isset($_GET['post']) && !empty($_GET['post'])) :
		$typenow = $post->post_type;
	elseif (empty($typenow) && !empty($_GET['post'])) :
        $post = get_post($_GET['post']);
        $typenow = $post->post_type;
    endif;
		
	if ( $typenow == '' || $typenow=="product" || $typenow=="shop_order" || $typenow=="shop_coupon" ) :
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