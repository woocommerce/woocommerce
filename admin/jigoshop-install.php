<?php
/**
 * JigoShop Install
 * 
 * Plugin install script which adds default pages, taxonomies, and database tables
 *
 * @author 		Jigowatt
 * @category 	Admin
 * @package 	JigoShop
 */

/**
 * Install jigoshop
 * 
 * Calls each function to install bits, and clears the cron jobs and rewrite rules
 *
 * @since 		1.0
 */
function install_jigoshop() {
	
	// Get options and define post types before we start
	require_once ( 'jigoshop-admin-settings-options.php' );	
	jigoshop_post_type();
	
	// Do install
	jigoshop_default_options();
	jigoshop_create_pages();
	jigoshop_tables_install();
	
	jigoshop_post_type();
	jigoshop_default_taxonomies();
	
	// Clear cron
	wp_clear_scheduled_hook('jigoshop_update_sale_prices_schedule_check');
	update_option('jigoshop_update_sale_prices', 'no');
	
	// Flush Rules
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
	
	// Update version
	update_option( "jigoshop_db_version", JIGOSHOP_VERSION );
}

/**
 * Default options
 * 
 * Sets up the default options used on the settings page
 *
 * @since 		1.0
 */
function jigoshop_default_options() {
	global $options_settings;
	foreach ($options_settings as $value) {
        if (isset($value['std'])) add_option($value['id'], $value['std']);
    }
    
    add_option('jigoshop_shop_slug', 'shop');
}

/**
 * Create pages
 * 
 * Creates pages that the plugin relies on, storing page id's in variables.
 *
 * @since 		1.0
 */
function jigoshop_create_pages() {
    global $wpdb;
	
    $slug = esc_sql( _x('shop', 'page_slug', 'jigoshop') );
	$page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Shop', 'jigoshop'),
	        'post_content' => '',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('jigoshop_shop_page_id', $page_id);

    } else {
    	update_option('jigoshop_shop_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('cart', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Cart', 'jigoshop'),
	        'post_content' => '[jigoshop_cart]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('jigoshop_cart_page_id', $page_id);

    } else {
    	update_option('jigoshop_cart_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('checkout', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Checkout', 'jigoshop'),
	        'post_content' => '[jigoshop_checkout]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('jigoshop_checkout_page_id', $page_id);

    } else {
    	update_option('jigoshop_checkout_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('order-tracking', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Track your order', 'jigoshop'),
	        'post_content' => '[jigoshop_order_tracking]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
    } 
    
    $slug = esc_sql( _x('my-account', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('My Account', 'jigoshop'),
	        'post_content' => '[jigoshop_my_account]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('jigoshop_myaccount_page_id', $page_id);

    } else {
    	update_option('jigoshop_myaccount_page_id', $page_found);
    } 
    
    $slug = esc_sql( _x('edit-address', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('jigoshop_myaccount_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Edit My Address', 'jigoshop'),
	        'post_content' => '[jigoshop_edit_address]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('jigoshop_edit_address_page_id', $page_id);

    } else {
    	update_option('jigoshop_edit_address_page_id', $page_found);
    } 
    
    $slug = esc_sql( _x('view-order', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('jigoshop_myaccount_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('View Order', 'jigoshop'),
	        'post_content' => '[jigoshop_view_order]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('jigoshop_view_order_page_id', $page_id);

    } else {
    	update_option('jigoshop_view_order_page_id', $page_found);
    } 
    
    $slug = esc_sql( _x('change-password', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('jigoshop_myaccount_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Change Password', 'jigoshop'),
	        'post_content' => '[jigoshop_change_password]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        
        update_option('jigoshop_change_password_page_id', $page_id);

    } else {
    	update_option('jigoshop_change_password_page_id', $page_found);
    }
    
    $slug = esc_sql( _x('pay', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

    if(!$page_found) {

        $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('jigoshop_checkout_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Checkout &rarr; Pay', 'jigoshop'),
	        'post_content' => '[jigoshop_pay]',
	        'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        update_option('jigoshop_pay_page_id', $page_id);

    } else {
    	update_option('jigoshop_pay_page_id', $page_found);
    }
    
    // Thank you Page
    $slug = esc_sql( _x('thanks', 'page_slug', 'jigoshop') );
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1");

	if(!$page_found) {
	
	    $page_data = array(
	        'post_status' => 'publish',
	        'post_type' => 'page',
	        'post_parent' => get_option('jigoshop_checkout_page_id'),
	        'post_author' => 1,
	        'post_name' => $slug,
	        'post_title' => __('Thank you', 'jigoshop'),
	        'post_content' => '[jigoshop_thankyou]',
	        'comment_status' => 'closed'
	    );
	    $page_id = wp_insert_post($page_data);
	
	    update_option('jigoshop_thanks_page_id', $page_id);
	
	} else {
		update_option('jigoshop_thanks_page_id', $page_found);
	}
    
}

/**
 * Table Install
 * 
 * Sets up the database tables which the plugin needs to function.
 *
 * @since 		1.0
 */
function jigoshop_tables_install() {
	global $wpdb;
	
	//$wpdb->show_errors();
	
    $collate = '';
    if($wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "jigoshop_attribute_taxonomies" ." (
        `attribute_id` 			mediumint(9) NOT NULL AUTO_INCREMENT,
        `attribute_name`		varchar(200) NOT NULL,
        `attribute_type`		varchar(200) NOT NULL,
        PRIMARY KEY id (`attribute_id`)) $collate;";
    $wpdb->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "jigoshop_downloadable_product_permissions" ." (
        `product_id` 			mediumint(9) NOT NULL,
        `user_email`			varchar(200) NOT NULL,
        `user_id`				mediumint(9) NULL,
        `order_key`				varchar(200) NOT NULL,
        `downloads_remaining`	varchar(9) NULL,
        PRIMARY KEY id (`product_id`, `order_key`)) $collate;";
    $wpdb->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "jigoshop_termmeta" ." (
		`meta_id` 				bigint(20) NOT NULL AUTO_INCREMENT,
      	`jigoshop_term_id` 		bigint(20) NOT NULL,
      	`meta_key` 				varchar(255) NULL,
      	`meta_value` 			longtext NULL,
      	PRIMARY KEY id (`meta_id`)) $collate;";
    $wpdb->query($sql);	

}

/**
 * Default taxonomies
 * 
 * Adds the default terms for taxonomies - product types and order statuses. Modify at your own risk.
 *
 * @since 		1.0
 */
function jigoshop_default_taxonomies() {
	
	$product_types = array(
		'simple',
		'grouped',
		'configurable',
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