<?php
/**
 * JigoShop Admin
 * 
 * Main admin file which loads all settings panels and sets up the menus.
 *
 * @author 		Jigowatt
 * @category 	Admin
 * @package 	JigoShop
 */



require_once ( 'jigoshop-install.php' );

require_once ( 'jigoshop-admin-dashboard.php' );
require_once ( 'jigoshop-write-panels.php' );
require_once ( 'jigoshop-admin-settings.php' );
require_once ( 'jigoshop-admin-attributes.php' );
require_once ( 'jigoshop-admin-post-types.php' );
	

function jigoshop_admin_init () {
	require_once ( 'jigoshop-admin-settings-options.php' );	
}
add_action('admin_init', 'jigoshop_admin_init');

/**
 * Admin Menus
 * 
 * Sets up the admin menus in wordpress.
 *
 * @since 		1.0
 */
function jigoshop_admin_menu() {
	global $menu;
	
	$menu[] = array( '', 'read', 'separator-jigoshop', '', 'wp-menu-separator' );
	
    add_menu_page(__('Jigoshop'), __('Jigoshop'), 'manage_options', 'jigoshop' , 'jigoshop_dashboard', jigoshop::plugin_url() . '/assets/images/icons/menu_icons.png', 56);
    add_submenu_page('jigoshop', __('Dashboard', 'jigoshop'), __('Dashboard', 'jigoshop'), 'manage_options', 'jigoshop', 'jigoshop_dashboard'); 
    add_submenu_page('jigoshop', __('General Settings', 'jigoshop'),  __('Settings', 'jigoshop') , 'manage_options', 'settings', 'jigoshop_settings');
    add_submenu_page('jigoshop', __('System Info','jigoshop'), __('System Info','jigoshop'), 'manage_options', 'sysinfo', 'jigoshop_system_info');
    add_submenu_page('edit.php?post_type=product', __('Attributes','jigoshop'), __('Attributes','jigoshop'), 'manage_options', 'attributes', 'jigoshop_attributes');
}

function jigoshop_admin_menu_order( $menu_order ) {

	// Initialize our custom order array
	$jigoshop_menu_order = array();

	// Get the index of our custom separator
	$jigoshop_separator = array_search( 'separator-jigoshop', $menu_order );

	// Loop through menu order and do some rearranging
	foreach ( $menu_order as $index => $item ) :

		if ( ( ( 'jigoshop' ) == $item ) ) :
			$jigoshop_menu_order[] = 'separator-jigoshop';
			unset( $menu_order[$jigoshop_separator] );
		endif;

		if ( !in_array( $item, array( 'separator-jigoshop' ) ) ) :
			$jigoshop_menu_order[] = $item;
		endif;

	endforeach;
	
	// Return order
	return $jigoshop_menu_order;
}

function jigoshop_admin_custom_menu_order() {
	if ( !current_user_can( 'manage_options' ) ) return false;

	return true;
}
add_action('admin_menu', 'jigoshop_admin_menu');
add_action('menu_order', 'jigoshop_admin_menu_order');
add_action('custom_menu_order', 'jigoshop_admin_custom_menu_order');

/**
 * Admin Head
 * 
 * Outputs some styles in the admin <head> to show icons on the jigoshop admin pages
 *
 * @since 		1.0
 */
function jigoshop_admin_head() {
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
add_action('admin_head', 'jigoshop_admin_head');

/**
 * System info
 * 
 * Shows the system info panel which contains version data and debug info
 *
 * @since 		1.0
 * @usedby 		jigoshop_settings()
 */
function jigoshop_system_info() {
	?>
	<div class="wrap jigoshop">
		<div class="icon32 icon32-jigoshop-debug" id="icon-jigoshop"><br/></div>
	    <h2><?php _e('System Information','jigoshop') ?></h2>
		<div id="tabs-wrap">
			<ul class="tabs">
				<li><a href="#versions"><?php _e('Environment', 'jigoshop'); ?></a></li>
				<li><a href="#debugging"><?php _e('Debugging', 'jigoshop'); ?></a></li>
			</ul>
			<div id="versions" class="panel">
				<table class="widefat fixed" style="width:850px;">
		            <thead>		            
		            	<tr>
		                    <th scope="col" width="200px"><?php _e('Versions','jigoshop')?></th>
		                    <th scope="col">&nbsp;</th>
		                </tr>
		           	</thead>
		           	<tbody>
		                <tr>
		                    <td class="titledesc"><?php _e('Jigoshop Version','jigoshop')?></td>
		                    <td class="forminp"><?php echo jigoshop::get_var('version'); ?></td>
		                </tr>
		                <tr>
		                    <td class="titledesc"><?php _e('WordPress Version','jigoshop')?></td>
		                    <td class="forminp"><?php if (is_multisite()) echo 'WPMU'; else echo 'WP'; ?> <?php echo bloginfo('version'); ?></td>
		                </tr>
		            </tbody>
		            <thead>
		                <tr>
		                    <th scope="col" width="200px"><?php _e('Server','jigoshop')?></th>
		                    <th scope="col">&nbsp;</th>
		                </tr>
		            </thead>
		           	<tbody>
		                <tr>
		                    <td class="titledesc"><?php _e('PHP Version','jigoshop')?></td>
		                    <td class="forminp"><?php if(function_exists('phpversion')) echo phpversion(); ?></td>
		                </tr>
		                <tr>
		                    <td class="titledesc"><?php _e('Server Software','jigoshop')?></td>
		                    <td class="forminp"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
		                </tr>
		        	</tbody>
		        </table>
			</div>
			<div id="debugging" class="panel">
				<table class="widefat fixed" style="width:850px;">
		            <tbody>
		            	<tr>
		                    <th scope="col" width="200px"><?php _e('Debug Information','jigoshop')?></th>
		                    <th scope="col">&nbsp;</th>
		                </tr>
		                <tr>
		                    <td class="titledesc"><?php _e('UPLOAD_MAX_FILESIZE','jigoshop')?></td>
		                    <td class="forminp"><?php 
		                    	if(function_exists('phpversion')) echo (jigoshop_let_to_num(ini_get('upload_max_filesize'))/(1024*1024))."MB";
		                    ?></td>
		                </tr>
		                <tr>
		                    <td class="titledesc"><?php _e('POST_MAX_SIZE','jigoshop')?></td>
		                    <td class="forminp"><?php 
		                    	if(function_exists('phpversion')) echo (jigoshop_let_to_num(ini_get('post_max_size'))/(1024*1024))."MB";
		                    ?></td>
		                </tr>
		                <tr>
		                    <td class="titledesc"><?php _e('WordPress Memory Limit','jigoshop')?></td>
		                    <td class="forminp"><?php 
		                    	echo (jigoshop_let_to_num(WP_MEMORY_LIMIT)/(1024*1024))."MB";
		                    ?></td>
		                </tr>
		                 <tr>
		                    <td class="titledesc"><?php _e('WP_DEBUG','jigoshop')?></td>
		                    <td class="forminp"><?php if (WP_DEBUG) echo __('On', 'jigoshop'); else __('Off', 'jigoshop'); ?></td>
		                </tr>
		                <tr>
		                    <td class="titledesc"><?php _e('DISPLAY_ERRORS','jigoshop')?></td>
		                    <td class="forminp"><?php if(function_exists('phpversion')) echo ini_get('display_errors'); ?></td>
		                </tr>
		                <tr>
		                    <td class="titledesc"><?php _e('FSOCKOPEN','jigoshop')?></td>
		                    <td class="forminp"><?php if(function_exists('fsockopen')) echo '<span style="color:green">' . __('Your server supports fsockopen.', 'jigoshop'). '</span>'; else echo '<span style="color:red">' . __('Your server does not support fsockopen.', 'jigoshop'). '</span>'; ?></td>
		                </tr>
		        	</tbody>
		        </table>
			</div>
		</div> 
    </div>
    <script type="text/javascript">
	jQuery(function() {
	    // Tabs
		jQuery('ul.tabs').show();
		jQuery('ul.tabs li:first').addClass('active');
		jQuery('div.panel:not(div.panel:first)').hide();
		jQuery('ul.tabs a').click(function(){
			jQuery('ul.tabs li').removeClass('active');
			jQuery(this).parent().addClass('active');
			jQuery('div.panel').hide();
			jQuery( jQuery(this).attr('href') ).show();
			return false;
		});
	});
	</script>
	<?php
}

function jigoshop_feature_product () {

	if( !is_admin() ) die;
	
	if( !current_user_can('edit_posts') ) wp_die( __('You do not have sufficient permissions to access this page.') );
	
	if( !check_admin_referer()) wp_die( __('You have taken too long. Please go back and retry.', 'jigoshop') );
	
	$post_id = isset($_GET['product_id']) && (int)$_GET['product_id'] ? (int)$_GET['product_id'] : '';
	
	if(!$post_id) die;
	
	$post = get_post($post_id);
	if(!$post) die;
	
	if($post->post_type !== 'product') die;
	
	$product = new jigoshop_product($post->ID);

	if ($product->is_featured()) update_post_meta($post->ID, 'featured', 'no');
	else update_post_meta($post->ID, 'featured', 'yes');
	
	$sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
	wp_safe_redirect( $sendback );

}
add_action('wp_ajax_jigoshop-feature-product', 'jigoshop_feature_product');

/**
 * Returns proper post_type
 */
function jigoshop_get_current_post_type() {
        
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
 * Categories ordering
 */

/**
 * Load needed scripts to order categories
 */
function jigoshop_categories_scripts () {
	
	if( !isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'product_cat') return;
	
	wp_register_script('jigoshop-categories-ordering', jigoshop::plugin_url() . '/assets/js/categories-ordering.js', array('jquery-ui-sortable'));
	wp_print_scripts('jigoshop-categories-ordering');
	
}
add_action('admin_footer-edit-tags.php', 'jigoshop_categories_scripts');

/**
 * Ajax request handling for categories ordering
 */
function jigoshop_categories_ordering () {

	global $wpdb;
	
	$id = (int)$_POST['id'];
	$next_id  = isset($_POST['nextid']) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
	
	if( ! $id || ! $term = get_term_by('id', $id, 'product_cat') ) die(0);
	
	jigoshop_order_categories ( $term, $next_id);
	
	$children = get_terms('product_cat', "child_of=$id&menu_order=ASC&hide_empty=0");
	if( $term && sizeof($children) ) {
		echo 'children';
		die;	
	}
	
}
add_action('wp_ajax_jigoshop-categories-ordering', 'jigoshop_categories_ordering');
