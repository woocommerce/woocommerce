<?php
/**
 * Deprecated functions
 *
 * Where functions come to die.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function woocommerce_show_messages() {
	_deprecated_function( 'woocommerce_show_messages', '2.1', 'wc_print_notices' );
	wc_print_notices();
}
function woocommerce_weekend_area_js() {
	_deprecated_function( 'woocommerce_weekend_area_js', '2.1', '' );
}
function woocommerce_tooltip_js() {
	_deprecated_function( 'woocommerce_tooltip_js', '2.1', '' );
}
function woocommerce_datepicker_js() {
	_deprecated_function( 'woocommerce_datepicker_js', '2.1', '' );
}
function woocommerce_admin_scripts() {
	_deprecated_function( 'woocommerce_admin_scripts', '2.1', '' );
}
function woocommerce_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	_deprecated_function( 'woocommerce_create_page', '2.1', 'wc_create_page' );
	return wc_create_page( $slug, $option, $page_title, $page_content, $post_parent );
}
function woocommerce_readfile_chunked( $file, $retbytes = true ) {
	_deprecated_function( 'woocommerce_readfile_chunked', '2.1', 'WC_Download_Handler::readfile_chunked()' );
	return WC_Download_Handler::readfile_chunked( $file, $retbytes );
}

/**
 * Formal total costs - format to the number of decimal places for the base currency.
 *
 * @access public
 * @param mixed $number
 * @deprecated 2.1
 * @return string
 */
function woocommerce_format_total( $number ) {
	_deprecated_function( __FUNCTION__, '2.1', 'woocommerce_format_decimal()' );
	return woocommerce_format_decimal( $number, get_option( 'woocommerce_price_num_decimals' ), false );
}

/**
 * Get product name with extra details such as SKU price and attributes. Used within admin.
 *
 * @access public
 * @param mixed $product
 * @deprecated 2.1
 * @return void
 */
function woocommerce_get_formatted_product_name( $product ) {
	_deprecated_function( __FUNCTION__, '2.1', 'WC_Product::get_formatted_name()' );
	return $product->get_formatted_name();
}

/**
 * Handle IPN requests for the legacy paypal gateway by calling gateways manually if needed.
 *
 * @access public
 * @return void
 */
function woocommerce_legacy_paypal_ipn() {
	if ( ! empty( $_GET['paypalListener'] ) && $_GET['paypalListener'] == 'paypal_standard_IPN' ) {

		WC()->payment_gateways();

		do_action( 'woocommerce_api_wc_gateway_paypal' );
	}
}
add_action( 'init', 'woocommerce_legacy_paypal_ipn' );


/**
 * Handle renamed filters
 */
global $wc_map_deprecated_filters;

$wc_map_deprecated_filters = array(
	'woocommerce_cart_item_class'       => 'woocommerce_cart_table_item_class',
	'woocommerce_cart_item_product_id'  => 'hook_woocommerce_in_cart_product_id',
	'woocommerce_cart_item_thumbnail'   => 'hook_woocommerce_in_cart_product_thumbnail',
	'woocommerce_cart_item_price'       => 'woocommerce_cart_item_price_html',
	'woocommerce_cart_item_name'        => 'woocommerce_in_cart_product_title',
	'woocommerce_order_item_class'      => 'woocommerce_order_table_item_class',
	'woocommerce_order_item_name'       => 'woocommerce_order_table_product_title',
	'woocommerce_order_amount_shipping' => 'woocommerce_order_amount_total_shipping'
);

foreach ( $wc_map_deprecated_filters as $new => $old )
	add_filter( $new, 'woocommerce_deprecated_filter_mapping' );

function woocommerce_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $wc_map_deprecated_filters;

	$filter = current_filter();

	if ( isset( $wc_map_deprecated_filters[ $filter ] ) )
		if ( has_filter( $wc_map_deprecated_filters[ $filter ] ) ) {
			$data = apply_filters( $wc_map_deprecated_filters[ $filter ], $arg_1, $arg_2, $arg_3 );
			_deprecated_function( 'The ' . $wc_map_deprecated_filters[ $filter ] . ' filter', '2.1', $filter );
		}

	return $data;
}

/**
 * Alias functions/soft-deprecated function names (moving from woocommerce_ to wc_). These will be deprecated with notices in future updates.
 */

/**
 * Attribute functions - soft deprecated
 */
function woocommerce_product_dropdown_categories( $args = array(), $deprecated_hierarchical = 1, $deprecated_show_uncategorized = 1, $deprecated_orderby = '' ) {
	wc_product_dropdown_categories( $args, $deprecated_hierarchical, $deprecated_show_uncategorized, $deprecated_orderby );
}
function woocommerce_walk_category_dropdown_tree() {
	wc_walk_category_dropdown_tree();
}
function woocommerce_taxonomy_metadata_wpdbfix() {
	wc_taxonomy_metadata_wpdbfix();
}
function woocommerce_order_terms( $the_term, $next_id, $taxonomy, $index = 0, $terms = null ) {
	wc_reorder_terms( $the_term, $next_id, $taxonomy, $index, $terms );
}
function woocommerce_set_term_order( $term_id, $index, $taxonomy, $recursive = false ) {
	wc_set_term_order( $term_id, $index, $taxonomy, $recursive );
}
function woocommerce_terms_clauses( $clauses, $taxonomies, $args ) {
	wc_terms_clauses( $clauses, $taxonomies, $args );
}
function _woocommerce_term_recount( $terms, $taxonomy, $callback, $terms_are_term_taxonomy_ids ) {
	_wc_term_recount( $terms, $taxonomy, $callback, $terms_are_term_taxonomy_ids );
}
function woocommerce_recount_after_stock_change( $product_id ) {
	wc_recount_after_stock_change( $product_id );
}
function woocommerce_change_term_counts( $terms, $taxonomies, $args ) {
	wc_change_term_counts( $terms, $taxonomies, $args );
}

/**
 * Template functions - soft deprecated
 */
function woocommerce_template_redirect() {
	wc_template_redirect();
}
if ( ! function_exists( 'woocommerce_output_content_wrapper' ) ) {
	function woocommerce_output_content_wrapper() {
		wc_output_content_wrapper();
	}
}
if ( ! function_exists( 'woocommerce_output_content_wrapper_end' ) ) {
	function woocommerce_output_content_wrapper_end() {
		wc_output_content_wrapper_end();
	}
}
if ( ! function_exists( 'woocommerce_get_sidebar' ) ) {
	function woocommerce_get_sidebar() {
		wc_get_sidebar();
	}
}
if ( ! function_exists( 'woocommerce_demo_store' ) ) {
	function woocommerce_demo_store() {
		wc_demo_store();
	}
}
if ( ! function_exists( 'woocommerce_page_title' ) ) {
	function woocommerce_page_title( $echo = true ) {
		wc_page_title( $echo );
	}
}
if ( ! function_exists( 'woocommerce_product_loop_start' ) ) {
	function woocommerce_product_loop_start( $echo = true ) {
		wc_product_loop_start( $echo );
	}
}
if ( ! function_exists( 'woocommerce_product_loop_end' ) ) {
	function woocommerce_product_loop_end( $echo = true ) {
		wc_product_loop_end( $echo );
	}
}
if ( ! function_exists( 'woocommerce_taxonomy_archive_description' ) ) {
	function woocommerce_taxonomy_archive_description() {
		wc_taxonomy_archive_description();
	}
}
if ( ! function_exists( 'woocommerce_product_archive_description' ) ) {
	function woocommerce_product_archive_description() {
		wc_product_archive_description();
	}
}
if ( ! function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
	function woocommerce_template_loop_add_to_cart() {
		wc_template_loop_add_to_cart();
	}
}
if ( ! function_exists( 'woocommerce_template_loop_product_thumbnail' ) ) {
	function woocommerce_template_loop_product_thumbnail() {
		wc_template_loop_product_thumbnail();
	}
}
if ( ! function_exists( 'woocommerce_template_loop_price' ) ) {
	function woocommerce_template_loop_price() {
		wc_template_loop_price();
	}
}
if ( ! function_exists( 'woocommerce_template_loop_rating' ) ) {
	function woocommerce_template_loop_rating() {
		wc_template_loop_rating();
	}
}
if ( ! function_exists( 'woocommerce_show_product_loop_sale_flash' ) ) {
	function woocommerce_show_product_loop_sale_flash() {
		wc_show_product_loop_sale_flash();
	}
}
if ( ! function_exists( 'woocommerce_reset_loop' ) ) {
	function woocommerce_reset_loop() {
		wc_reset_loop();
	}
}
if ( ! function_exists( 'woocommerce_get_product_schema' ) ) {
	function woocommerce_get_product_schema() {
		wc_get_product_schema();
	}
}
if ( ! function_exists( 'woocommerce_get_product_thumbnail' ) ) {
	function woocommerce_get_product_thumbnail() {
		wc_get_product_thumbnail();
	}
}
if ( ! function_exists( 'woocommerce_result_count' ) ) {
	function woocommerce_result_count() {
		wc_result_count();
	}
}
if ( ! function_exists( 'woocommerce_catalog_ordering' ) ) {
	function woocommerce_catalog_ordering() {
		wc_catalog_ordering();
	}
}
if ( ! function_exists( 'woocommerce_pagination' ) ) {
	function woocommerce_pagination() {
		wc_pagination();
	}
}
if ( ! function_exists( 'woocommerce_show_product_images' ) ) {
	function woocommerce_show_product_images() {
		wc_show_product_images();
	}
}
if ( ! function_exists( 'woocommerce_show_product_thumbnails' ) ) {
	function woocommerce_show_product_thumbnails() {
		wc_show_product_thumbnails();
	}
}
if ( ! function_exists( 'woocommerce_output_product_data_tabs' ) ) {
	function woocommerce_output_product_data_tabs() {
		wc_output_product_data_tabs();
	}
}
if ( ! function_exists( 'woocommerce_template_single_title' ) ) {
	function woocommerce_template_single_title() {
		wc_template_single_title();
	}
}
if ( ! function_exists( 'woocommerce_template_single_rating' ) ) {
	function woocommerce_template_single_rating() {
		wc_template_single_rating();
	}
}
if ( ! function_exists( 'woocommerce_template_single_price' ) ) {
	function woocommerce_template_single_price() {
		wc_template_single_price();
	}
}
if ( ! function_exists( 'woocommerce_template_single_excerpt' ) ) {
	function woocommerce_template_single_excerpt() {
		wc_template_single_excerpt();
	}
}
if ( ! function_exists( 'woocommerce_template_single_meta' ) ) {
	function woocommerce_template_single_meta() {
		wc_template_single_meta();
	}
}
if ( ! function_exists( 'woocommerce_template_single_sharing' ) ) {
	function woocommerce_template_single_sharing() {
		wc_template_single_sharing();
	}
}
if ( ! function_exists( 'woocommerce_show_product_sale_flash' ) ) {
	function woocommerce_show_product_sale_flash() {
		wc_show_product_sale_flash();
	}
}
if ( ! function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
	function woocommerce_template_single_add_to_cart() {
		wc_template_single_add_to_cart();
	}
}
if ( ! function_exists( 'woocommerce_simple_add_to_cart' ) ) {
	function woocommerce_simple_add_to_cart() {
		wc_simple_add_to_cart();
	}
}
if ( ! function_exists( 'woocommerce_grouped_add_to_cart' ) ) {
	function woocommerce_grouped_add_to_cart() {
		wc_grouped_add_to_cart();
	}
}
if ( ! function_exists( 'woocommerce_variable_add_to_cart' ) ) {
	function woocommerce_variable_add_to_cart() {
		wc_variable_add_to_cart();
	}
}
if ( ! function_exists( 'woocommerce_external_add_to_cart' ) ) {
	function woocommerce_external_add_to_cart() {
		wc_external_add_to_cart();
	}
}
if ( ! function_exists( 'woocommerce_quantity_input' ) ) {
	function woocommerce_quantity_input( $args = array() ) {
		wc_quantity_input( $args );
	}
}
if ( ! function_exists( 'woocommerce_product_description_tab' ) ) {
	function woocommerce_product_description_tab() {
		wc_product_description_tab();
	}
}
if ( ! function_exists( 'woocommerce_product_additional_information_tab' ) ) {
	function woocommerce_product_additional_information_tab() {
		wc_product_additional_information_tab();
	}
}
if ( ! function_exists( 'woocommerce_product_reviews_tab' ) ) {
	function woocommerce_product_reviews_tab() {
		wc_product_reviews_tab();
	}
}
if ( ! function_exists( 'woocommerce_default_product_tabs' ) ) {
	function woocommerce_default_product_tabs( $tabs = array() ) {
		wc_default_product_tabs( $tabs );
	}
}
if ( ! function_exists( 'woocommerce_sort_product_tabs' ) ) {
	function woocommerce_sort_product_tabs( $tabs = array() ) {
		wc_sort_product_tabs( $tabs );
	}
}
if ( ! function_exists( 'woocommerce_comments' ) ) {
	function woocommerce_comments( $comment, $args, $depth ) {
		wc_comments( $comment, $args, $depth );
	}
}
if ( ! function_exists( 'woocommerce_output_related_products' ) ) {
	function woocommerce_output_related_products() {
		wc_output_related_products();
	}
}
if ( ! function_exists( 'woocommerce_related_products' ) ) {
	function woocommerce_related_products( $args = array(), $columns = false, $orderby = false ) {
		wc_related_products( $args, $columns, $orderby );
	}
}
if ( ! function_exists( 'woocommerce_upsell_display' ) ) {
	function woocommerce_upsell_display( $posts_per_page = '-1', $columns = 2, $orderby = 'rand' ) {
		wc_upsell_display( $posts_per_page, $columns, $orderby );
	}
}
if ( ! function_exists( 'woocommerce_shipping_calculator' ) ) {
	function woocommerce_shipping_calculator() {
		wc_shipping_calculator();
	}
}
if ( ! function_exists( 'woocommerce_cart_totals' ) ) {
	function woocommerce_cart_totals() {
		wc_cart_totals();
	}
}
if ( ! function_exists( 'woocommerce_cross_sell_display' ) ) {
	function woocommerce_cross_sell_display( $posts_per_page = 2, $columns = 2, $orderby = 'rand' ) {
		wc_cross_sell_display( $posts_per_page, $columns, $orderby );
	}
}
if ( ! function_exists( 'woocommerce_mini_cart' ) ) {
	function woocommerce_mini_cart( $args = array() ) {
		wc_mini_cart( $args );
	}
}
if ( ! function_exists( 'woocommerce_login_form' ) ) {
	function woocommerce_login_form( $args = array() ) {
		wc_login_form( $args );
	}
}
if ( ! function_exists( 'woocommerce_checkout_login_form' ) ) {
	function woocommerce_checkout_login_form() {
		wc_checkout_login_form();
	}
}
if ( ! function_exists( 'woocommerce_breadcrumb' ) ) {
	function woocommerce_breadcrumb( $args = array() ) {
		wc_breadcrumb( $args );
	}
}
if ( ! function_exists( 'woocommerce_order_review' ) ) {
	function woocommerce_order_review() {
		wc_order_review();
	}
}
if ( ! function_exists( 'woocommerce_checkout_coupon_form' ) ) {
	function woocommerce_checkout_coupon_form() {
		wc_checkout_coupon_form();
	}
}
if ( ! function_exists( 'woocommerce_products_will_display' ) ) {
	function woocommerce_products_will_display() {
		wc_products_will_display();
	}
}
if ( ! function_exists( 'woocommerce_product_subcategories' ) ) {
	function woocommerce_product_subcategories( $args = array() ) {
		wc_product_subcategories( $args );
	}
}
if ( ! function_exists( 'woocommerce_subcategory_thumbnail' ) ) {
	function woocommerce_subcategory_thumbnail( $category ) {
		wc_subcategory_thumbnail( $category );
	}
}
if ( ! function_exists( 'woocommerce_order_details_table' ) ) {
	function woocommerce_order_details_table( $order_id ) {
		wc_order_details_table( $order_id );
	}
}
if ( ! function_exists( 'woocommerce_order_again_button' ) ) {
	function woocommerce_order_again_button( $order ) {
		wc_order_again_button( $order );
	}
}
if ( ! function_exists( 'woocommerce_form_field' ) ) {
	function woocommerce_form_field( $key, $args, $value = null ) {
		wc_form_field( $key, $args, $value );
	}
}
if ( ! function_exists( 'woocommerce_products_rss_feed' ) ) {
	function woocommerce_products_rss_feed() {
		wc_products_rss_feed();
	}
}

/**
 * Product functions - soft deprecated
 */
function woocommerce_get_product_ids_on_sale() {
	wc_get_product_ids_on_sale();
}
function woocommerce_get_featured_product_ids() {
	wc_get_featured_product_ids();
}
function woocommerce_get_product_terms( $object_id, $taxonomy, $fields = 'all' ) {
	wc_get_product_terms( $object_id, $taxonomy, array( 'fields' => $fields ) );
}
function woocommerce_product_post_type_link( $permalink, $post ) {
	wc_product_post_type_link( $permalink, $post );
}
function woocommerce_placeholder_img_src() {
	wc_placeholder_img_src();
}
function woocommerce_placeholder_img() {
	wc_placeholder_img();
}
function woocommerce_get_formatted_variation( $variation = '', $flat = false ) {
	wc_get_formatted_variation( $variation, $flat );
}
function woocommerce_scheduled_sales() {
	wc_scheduled_sales();
}
function woocommerce_get_attachment_image_attributes( $attr ) {
	wc_get_attachment_image_attributes( $attr );
}
function woocommerce_prepare_attachment_for_js( $response ) {
	wc_prepare_attachment_for_js( $response );
}
function woocommerce_track_product_view() {
	wc_track_product_view();
}
