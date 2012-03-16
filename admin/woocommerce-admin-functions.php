<?php
/**
 * WooCommerce Admin Functions
 * 
 * Hooked-in functions for WooCommerce related events in admin.
 *
 * @package		WooCommerce
 * @category	Actions
 * @author		WooThemes
 */
 
/**
 * Checks which method we're using to serve downloads
 * 
 * If using force or x-sendfile, this ensures the .htaccess is in place
 */
function woocomerce_check_download_folder_protection() {
	$upload_dir 		= wp_upload_dir();
	$downloads_url 		= $upload_dir['basedir'] . '/woocommerce_uploads';
	$download_method	= get_option('woocommerce_file_download_method');
	
	if ($download_method=='redirect') :
		
		// Redirect method - don't protect
		if (file_exists($downloads_url.'/.htaccess')) :
			unlink( $downloads_url . '/.htaccess' );
		endif;
		
		flush_rewrite_rules( true );
		
	else :
		
		// Force method - protect, add rules to the htaccess file
		if (!file_exists($downloads_url.'/.htaccess')) :
			if ($file_handle = @fopen( $downloads_url . '/.htaccess', 'w' )) :
				fwrite($file_handle, 'deny from all');
				fclose($file_handle);
			endif;
		endif;
		
		flush_rewrite_rules( true );
		
	endif;
} 

/**
 * Protect downlodas from ms-files.php in multisite
 */
function woocommerce_ms_protect_download_rewite_rules( $rewrite ) {
    global $wp_rewrite;
    
    $download_method	= get_option('woocommerce_file_download_method');
    
    if (!is_multisite() || $download_method=='redirect') return $rewrite;
	
	$rule  = "\n# WooCommerce Rules - Protect Files from ms-files.php\n\n";
	$rule .= "<IfModule mod_rewrite.c>\n";
	$rule .= "RewriteEngine On\n";
	$rule .= "RewriteCond %{QUERY_STRING} file=woocommerce_uploads/ [NC]\n";
	$rule .= "RewriteRule /ms-files.php$ - [F]\n";
	$rule .= "</IfModule>\n\n";

	return $rule . $rewrite;
}
 
/**
 * Deleting products sync
 * 
 * Removes variations etc belonging to a deleted post
 */
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
 * Preview Emails
 **/
function woocommerce_preview_emails() {
	if (isset($_GET['preview_woocommerce_mail'])) :
		$nonce = $_REQUEST['_wpnonce'];
		if (!wp_verify_nonce($nonce, 'preview-mail') ) die('Security check'); 
		
		global $woocommerce, $email_heading;
		
		$mailer = $woocommerce->mailer();
	
		$email_heading = __('Email preview', 'woocommerce');
		
		$message = '<h2>WooCommerce sit amet</h2>';
		
		$message.= wpautop('Ut ut est qui euismod parum. Dolor veniam tation nihil assum mazim. Possim fiant habent decima et claritatem. Erat me usus gothica laoreet consequat. Clari facer litterarum aliquam insitam dolor. 

Gothica minim lectores demonstraverunt ut soluta. Sequitur quam exerci veniam aliquip litterarum. Lius videntur nisl facilisis claritatem nunc. Praesent in iusto me tincidunt iusto. Dolore lectores sed putamus exerci est. ');
		
		echo $mailer->wrap_message( $email_heading, $message );
		
		exit;
		
	endif;
}

/**
 * Prevent non-admin access to backend
 */
function woocommerce_prevent_admin_access() {
	if ( get_option('woocommerce_lock_down_admin')=='yes' && !is_ajax() && !current_user_can('edit_posts') ) {
		wp_safe_redirect(get_permalink(woocommerce_get_page_id('myaccount')));
		exit;
	}
}

/**
 * Fix 'insert into post' buttons for images
 **/
function woocommerce_allow_img_insertion($vars) {
    $vars['send'] = true; // 'send' as in "Send to Editor"
    return($vars);
}

/**
 * Directory for uploads
 */
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
function woocommerce_media_upload_downloadable_product() {
	do_action('media_upload_file');
}

/**
 * Shortcode button in post editor
 **/
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

/**
 * Reorder categories on term insertion
 */
function woocommerce_create_term( $term_id, $tt_id, $taxonomy ) {
	
	if (!$taxonomy=='product_cat' && !strstr($taxonomy, 'pa_')) return;
	
	$next_id = null;
	
	$term = get_term($term_id, $taxonomy);
	
	// gets the sibling terms
	$siblings = get_terms($taxonomy, "parent={$term->parent}&menu_order=ASC&hide_empty=0");
	
	foreach ($siblings as $sibling) {
		if( $sibling->term_id == $term_id ) continue;
		$next_id =  $sibling->term_id; // first sibling term of the hierarchy level
		break;
	}

	// reorder
	woocommerce_order_terms( $term, $next_id, $taxonomy );
}

/**
 * Delete terms metas on deletion
 */
function woocommerce_delete_term( $term_id, $tt_id, $taxonomy ) {
	
	$term_id = (int) $term_id;
	
	if(!$term_id) return;
	
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->woocommerce_termmeta} WHERE `woocommerce_term_id` = " . $term_id);
	
}