<?php
/**
 * WooCommerce Admin Hooks
 * 
 * Action/filter hooks used for WooCommerce functions
 *
 * @package		WooCommerce
 * @category	Admin
 * @author		WooThemes
 */
global $woocommerce;

/** Events ****************************************************************/

add_action('delete_post', 'woocommerce_delete_product_sync', 10);
add_action('admin_init', 'woocommerce_preview_emails');
add_action('admin_init', 'woocommerce_prevent_admin_access');
add_action('admin_init', 'install_woocommerce_redirect');
add_action('woocommerce_settings_saved', 'woocomerce_check_download_folder_protection');

/** Filters ***************************************************************/

add_filter('get_media_item_args', 'woocommerce_allow_img_insertion');

/** File Uploads **********************************************************/

add_filter('upload_dir', 'woocommerce_downloads_upload_dir');
add_action('media_upload_downloadable_product', 'woocommerce_media_upload_downloadable_product');

/** Shortcode buttons *****************************************************/

add_action( 'init', 'woocommerce_add_shortcode_button' );
add_filter( 'tiny_mce_version', 'woocommerce_refresh_mce' );

/** Category/Term ordering ************************************************/

add_action("create_term", 'woocommerce_create_term', 5, 3);
add_action("delete_product_term", 'woocommerce_delete_term', 5, 3);