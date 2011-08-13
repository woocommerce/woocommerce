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

include_once( 'admin-settings.php' );
include_once( 'admin-install.php' );

function woocommerce_admin_init() {
	include_once( 'admin-attributes.php' );
	include_once( 'admin-dashboard.php' );
	include_once( 'admin-import.php' );
	include_once( 'admin-post-types.php' );
	include_once( 'writepanels/writepanels-init.php' );	
}
add_action('admin_init', 'woocommerce_admin_init');

/**
 * Admin Menus
 * 
 * Sets up the admin menus in wordpress.
 *
 * @since 		1.0
 */
function woocommerce_admin_menu() {
	global $menu;
	
	$menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator' );
	
    add_menu_page(__('WooCommerce'), __('WooCommerce'), 'manage_woocommerce', 'woocommerce' , 'woocommerce_dashboard', woocommerce::plugin_url() . '/assets/images/icons/menu_icons.png', 56);
    add_submenu_page('woocommerce', __('Dashboard', 'woothemes'), __('Dashboard', 'woothemes'), 'manage_woocommerce', 'woocommerce', 'woocommerce_dashboard'); 
    add_submenu_page('woocommerce', __('General Settings', 'woothemes'),  __('Settings', 'woothemes') , 'manage_woocommerce', 'woocommerce-settings', 'woocommerce_settings');
    add_submenu_page('woocommerce', __('Coupons', 'woothemes'),  __('Coupons', 'woothemes') , 'manage_woocommerce', 'woocommerce-coupons', 'woocommerce_coupons');
    add_submenu_page('woocommerce', __('System Info', 'woothemes'), __('System Info', 'woothemes'), 'manage_options', 'sysinfo', 'woocommerce_system_info');
    add_submenu_page('edit.php?post_type=product', __('Attributes', 'woothemes'), __('Attributes', 'woothemes'), 'manage_woocommerce', 'attributes', 'woocommerce_attributes');
}

function woocommerce_admin_menu_order( $menu_order ) {

	// Initialize our custom order array
	$woocommerce_menu_order = array();

	// Get the index of our custom separator
	$woocommerce_separator = array_search( 'separator-woocommerce', $menu_order );

	// Loop through menu order and do some rearranging
	foreach ( $menu_order as $index => $item ) :

		if ( ( ( 'woocommerce' ) == $item ) ) :
			$woocommerce_menu_order[] = 'separator-woocommerce';
			unset( $menu_order[$woocommerce_separator] );
		endif;

		if ( !in_array( $item, array( 'separator-woocommerce' ) ) ) :
			$woocommerce_menu_order[] = $item;
		endif;

	endforeach;
	
	// Return order
	return $woocommerce_menu_order;
}

function woocommerce_admin_custom_menu_order() {
	if ( !current_user_can( 'manage_options' ) ) return false;

	return true;
}
add_action('admin_menu', 'woocommerce_admin_menu');
add_action('menu_order', 'woocommerce_admin_menu_order');
add_action('custom_menu_order', 'woocommerce_admin_custom_menu_order');

/**
 * Admin Head
 * 
 * Outputs some styles in the admin <head> to show icons on the woocommerce admin pages
 */
function woocommerce_admin_head() {
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
 * System info
 * 
 * Shows the system info panel which contains version data and debug info
 */
function woocommerce_system_info() {
	?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-debug" id="icon-woocommerce"><br/></div>
		<h2><?php _e('System Information', 'woothemes')?></h2>
		<br class="clear" />
		<table class="widefat fixed">
            <thead>		            
            	<tr>
                    <th scope="col" width="200px"><?php _e('Versions', 'woothemes')?></th>
                    <th scope="col">&nbsp;</th>
                </tr>
           	</thead>
           	<tbody>
                <tr>
                    <td class="titledesc"><?php _e('WooCommerce Version', 'woothemes')?></td>
                    <td class="forminp"><?php echo woocommerce::get_var('version'); ?></td>
                </tr>
                <tr>
                    <td class="titledesc"><?php _e('WordPress Version', 'woothemes')?></td>
                    <td class="forminp"><?php if (is_multisite()) echo 'WPMU'; else echo 'WP'; ?> <?php echo bloginfo('version'); ?></td>
                </tr>
            </tbody>
            <thead>
                <tr>
                    <th scope="col" width="200px"><?php _e('Server', 'woothemes')?></th>
                    <th scope="col">&nbsp;</th>
                </tr>
            </thead>
           	<tbody>
                <tr>
                    <td class="titledesc"><?php _e('PHP Version', 'woothemes')?></td>
                    <td class="forminp"><?php if(function_exists('phpversion')) echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td class="titledesc"><?php _e('Server Software', 'woothemes')?></td>
                    <td class="forminp"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                </tr>
        	</tbody>
			<thead>		            
            	<tr>
                    <th scope="col" width="200px"><?php _e('Debug Information', 'woothemes')?></th>
                    <th scope="col">&nbsp;</th>
                </tr>
           	</thead>
            <tbody>
                <tr>
                    <td class="titledesc"><?php _e('UPLOAD_MAX_FILESIZE', 'woothemes')?></td>
                    <td class="forminp"><?php 
                    	if(function_exists('phpversion')) echo (woocommerce_let_to_num(ini_get('upload_max_filesize'))/(1024*1024))."MB";
                    ?></td>
                </tr>
                <tr>
                    <td class="titledesc"><?php _e('POST_MAX_SIZE', 'woothemes')?></td>
                    <td class="forminp"><?php 
                    	if(function_exists('phpversion')) echo (woocommerce_let_to_num(ini_get('post_max_size'))/(1024*1024))."MB";
                    ?></td>
                </tr>
                <tr>
                    <td class="titledesc"><?php _e('WordPress Memory Limit', 'woothemes')?></td>
                    <td class="forminp"><?php 
                    	echo (woocommerce_let_to_num(WP_MEMORY_LIMIT)/(1024*1024))."MB";
                    ?></td>
                </tr>
                 <tr>
                    <td class="titledesc"><?php _e('WP_DEBUG', 'woothemes')?></td>
                    <td class="forminp"><?php if (WP_DEBUG) echo __('On', 'woothemes'); else __('Off', 'woothemes'); ?></td>
                </tr>
                <tr>
                    <td class="titledesc"><?php _e('DISPLAY_ERRORS', 'woothemes')?></td>
                    <td class="forminp"><?php if(function_exists('phpversion')) echo ini_get('display_errors'); ?></td>
                </tr>
                <tr>
                    <td class="titledesc"><?php _e('FSOCKOPEN', 'woothemes')?></td>
                    <td class="forminp"><?php if(function_exists('fsockopen')) echo '<span style="color:green">' . __('Your server supports fsockopen.', 'woothemes'). '</span>'; else echo '<span style="color:red">' . __('Your server does not support fsockopen.', 'woothemes'). '</span>'; ?></td>
                </tr>
        	</tbody>
        </table>
	</div> 
	<?php
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

/**
 * Returns proper post_type
 */
function woocommerce_get_current_post_type() {
        
	global $post, $typenow, $current_screen;
         
    if( $current_screen && @$current_screen->post_type ) return $current_screen->post_type;
    
    if( $typenow ) return $typenow;
        
    if( !empty($_REQUEST['post_type']) ) return sanitize_key( $_REQUEST['post_type'] );
    
    if ( !empty($post) && !empty($post->post_type) ) return $post->post_type;
         
    if( ! empty($_REQUEST['post']) && (int)$_REQUEST['post'] ) {
    	$p = get_post( $_REQUEST['post'] );
    	return $p ? $p->post_type : '';
    }
    
    return '';
}

/**
 * Categories ordering scripts
 */
function woocommerce_categories_scripts () {
	
	if( !isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'product_cat') return;
	
	wp_register_script('woocommerce-categories-ordering', woocommerce::plugin_url() . '/assets/js/categories-ordering.js', array('jquery-ui-sortable'));
	wp_print_scripts('woocommerce-categories-ordering');
	
}
add_action('admin_footer-edit-tags.php', 'woocommerce_categories_scripts');

/**
 * Ajax request handling for categories ordering
 */
function woocommerce_categories_ordering() {

	global $wpdb;
	
	$id = (int)$_POST['id'];
	$next_id  = isset($_POST['nextid']) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
	
	if( ! $id || ! $term = get_term_by('id', $id, 'product_cat') ) die(0);
	
	woocommerce_order_categories( $term, $next_id );
	
	$children = get_terms('product_cat', "child_of=$id&menu_order=ASC&hide_empty=0");
	if( $term && sizeof($children) ) {
		echo 'children';
		die;	
	}
	
}
add_action('wp_ajax_woocommerce-categories-ordering', 'woocommerce_categories_ordering');
