<?php
/**
 * WooCommerce Hooks
 * 
 * Action/filter hooks used for WooCommerce functions/templates
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */
global $woocommerce;

/** Template Hooks ********************************************************/

if ( !is_admin() || defined('DOING_AJAX') ) {

	/* Content Wrappers */
	add_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	add_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	
	/* Sale flashes */
	add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	
	/* Breadcrumbs */
	add_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
	
	/* Sidebar */
	add_action( 'get_sidebar', 'woocommerce_prevent_sidebar_cache' );
	add_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	
	/* Products Loop */
	add_action( 'woocommerce_before_shop_loop', 'woocommerce_show_messages', 10 );
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
	add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
	add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
	
	/* Subcategories */
	add_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10);
	
	/* Before Single Products */
	add_action( 'woocommerce_before_single_product', 'woocommerce_show_messages', 10 );
	
	/* Before Single Products Summary Div */
	add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
	add_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
	
	/* After Single Products Summary Div */
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
	
	/* Product Summary Box */
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
	
	/* After Single Products */
	add_action('woocommerce_after_single_product', 'woocommerce_upsell_display');
	
	/* Product Add to cart */
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
	add_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 ); 
	add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 ); 
	add_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 ); 
	add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
	
	/* Pagination in loop-shop */
	add_action( 'woocommerce_pagination', 'woocommerce_pagination', 10 );
	add_action( 'woocommerce_pagination', 'woocommerce_catalog_ordering', 20 );
	
	/* Product page tabs */
	add_action( 'woocommerce_product_tabs', 'woocommerce_product_description_tab', 10 );
	add_action( 'woocommerce_product_tabs', 'woocommerce_product_attributes_tab', 20 );
	add_action( 'woocommerce_product_tabs', 'woocommerce_product_reviews_tab', 30 );
	
	add_action( 'woocommerce_product_tab_panels', 'woocommerce_product_description_panel', 10 );
	add_action( 'woocommerce_product_tab_panels', 'woocommerce_product_attributes_panel', 20 );
	add_action( 'woocommerce_product_tab_panels', 'woocommerce_product_reviews_panel', 30 );
	
	/* Checkout */
	add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
	add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
	
	/* Cart */
	add_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
	
	/* Footer */
	add_action( 'wp_footer', 'woocommerce_demo_store' );
	
	/* Order details */
	add_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
	add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

}

/** Store Event Hooks *****************************************************/

/* Shop Page Handling and Support */
add_action( 'template_redirect', 'woocommerce_redirects' );
add_filter( 'wp_nav_menu_objects',  'woocommerce_nav_menu_item_classes', 2, 20 );
add_filter( 'wp_list_pages', 'woocommerce_list_pages' );

/* Logout link */
add_filter( 'wp_nav_menu_items', 'woocommerce_nav_menu_items', 10, 2 );

/* Clear the cart */
if (get_option('woocommerce_clear_cart_on_logout')=='yes') add_action( 'wp_logout', 'woocommerce_empty_cart' );
add_action( 'get_header', 'woocommerce_clear_cart_after_payment' );

/* Disable admin bar */
add_action( 'init', 'woocommerce_disable_admin_bar' );

/* Catalog sorting/ordering */
add_action( 'init', 'woocommerce_update_catalog_ordering' );

/* Cart Actions */
add_action( 'init', 'woocommerce_update_cart_action' );
add_action( 'init', 'woocommerce_add_to_cart_action' );
add_action( 'wp_login', 'woocommerce_load_persistent_cart', 1, 2);

/* Checkout Actions */
add_action( 'init', 'woocommerce_checkout_action', 10 );
add_action( 'init', 'woocommerce_pay_action', 10 );

/* Login and Registration */
add_action( 'init', 'woocommerce_process_login' );
add_action( 'init', 'woocommerce_process_registration' );

/* Product Downloads */
add_action('init', 'woocommerce_download_product');

/* Analytics */
add_action( 'woocommerce_thankyou', 'woocommerce_ecommerce_tracking_piwik' );

/* RSS Feeds */
add_action( 'wp_head', 'woocommerce_products_rss_feed' );

/* Order actions */
add_action( 'init', 'woocommerce_cancel_order' );
add_action( 'init', 'woocommerce_order_again' );

/* Star Ratings */
add_action( 'comment_post', 'woocommerce_add_comment_rating', 1 );
add_filter( 'preprocess_comment', 'woocommerce_check_comment_rating', 0 );

/* Text filters */
add_filter( 'woocommerce_short_description', 'wptexturize'        );
add_filter( 'woocommerce_short_description', 'convert_smilies'    );
add_filter( 'woocommerce_short_description', 'convert_chars'      );
add_filter( 'woocommerce_short_description', 'wpautop'            );
add_filter( 'woocommerce_short_description', 'shortcode_unautop'  );
add_filter( 'woocommerce_short_description', 'prepend_attachment' );
add_filter( 'woocommerce_short_description', 'do_shortcode', 11 ); // AFTER wpautop()
