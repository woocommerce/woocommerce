<?php
/**
 * WooCommerce Template Actions
 * 
 * Actions used in the template files to output content.
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */

/* Content Wrappers */
add_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
add_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

/* Sale flashes */
add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_sale_flash', 10, 2);
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10, 2);

/* Breadcrumbs */
add_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);

/* Sidebar */
add_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

/* Products Loop */
add_action( 'woocommerce_before_shop_loop_products', 'woocommerce_product_subcategories' );
add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10, 2);
add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10, 2);
add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10, 2);

/* Subcategories */
add_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10);

/* Before Single Products */
add_action( 'woocommerce_before_single_product', 'woocommerce_check_product_visibility', 10, 2);

/* Before Single Products Summary Div */
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
add_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );

/* After Single Products Summary Div */
add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

/* Product Summary Box */
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10, 2);
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20, 2);
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40, 2);
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50, 2);

/* After Single Products */
add_action('woocommerce_after_single_product', 'woocommerce_upsell_display');

/* Product Add to cart */
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30, 2 );
add_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart' ); 
add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart' ); 
add_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart' ); 
add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart' );

/* Product Add to Cart forms */
add_action( 'woocommerce_add_to_cart_form', 'woocommerce_add_to_cart_form_nonce', 10);

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
add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );

/* Remove the singular class for woocommerce single product */
add_action( 'after_setup_theme', 'woocommerce_body_classes_check' );

function woocommerce_body_classes_check () {
	if( has_filter( 'body_class', 'twentyeleven_body_classes' ) ) add_filter( 'body_class', 'woocommerce_body_classes' );
}

/* Cart */
add_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

/* Footer */
add_action( 'wp_footer', 'woocommerce_demo_store' );

/* Order details */
add_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
