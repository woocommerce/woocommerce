<?php
/**
 * WooCommerce Install
 * 
 * Plugin install script which adds default pages, taxonomies, and database tables
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

/**
 * Install woocommerce
 */
function install_woocommerce() {
	
	global $woocommerce_settings;
	
	// Include settings so that we can run through defaults
	include_once( 'admin-settings.php' );
	
	// Do install
	woocommerce_default_options();
	woocommerce_create_pages();
	woocommerce_tables_install();
	woocommerce_default_taxonomies();
	
	// Update version
	update_option( "woocommerce_db_version", WOOCOMMERCE_VERSION );
	
	// Flag installed so we can redirect
	update_option( "woocommerce_installed", 1 );
}

/**
 * Install woocommerce redirect
 */
add_action('admin_init', 'install_woocommerce_redirect');
function install_woocommerce_redirect() {
	if (get_option('woocommerce_installed')==1) :
		update_option( "woocommerce_installed", 0 );
		flush_rewrite_rules( false );
		wp_redirect(admin_url('admin.php?page=woocommerce&installed=true'));
		exit;
	endif;
}


/**
 * Default options
 * 
 * Sets up the default options used on the settings page
 */
function woocommerce_default_options() {
	global $woocommerce_settings;
	foreach ($woocommerce_settings as $value) {
	
        if (isset($value['std'])) :
        
        	if ($value['type']=='image_width') :
        		
        		add_option($value['id'].'_width', $value['std']);
        		add_option($value['id'].'_height', $value['std']);
        		
        	else :
        
        		add_option($value['id'], $value['std']);
        	
        	endif;
        	
        endif;
        
    }
    
    add_option('woocommerce_shop_slug', 'shop');
}

/**
 * Create pages
 * 
 * Creates pages that the plugin relies on, storing page id's in variables.
 */
function woocommerce_create_pages() {
    global $wpdb;
	
    $slug = esc_sql( _x('shop', 'page_slug', 'woothemes') );
	$page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Shop', 'woothemes'),
	        'post_content' => '',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('woocommerce_shop_page_id', $page_id);

    } else {
    	update_option('woocommerce_shop_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('cart', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Cart', 'woothemes'),
	        'post_content' => '[woocommerce_cart]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('woocommerce_cart_page_id', $page_id);

    } else {
    	update_option('woocommerce_cart_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('checkout', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Checkout', 'woothemes'),
	        'post_content' => '[woocommerce_checkout]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('woocommerce_checkout_page_id', $page_id);

    } else {
    	update_option('woocommerce_checkout_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('order-tracking', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Track your order', 'woothemes'),
	        'post_content' => '[woocommerce_order_tracking]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
    } 
    
    $slug = esc_sql( _x('my-account', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('My Account', 'woothemes'),
	        'post_content' => '[woocommerce_my_account]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('woocommerce_myaccount_page_id', $page_id);

    } else {
    	update_option('woocommerce_myaccount_page_id', $page_found);
    } 
    
    $slug = esc_sql( _x('edit-address', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('woocommerce_myaccount_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Edit My Address', 'woothemes'),
	        'post_content' => '[woocommerce_edit_address]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('woocommerce_edit_address_page_id', $page_id);

    } else {
    	update_option('woocommerce_edit_address_page_id', $page_found);
    } 
    
    $slug = esc_sql( _x('view-order', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('woocommerce_myaccount_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('View Order', 'woothemes'),
	        'post_content' => '[woocommerce_view_order]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('woocommerce_view_order_page_id', $page_id);

    } else {
    	update_option('woocommerce_view_order_page_id', $page_found);
    } 
    
    $slug = esc_sql( _x('change-password', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('woocommerce_myaccount_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Change Password', 'woothemes'),
	        'post_content' => '[woocommerce_change_password]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('woocommerce_change_password_page_id', $page_id);

    } else {
    	update_option('woocommerce_change_password_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('pay', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('woocommerce_checkout_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Checkout &rarr; Pay', 'woothemes'),
	        'post_content' => '[woocommerce_pay]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('woocommerce_pay_page_id', $page_id);

    } else {
    	update_option('woocommerce_pay_page_id', $page_found);
    }
    
    // Thank you Page
    $slug = esc_sql( _x('thanks', 'page_slug', 'woothemes') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

	if(!$page_found) {
	
	    $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('woocommerce_checkout_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Thank you', 'woothemes'),
	        'post_content' => '[woocommerce_thankyou]',
	        'comment_status' => 'closed'
	    );
	    $page_id = wp_insert_post($page_data);
	
	    update_option('woocommerce_thanks_page_id', $page_id);
	
	} else {
		update_option('woocommerce_thanks_page_id', $page_found);
	}
    
}

/**
 * Table Install
 * 
 * Sets up the database tables which the plugin needs to function.
 */
function woocommerce_tables_install() {
	global $wpdb;
	
	$collate = '';
    if($wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "woocommerce_attribute_taxonomies" ." (
        `attribute_id` 			mediumint(9) NOT NULL AUTO_INCREMENT,
        `attribute_name`		varchar(200) NOT NULL,
        `attribute_label`		longtext NULL,
        `attribute_type`		varchar(200) NOT NULL,
        PRIMARY KEY id (`attribute_id`)) $collate;";
    $wpdb->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "woocommerce_downloadable_product_permissions" ." (
        `product_id` 			mediumint(9) NOT NULL,
        `user_email`			varchar(200) NOT NULL,
        `user_id`				mediumint(9) NULL,
        `order_key`				varchar(200) NOT NULL,
        `downloads_remaining`	varchar(9) NULL,
        PRIMARY KEY id (`product_id`, `order_key`)) $collate;";
    $wpdb->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "woocommerce_termmeta" ." (
		`meta_id` 				bigint(20) NOT NULL AUTO_INCREMENT,
      	`woocommerce_term_id` 		bigint(20) NOT NULL,
      	`meta_key` 				varchar(255) NULL,
      	`meta_value` 			longtext NULL,
      	PRIMARY KEY id (`meta_id`)) $collate;";
    $wpdb->query($sql);	

}

/**
 * Default taxonomies
 * 
 * Adds the default terms for taxonomies - product types and order statuses. Modify at your own risk.
 */
function woocommerce_default_taxonomies() {
	
	$product_types = array(
		'simple',
		'grouped',
		'variable',
		'downloadable',
		'virtual'
	);
	
	foreach($product_types as $type) {
		if (!$type_id = get_term_by( 'slug', sanitize_title($type), 'product_type')) {
			wp_insert_term($type, 'product_type');
		}
	}
	
	$order_status = array(
		'pending',
		'on-hold',
		'processing',
		'completed',
		'refunded',
		'cancelled'
	);
	
	foreach($order_status as $status) {
		if (!$status_id = get_term_by( 'slug', sanitize_title($status), 'shop_order_status')) {
			wp_insert_term($status, 'shop_order_status');
		}
	}
	
}