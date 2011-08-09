<?php
/**
 * ACTIONS USED IN TEMPLATE FILES
 *
 **/

/* Content Wrappers */
add_action( 'jigoshop_before_main_content', 'jigoshop_output_content_wrapper', 10);
add_action( 'jigoshop_after_main_content', 'jigoshop_output_content_wrapper_end', 10);

/* Shop Messages */
add_action( 'jigoshop_before_single_product', 'jigoshop::show_messages', 10);
add_action( 'jigoshop_before_shop_loop', 'jigoshop::show_messages', 10);

/* Sale flashes */
add_action( 'jigoshop_before_shop_loop_item_title', 'jigoshop_show_product_sale_flash', 10, 2);
add_action( 'jigoshop_before_single_product_summary', 'jigoshop_show_product_sale_flash', 10, 2);

/* Breadcrumbs */
add_action( 'jigoshop_before_main_content', 'jigoshop_breadcrumb', 20, 0);

/* Sidebar */
add_action( 'jigoshop_sidebar', 'jigoshop_get_sidebar', 10);

/* Products Loop */
add_action( 'jigoshop_after_shop_loop_item', 'jigoshop_template_loop_add_to_cart', 10, 2);
add_action( 'jigoshop_before_shop_loop_item_title', 'jigoshop_template_loop_product_thumbnail', 10, 2);
add_action( 'jigoshop_after_shop_loop_item_title', 'jigoshop_template_loop_price', 10, 2);

/* Before Single Products */
add_action( 'jigoshop_before_single_product', 'jigoshop_check_product_visibility', 10, 2);

/* Before Single Products Summary Div */
add_action( 'jigoshop_before_single_product_summary', 'jigoshop_show_product_images', 20);
add_action( 'jigoshop_product_thumbnails', 'jigoshop_show_product_thumbnails', 20 );

/* After Single Products Summary Div */
add_action( 'jigoshop_after_single_product_summary', 'jigoshop_output_product_data_tabs', 10);
add_action( 'jigoshop_after_single_product_summary', 'jigoshop_output_related_products', 20);

/* Product Summary Box */
add_action( 'jigoshop_template_single_summary', 'jigoshop_template_single_price', 10, 2);
add_action( 'jigoshop_template_single_summary', 'jigoshop_template_single_excerpt', 20, 2);
add_action( 'jigoshop_template_single_summary', 'jigoshop_template_single_meta', 40, 2);
add_action( 'jigoshop_template_single_summary', 'jigoshop_template_single_sharing', 50, 2);

/* Product Add to cart */
add_action( 'jigoshop_template_single_summary', 'jigoshop_template_single_add_to_cart', 30, 2 );
add_action( 'simple_add_to_cart', 'jigoshop_simple_add_to_cart' ); 
add_action( 'virtual_add_to_cart', 'jigoshop_simple_add_to_cart' ); 
add_action( 'downloadable_add_to_cart', 'jigoshop_downloadable_add_to_cart' ); 
add_action( 'grouped_add_to_cart', 'jigoshop_grouped_add_to_cart' ); 
add_action( 'variable_add_to_cart', 'jigoshop_variable_add_to_cart' ); 

/* Product Add to Cart forms */
add_action( 'jigoshop_add_to_cart_form', 'jigoshop_add_to_cart_form_nonce', 10);

/* Pagination in loop-shop */
add_action( 'jigoshop_pagination', 'jigoshop_pagination', 10 );

/* Product page tabs */
add_action( 'jigoshop_product_tabs', 'jigoshop_product_description_tab', 10 );
add_action( 'jigoshop_product_tabs', 'jigoshop_product_attributes_tab', 20 );
add_action( 'jigoshop_product_tabs', 'jigoshop_product_reviews_tab', 30 );

add_action( 'jigoshop_product_tab_panels', 'jigoshop_product_description_panel', 10 );
add_action( 'jigoshop_product_tab_panels', 'jigoshop_product_attributes_panel', 20 );
add_action( 'jigoshop_product_tab_panels', 'jigoshop_product_reviews_panel', 30 );

/* Checkout */
add_action( 'before_checkout_form', 'jigoshop_checkout_login_form', 10 );

/* Remove the singular class for jigoshop single product */
add_action( 'after_setup_theme', 'jigoshop_body_classes_check' );

function jigoshop_body_classes_check () {
	if( has_filter( 'body_class', 'twentyeleven_body_classes' ) ) 
		add_filter( 'body_class', 'jigoshop_body_classes' );
}
