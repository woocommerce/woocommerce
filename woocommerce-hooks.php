<?php
/**
 * WooCommerce Hooks
 *
 * Action/filter hooks used for WooCommerce functions/templates
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

/** Template Hooks ********************************************************/

if ( ! is_admin() || defined('DOING_AJAX') ) {

	/**
	 * Content Wrappers
	 *
	 * @see woocommerce_output_content_wrapper()
	 * @see woocommerce_output_content_wrapper_end()
	 */
	add_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	add_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

	/**
	 * Sale flashes
	 *
	 * @see woocommerce_show_product_loop_sale_flash()
	 * @see woocommerce_show_product_sale_flash()
	 */
	add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

	/**
	 * Breadcrumbs
	 *
	 * @see woocommerce_breadcrumb()
	 */
	add_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );

	/**
	 * Sidebar
	 *
	 * @see woocommerce_get_sidebar()
	 */
	add_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	/**
	 * Archive descriptions
	 *
	 * @see woocommerce_taxonomy_archive_description()
	 * @see woocommerce_product_archive_description()
	 */
	add_action( 'woocommerce_taxonomy_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	add_action( 'woocommerce_product_archive_description', 'woocommerce_product_archive_description', 10 );

	/**
	 * Products Loop
	 *
	 * @see woocommerce_reset_loop()
	 * @see woocommerce_show_messages()
	 * @see woocommerce_template_loop_add_to_cart()
	 * @see woocommerce_template_loop_product_thumbnail()
	 * @see woocommerce_template_loop_price()
	 */
	add_filter( 'loop_end', 'woocommerce_reset_loop' );
	add_action( 'woocommerce_before_shop_loop', 'woocommerce_show_messages', 10 );
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

	/**
	 * Subcategories
	 *
	 * @see woocommerce_subcategory_thumbnail()
	 */
	add_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10 );

	/**
	 * Before Single Products
	 *
	 * @see woocommerce_show_messages()
	 */
	add_action( 'woocommerce_before_single_product', 'woocommerce_show_messages', 10 );

	/**
	 * Before Single Products Summary Div
	 *
	 * @see woocommerce_show_product_images()
	 * @see woocommerce_show_product_thumbnails()
	 */
	add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
	add_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );

	/**
	 * After Single Products Summary Div
	 *
	 * @see woocommerce_output_product_data_tabs()
	  * @see woocommerce_upsell_display()
	 * @see woocommerce_output_related_products()
	 */
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

	/**
	 * Product Summary Box
	 *
	 * @see woocommerce_template_single_title()
	 * @see woocommerce_template_single_price()
	 * @see woocommerce_template_single_excerpt()
	 * @see woocommerce_template_single_meta()
	 * @see woocommerce_template_single_sharing()
	 */
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );


	/**
	 * Product Add to cart
	 *
	 * @see woocommerce_template_single_add_to_cart()
	 * @see woocommerce_simple_add_to_cart()
	 * @see woocommerce_grouped_add_to_cart()
	 * @see woocommerce_variable_add_to_cart()
	 * @see woocommerce_external_add_to_cart()
	 */
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	add_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
	add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
	add_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
	add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );

	/**
	 * Pagination in loop-shop
	 *
	 * @see woocommerce_pagination()
	 * @see woocommerce_catalog_ordering()
	 */
	add_action( 'woocommerce_pagination', 'woocommerce_pagination', 10 );
	add_action( 'woocommerce_pagination', 'woocommerce_catalog_ordering', 20 );

	/**
	 * Product page tabs
	 *
	 * @see woocommerce_product_description_tab()
	 * @see woocommerce_product_attributes_tab()
	 * @see woocommerce_product_reviews_tab()
	 * @see woocommerce_product_description_panel()
	 * @see woocommerce_product_attributes_panel()
	 * @see woocommerce_product_reviews_panel()
	 */
	add_action( 'woocommerce_product_tabs', 'woocommerce_product_description_tab', 10 );
	add_action( 'woocommerce_product_tabs', 'woocommerce_product_attributes_tab', 20 );
	add_action( 'woocommerce_product_tabs', 'woocommerce_product_reviews_tab', 30 );
	add_action( 'woocommerce_product_tab_panels', 'woocommerce_product_description_panel', 10 );
	add_action( 'woocommerce_product_tab_panels', 'woocommerce_product_attributes_panel', 20 );
	add_action( 'woocommerce_product_tab_panels', 'woocommerce_product_reviews_panel', 30 );

	/**
	 * Checkout
	 *
	 * @see woocommerce_checkout_login_form()
	 * @see woocommerce_checkout_coupon_form()
	 * @see woocommerce_order_review()
	 */
	add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
	add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );

	/**
	 * Cart
	 *
	 * @see woocommerce_cross_sell_display()
	 */
	add_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );

	/**
	 * Footer
	 *
	 * @see woocommerce_demo_store()
	 */
	add_action( 'wp_footer', 'woocommerce_demo_store' );

	/**
	 * Order details
	 *
	 * @see woocommerce_order_details_table()
	 * @see woocommerce_order_details_table()
	 */
	add_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
	add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
}

/** Store Event Hooks *****************************************************/

/**
 * Shop Page Handling and Support
 *
 * @see woocommerce_redirects()
 * @see woocommerce_nav_menu_item_classes()
 * @see woocommerce_list_pages()
 */
add_action( 'template_redirect', 'woocommerce_redirects' );
add_filter( 'wp_nav_menu_objects',  'woocommerce_nav_menu_item_classes', 2, 20 );
add_filter( 'wp_list_pages', 'woocommerce_list_pages' );

/**
 * Logout link
 *
 * @see woocommerce_nav_menu_items()
 */
add_filter( 'wp_nav_menu_items', 'woocommerce_nav_menu_items', 10, 2 );

/**
 * Clear the cart
 *
 * @see woocommerce_empty_cart()
 * @see woocommerce_clear_cart_after_payment()
 */
if ( get_option( 'woocommerce_clear_cart_on_logout' ) == 'yes' )
	add_action( 'wp_logout', 'woocommerce_empty_cart' );
add_action( 'get_header', 'woocommerce_clear_cart_after_payment' );

/**
 * Disable admin bar
 *
 * @see woocommerce_disable_admin_bar()
 */
add_filter( 'show_admin_bar', 'woocommerce_disable_admin_bar', 10, 1 );

/**
 * Catalog sorting/ordering
 *
 * @see woocommerce_update_catalog_ordering()
 */
add_action( 'init', 'woocommerce_update_catalog_ordering' );

/**
 * Cart Actions
 *
 * @see woocommerce_update_cart_action()
 * @see woocommerce_add_to_cart_action()
 * @see woocommerce_load_persistent_cart()
 */
add_action( 'init', 'woocommerce_update_cart_action' );
add_action( 'init', 'woocommerce_add_to_cart_action' );
add_action( 'wp_login', 'woocommerce_load_persistent_cart', 1, 2 );

/**
 * Checkout Actions
 *
 * @see woocommerce_checkout_action()
 * @see woocommerce_pay_action()
 */
add_action( 'init', 'woocommerce_checkout_action', 10 );
add_action( 'init', 'woocommerce_pay_action', 10 );

/**
 * Login and Registration
 *
 * @see woocommerce_process_login()
 * @see woocommerce_process_registration()
 */
add_action( 'init', 'woocommerce_process_login' );
add_action( 'init', 'woocommerce_process_registration' );

/**
 * Product Downloads
 *
 * @see woocommerce_download_product()
 */
add_action('init', 'woocommerce_download_product');

/**
 * Analytics
 *
 * @see woocommerce_ecommerce_tracking_piwik()
 */
add_action( 'woocommerce_thankyou', 'woocommerce_ecommerce_tracking_piwik' );

/**
 * RSS Feeds
 *
 * @see woocommerce_products_rss_feed()
 */
add_action( 'wp_head', 'woocommerce_products_rss_feed' );

/**
 * Order actions
 *
 * @see woocommerce_cancel_order()
 * @see woocommerce_order_again()
 */
add_action( 'init', 'woocommerce_cancel_order' );
add_action( 'init', 'woocommerce_order_again' );

/**
 * Star Ratings
 *
 * @see woocommerce_add_comment_rating()
 * @see woocommerce_check_comment_rating()
 */
add_action( 'comment_post', 'woocommerce_add_comment_rating', 1 );
add_filter( 'preprocess_comment', 'woocommerce_check_comment_rating', 0 );

/**
 * Text filters
 */
add_filter( 'woocommerce_short_description', 'wptexturize'        );
add_filter( 'woocommerce_short_description', 'convert_smilies'    );
add_filter( 'woocommerce_short_description', 'convert_chars'      );
add_filter( 'woocommerce_short_description', 'wpautop'            );
add_filter( 'woocommerce_short_description', 'shortcode_unautop'  );
add_filter( 'woocommerce_short_description', 'prepend_attachment' );
add_filter( 'woocommerce_short_description', 'do_shortcode', 11 ); // AFTER wpautop()