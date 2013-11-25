<?php
/**
 * WooCommerce Template Hooks
 *
 * Action/filter hooks used for WooCommerce functions/templates
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Templates
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'the_post', 'wc_setup_product_data' );
add_action( 'template_redirect', 'wc_template_redirect' );
add_filter( 'body_class', 'wc_body_class' );
add_filter( 'post_class', 'wc_product_post_class', 20, 3 );

/**
 * Content Wrappers
 *
 * @see wc_output_content_wrapper()
 * @see wc_output_content_wrapper_end()
 */
add_action( 'woocommerce_before_main_content', 'wc_output_content_wrapper', 10 );
add_action( 'woocommerce_after_main_content', 'wc_output_content_wrapper_end', 10 );

/**
 * Sale flashes
 *
 * @see wc_show_product_loop_sale_flash()
 * @see wc_show_product_sale_flash()
 */
add_action( 'woocommerce_before_shop_loop_item_title', 'wc_show_product_loop_sale_flash', 10 );
add_action( 'woocommerce_before_single_product_summary', 'wc_show_product_sale_flash', 10 );

/**
 * Breadcrumbs
 *
 * @see wc_breadcrumb()
 */
add_action( 'woocommerce_before_main_content', 'wc_breadcrumb', 20, 0 );

/**
 * Sidebar
 *
 * @see wc_get_sidebar()
 */
add_action( 'woocommerce_sidebar', 'wc_get_sidebar', 10 );

/**
 * Archive descriptions
 *
 * @see wc_taxonomy_archive_description()
 * @see wc_product_archive_description()
 */
add_action( 'woocommerce_archive_description', 'wc_taxonomy_archive_description', 10 );
add_action( 'woocommerce_archive_description', 'wc_product_archive_description', 10 );

/**
 * Products Loop
 *
 * @see wc_result_count()
 * @see wc_catalog_ordering()
 * @see wc_reset_loop()
 */
add_action( 'woocommerce_before_shop_loop', 'wc_result_count', 20 );
add_action( 'woocommerce_before_shop_loop', 'wc_catalog_ordering', 30 );
add_filter( 'loop_end', 'wc_reset_loop' );

/**
 * Product Loop Items
 *
 * @see wc_template_loop_add_to_cart()
 * @see wc_template_loop_product_thumbnail()
 * @see wc_template_loop_price()
 * @see wc_template_loop_rating()
 */
add_action( 'woocommerce_after_shop_loop_item', 'wc_template_loop_add_to_cart', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'wc_template_loop_product_thumbnail', 10 );
add_action( 'woocommerce_after_shop_loop_item_title', 'wc_template_loop_price', 10 );
add_action( 'woocommerce_after_shop_loop_item_title', 'wc_template_loop_rating', 5 );

/**
 * Subcategories
 *
 * @see wc_subcategory_thumbnail()
 */
add_action( 'woocommerce_before_subcategory_title', 'wc_subcategory_thumbnail', 10 );

/**
 * Before Single Products Summary Div
 *
 * @see wc_show_product_images()
 * @see wc_show_product_thumbnails()
 */
add_action( 'woocommerce_before_single_product_summary', 'wc_show_product_images', 20 );
add_action( 'woocommerce_product_thumbnails', 'wc_show_product_thumbnails', 20 );

/**
 * After Single Products Summary Div
 *
 * @see wc_output_product_data_tabs()
 * @see wc_upsell_display()
 * @see wc_output_related_products()
 */
add_action( 'woocommerce_after_single_product_summary', 'wc_output_product_data_tabs', 10 );
add_action( 'woocommerce_after_single_product_summary', 'wc_upsell_display', 15 );
add_action( 'woocommerce_after_single_product_summary', 'wc_output_related_products', 20 );

/**
 * Product Summary Box
 *
 * @see wc_template_single_title()
 * @see wc_template_single_price()
 * @see wc_template_single_excerpt()
 * @see wc_template_single_meta()
 * @see wc_template_single_sharing()
 */
add_action( 'woocommerce_single_product_summary', 'wc_template_single_title', 5 );
add_action( 'woocommerce_single_product_summary', 'wc_template_single_rating', 10 );
add_action( 'woocommerce_single_product_summary', 'wc_template_single_price', 10 );
add_action( 'woocommerce_single_product_summary', 'wc_template_single_excerpt', 20 );
add_action( 'woocommerce_single_product_summary', 'wc_template_single_meta', 40 );
add_action( 'woocommerce_single_product_summary', 'wc_template_single_sharing', 50 );

/**
 * Product Add to cart
 *
 * @see wc_template_single_add_to_cart()
 * @see wc_simple_add_to_cart()
 * @see wc_grouped_add_to_cart()
 * @see wc_variable_add_to_cart()
 * @see wc_external_add_to_cart()
 */
add_action( 'woocommerce_single_product_summary', 'wc_template_single_add_to_cart', 30 );
add_action( 'wc_simple_add_to_cart', 'wc_simple_add_to_cart', 30 );
add_action( 'wc_grouped_add_to_cart', 'wc_grouped_add_to_cart', 30 );
add_action( 'wc_variable_add_to_cart', 'wc_variable_add_to_cart', 30 );
add_action( 'wc_external_add_to_cart', 'wc_external_add_to_cart', 30 );

/**
 * Pagination after shop loops
 *
 * @see woocommerce_pagination()
 */
add_action( 'woocommerce_after_shop_loop', 'wc_pagination', 10 );

/**
 * Product page tabs
 */
add_filter( 'woocommerce_product_tabs', 'wc_default_product_tabs' );
add_filter( 'woocommerce_product_tabs', 'wc_sort_product_tabs', 99 );

/**
 * Checkout
 *
 * @see wc_checkout_login_form()
 * @see wc_checkout_coupon_form()
 * @see wc_order_review()
 */
add_action( 'woocommerce_before_checkout_form', 'wc_checkout_login_form', 10 );
add_action( 'woocommerce_before_checkout_form', 'wc_checkout_coupon_form', 10 );
add_action( 'woocommerce_checkout_order_review', 'wc_order_review', 10 );

/**
 * Cart
 *
 * @see wc_cross_sell_display()
 */
add_action( 'woocommerce_cart_collaterals', 'wc_cross_sell_display' );

/**
 * Header
 *
 * @see wc_products_rss_feed()
 * @see wc_generator_tag()
 */
add_action( 'wp_head', 'wc_products_rss_feed' );
add_action( 'wp_head', 'wc_generator_tag' );

/**
 * Footer
 *
 * @see woocommerce_demo_store()
 * @see wc_print_js()
 */
add_action( 'wp_footer', 'wc_demo_store' );
add_action( 'wp_footer', 'wc_print_js', 25 );

/**
 * Order details
 *
 * @see wc_order_details_table()
 * @see wc_order_details_table()
 */
add_action( 'woocommerce_view_order', 'wc_order_details_table', 10 );
add_action( 'woocommerce_thankyou', 'wc_order_details_table', 10 );
add_action( 'woocommerce_order_details_after_order_table', 'wc_order_again_button' );
