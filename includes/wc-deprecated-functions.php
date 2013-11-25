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
