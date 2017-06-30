<?php
/**
 * Deprecated functions
 *
 * Where functions come to die.
 *
 * @author 	WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs a deprecated action with notice only if used.
 *
 * @since  3.0.0
 * @param  string $action
 * @param  array $args
 * @param  string $deprecated_in
 * @param  string $replacement
 */
function wc_do_deprecated_action( $action, $args, $deprecated_in, $replacement ) {
	if ( has_action( $action ) ) {
		wc_deprecated_function( 'Action: ' . $action, $deprecated_in, $replacement );
		do_action_ref_array( $action, $args );
	}
}

/**
 * Wrapper for deprecated functions so we can apply some extra logic.
 *
 * @since  3.0.0
 * @param  string $function
 * @param  string $version
 * @param  string $replacement
 */
function wc_deprecated_function( $function, $version, $replacement = null ) {
	if ( is_ajax() ) {
		do_action( 'deprecated_function_run', $function, $replacement, $version );
		$log_string  = "The {$function} function is deprecated since version {$version}.";
		$log_string .= $replacement ? " Replace with {$replacement}." : '';
		error_log( $log_string );
	} else {
		_deprecated_function( $function, $version, $replacement );
	}
}


/**
 * Wrapper for wc_doing_it_wrong.
 *
 * @since  3.0.0
 * @param  string $function
 * @param  string $version
 * @param  string $replacement
 */
function wc_doing_it_wrong( $function, $message, $version ) {
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
}

/**
 * Wrapper for deprecated arguments so we can apply some extra logic.
 *
 * @since  3.0.0
 * @param  string $argument
 * @param  string $version
 * @param  string $replacement
 */
function wc_deprecated_argument( $argument, $version, $message = null ) {
	if ( is_ajax() ) {
		do_action( 'deprecated_argument_run', $argument, $message, $version );
		error_log( "The {$argument} argument is deprecated since version {$version}. {$message}" );
	} else {
		_deprecated_argument( $argument, $version, $message );
	}
}

/**
 * @deprecated 2.1
 */
function woocommerce_show_messages() {
	wc_deprecated_function( 'woocommerce_show_messages', '2.1', 'wc_print_notices' );
	wc_print_notices();
}

/**
 * @deprecated 2.1
 */
function woocommerce_weekend_area_js() {
	wc_deprecated_function( 'woocommerce_weekend_area_js', '2.1' );
}

/**
 * @deprecated 2.1
 */
function woocommerce_tooltip_js() {
	wc_deprecated_function( 'woocommerce_tooltip_js', '2.1' );
}

/**
 * @deprecated 2.1
 */
function woocommerce_datepicker_js() {
	wc_deprecated_function( 'woocommerce_datepicker_js', '2.1' );
}

/**
 * @deprecated 2.1
 */
function woocommerce_admin_scripts() {
	wc_deprecated_function( 'woocommerce_admin_scripts', '2.1' );
}

/**
 * @deprecated 2.1
 */
function woocommerce_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	wc_deprecated_function( 'woocommerce_create_page', '2.1', 'wc_create_page' );
	return wc_create_page( $slug, $option, $page_title, $page_content, $post_parent );
}

/**
 * @deprecated 2.1
 */
function woocommerce_readfile_chunked( $file, $retbytes = true ) {
	wc_deprecated_function( 'woocommerce_readfile_chunked', '2.1', 'WC_Download_Handler::readfile_chunked()' );
	return WC_Download_Handler::readfile_chunked( $file );
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
	wc_deprecated_function( __FUNCTION__, '2.1', 'wc_format_decimal()' );
	return wc_format_decimal( $number, wc_get_price_decimals(), false );
}

/**
 * Get product name with extra details such as SKU price and attributes. Used within admin.
 *
 * @access public
 * @param WC_Product $product
 * @deprecated 2.1
 * @return string
 */
function woocommerce_get_formatted_product_name( $product ) {
	wc_deprecated_function( __FUNCTION__, '2.1', 'WC_Product::get_formatted_name()' );
	return $product->get_formatted_name();
}

/**
 * Handle IPN requests for the legacy paypal gateway by calling gateways manually if needed.
 *
 * @access public
 */
function woocommerce_legacy_paypal_ipn() {
	if ( ! empty( $_GET['paypalListener'] ) && 'paypal_standard_IPN' === $_GET['paypalListener'] ) {
		WC()->payment_gateways();
		do_action( 'woocommerce_api_wc_gateway_paypal' );
	}
}
add_action( 'init', 'woocommerce_legacy_paypal_ipn' );

/**
 * @deprecated 3.0
 */
function get_product( $the_product = false, $args = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_product' );
	return wc_get_product( $the_product, $args );
}

/**
 * @deprecated 3.0
 */
function woocommerce_protected_product_add_to_cart( $passed, $product_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_protected_product_add_to_cart' );
	return wc_protected_product_add_to_cart( $passed, $product_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_empty_cart() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_empty_cart' );
	wc_empty_cart();
}

/**
 * @deprecated 3.0
 */
function woocommerce_load_persistent_cart( $user_login, $user = 0 ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_load_persistent_cart' );
	return wc_load_persistent_cart( $user_login, $user );
}

/**
 * @deprecated 3.0
 */
function woocommerce_add_to_cart_message( $product_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_add_to_cart_message' );
	wc_add_to_cart_message( $product_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_clear_cart_after_payment() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_clear_cart_after_payment' );
	wc_clear_cart_after_payment();
}

/**
 * @deprecated 3.0
 */
function woocommerce_cart_totals_subtotal_html() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_cart_totals_subtotal_html' );
	wc_cart_totals_subtotal_html();
}

/**
 * @deprecated 3.0
 */
function woocommerce_cart_totals_shipping_html() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_cart_totals_shipping_html' );
	wc_cart_totals_shipping_html();
}

/**
 * @deprecated 3.0
 */
function woocommerce_cart_totals_coupon_html( $coupon ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_cart_totals_coupon_html' );
	wc_cart_totals_coupon_html( $coupon );
}

/**
 * @deprecated 3.0
 */
function woocommerce_cart_totals_order_total_html() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_cart_totals_order_total_html' );
	wc_cart_totals_order_total_html();
}

/**
 * @deprecated 3.0
 */
function woocommerce_cart_totals_fee_html( $fee ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_cart_totals_fee_html' );
	wc_cart_totals_fee_html( $fee );
}

/**
 * @deprecated 3.0
 */
function woocommerce_cart_totals_shipping_method_label( $method ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_cart_totals_shipping_method_label' );
	return wc_cart_totals_shipping_method_label( $method );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_template_part( $slug, $name = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_template_part' );
	wc_get_template_part( $slug, $name );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_template' );
	wc_get_template( $template_name, $args, $template_path, $default_path );
}

/**
 * @deprecated 3.0
 */
function woocommerce_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_locate_template' );
	return wc_locate_template( $template_name, $template_path, $default_path );
}

/**
 * @deprecated 3.0
 */
function woocommerce_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_mail' );
	wc_mail( $to, $subject, $message, $headers, $attachments );
}

/**
 * @deprecated 3.0
 */
function woocommerce_disable_admin_bar( $show_admin_bar ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_disable_admin_bar' );
	return wc_disable_admin_bar( $show_admin_bar );
}

/**
 * @deprecated 3.0
 */
function woocommerce_create_new_customer( $email, $username = '', $password = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_create_new_customer' );
	return wc_create_new_customer( $email, $username, $password );
}

/**
 * @deprecated 3.0
 */
function woocommerce_set_customer_auth_cookie( $customer_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_set_customer_auth_cookie' );
	wc_set_customer_auth_cookie( $customer_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_update_new_customer_past_orders( $customer_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_update_new_customer_past_orders' );
	return wc_update_new_customer_past_orders( $customer_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_paying_customer( $order_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_paying_customer' );
	wc_paying_customer( $order_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_customer_bought_product( $customer_email, $user_id, $product_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_customer_bought_product' );
	return wc_customer_bought_product( $customer_email, $user_id, $product_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_customer_has_capability( $allcaps, $caps, $args ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_customer_has_capability' );
	return wc_customer_has_capability( $allcaps, $caps, $args );
}

/**
 * @deprecated 3.0
 */
function woocommerce_sanitize_taxonomy_name( $taxonomy ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_sanitize_taxonomy_name' );
	return wc_sanitize_taxonomy_name( $taxonomy );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_filename_from_url( $file_url ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_filename_from_url' );
	return wc_get_filename_from_url( $file_url );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_dimension( $dim, $to_unit ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_dimension' );
	return wc_get_dimension( $dim, $to_unit );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_weight( $weight, $to_unit ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_weight' );
	return wc_get_weight( $weight, $to_unit );
}

/**
 * @deprecated 3.0
 */
function woocommerce_trim_zeros( $price ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_trim_zeros' );
	return wc_trim_zeros( $price );
}

/**
 * @deprecated 3.0
 */
function woocommerce_round_tax_total( $tax ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_round_tax_total' );
	return wc_round_tax_total( $tax );
}

/**
 * @deprecated 3.0
 */
function woocommerce_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_format_decimal' );
	return wc_format_decimal( $number, $dp, $trim_zeros );
}

/**
 * @deprecated 3.0
 */
function woocommerce_clean( $var ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_clean' );
	return wc_clean( $var );
}

/**
 * @deprecated 3.0
 */
function woocommerce_array_overlay( $a1, $a2 ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_array_overlay' );
	return wc_array_overlay( $a1, $a2 );
}

/**
 * @deprecated 3.0
 */
function woocommerce_price( $price, $args = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_price' );
	return wc_price( $price, $args );
}

/**
 * @deprecated 3.0
 */
function woocommerce_let_to_num( $size ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_let_to_num' );
	return wc_let_to_num( $size );
}

/**
 * @deprecated 3.0
 */
function woocommerce_date_format() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_date_format' );
	return wc_date_format();
}

/**
 * @deprecated 3.0
 */
function woocommerce_time_format() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_time_format' );
	return wc_time_format();
}

/**
 * @deprecated 3.0
 */
function woocommerce_timezone_string() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_timezone_string' );
	return wc_timezone_string();
}

if ( ! function_exists( 'woocommerce_rgb_from_hex' ) ) {
	/**
	 * @deprecated 3.0
	 */
	function woocommerce_rgb_from_hex( $color ) {
		wc_deprecated_function( __FUNCTION__, '3.0', 'wc_rgb_from_hex' );
		return wc_rgb_from_hex( $color );
	}
}

if ( ! function_exists( 'woocommerce_hex_darker' ) ) {
	/**
	 * @deprecated 3.0
	 */
	function woocommerce_hex_darker( $color, $factor = 30 ) {
		wc_deprecated_function( __FUNCTION__, '3.0', 'wc_hex_darker' );
		return wc_hex_darker( $color, $factor );
	}
}

if ( ! function_exists( 'woocommerce_hex_lighter' ) ) {
	/**
	 * @deprecated 3.0
	 */
	function woocommerce_hex_lighter( $color, $factor = 30 ) {
		wc_deprecated_function( __FUNCTION__, '3.0', 'wc_hex_lighter' );
		return wc_hex_lighter( $color, $factor );
	}
}

if ( ! function_exists( 'woocommerce_light_or_dark' ) ) {
	/**
	 * @deprecated 3.0
	 */
	function woocommerce_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
		wc_deprecated_function( __FUNCTION__, '3.0', 'wc_light_or_dark' );
		return wc_light_or_dark( $color, $dark, $light );
	}
}

if ( ! function_exists( 'woocommerce_format_hex' ) ) {
	/**
	 * @deprecated 3.0
	 */
	function woocommerce_format_hex( $hex ) {
		wc_deprecated_function( __FUNCTION__, '3.0', 'wc_format_hex' );
		return wc_format_hex( $hex );
	}
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_order_id_by_order_key( $order_key ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_order_id_by_order_key' );
	return wc_get_order_id_by_order_key( $order_key );
}

/**
 * @deprecated 3.0
 */
function woocommerce_downloadable_file_permission( $download_id, $product_id, $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_downloadable_file_permission' );
	return wc_downloadable_file_permission( $download_id, $product_id, $order );
}

/**
 * @deprecated 3.0
 */
function woocommerce_downloadable_product_permissions( $order_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_downloadable_product_permissions' );
	wc_downloadable_product_permissions( $order_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_add_order_item( $order_id, $item ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_add_order_item' );
	return wc_add_order_item( $order_id, $item );
}

/**
 * @deprecated 3.0
 */
function woocommerce_delete_order_item( $item_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_delete_order_item' );
	return wc_delete_order_item( $item_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_update_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_update_order_item_meta' );
	return wc_update_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value );
}

/**
 * @deprecated 3.0
 */
function woocommerce_add_order_item_meta( $item_id, $meta_key, $meta_value, $unique = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_add_order_item_meta' );
	return wc_add_order_item_meta( $item_id, $meta_key, $meta_value, $unique );
}

/**
 * @deprecated 3.0
 */
function woocommerce_delete_order_item_meta( $item_id, $meta_key, $meta_value = '', $delete_all = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_delete_order_item_meta' );
	return wc_delete_order_item_meta( $item_id, $meta_key, $meta_value, $delete_all );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_order_item_meta( $item_id, $key, $single = true ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_order_item_meta' );
	return wc_get_order_item_meta( $item_id, $key, $single );
}

/**
 * @deprecated 3.0
 */
function woocommerce_cancel_unpaid_orders() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_cancel_unpaid_orders' );
	wc_cancel_unpaid_orders();
}

/**
 * @deprecated 3.0
 */
function woocommerce_processing_order_count() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_processing_order_count' );
	return wc_processing_order_count();
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_page_id( $page ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_page_id' );
	return wc_get_page_id( $page );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_endpoint_url' );
	return wc_get_endpoint_url( $endpoint, $value, $permalink );
}

/**
 * @deprecated 3.0
 */
function woocommerce_lostpassword_url( $url ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_lostpassword_url' );
	return wc_lostpassword_url( $url );
}

/**
 * @deprecated 3.0
 */
function woocommerce_customer_edit_account_url() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_customer_edit_account_url' );
	return wc_customer_edit_account_url();
}

/**
 * @deprecated 3.0
 */
function woocommerce_nav_menu_items( $items, $args ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_nav_menu_items' );
	return wc_nav_menu_items( $items );
}

/**
 * @deprecated 3.0
 */
function woocommerce_nav_menu_item_classes( $menu_items, $args ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_nav_menu_item_classes' );
	return wc_nav_menu_item_classes( $menu_items );
}

/**
 * @deprecated 3.0
 */
function woocommerce_list_pages( $pages ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_list_pages' );
	return wc_list_pages( $pages );
}

/**
 * @deprecated 3.0
 */
function woocommerce_product_dropdown_categories( $args = array(), $deprecated_hierarchical = 1, $deprecated_show_uncategorized = 1, $deprecated_orderby = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_product_dropdown_categories' );
	return wc_product_dropdown_categories( $args, $deprecated_hierarchical, $deprecated_show_uncategorized, $deprecated_orderby );
}

/**
 * @deprecated 3.0
 */
function woocommerce_walk_category_dropdown_tree( $a1 = '', $a2 = '', $a3 = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_walk_category_dropdown_tree' );
	return wc_walk_category_dropdown_tree( $a1, $a2, $a3 );
}

/**
 * @deprecated 3.0
 */
function woocommerce_taxonomy_metadata_wpdbfix() {
	wc_deprecated_function( __FUNCTION__, '3.0' );
}

/**
 * @deprecated 3.0
 */
function wc_taxonomy_metadata_wpdbfix() {
	wc_deprecated_function( __FUNCTION__, '3.0' );
}

/**
 * @deprecated 3.0
 */
function woocommerce_order_terms( $the_term, $next_id, $taxonomy, $index = 0, $terms = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_reorder_terms' );
	return wc_reorder_terms( $the_term, $next_id, $taxonomy, $index, $terms );
}

/**
 * @deprecated 3.0
 */
function woocommerce_set_term_order( $term_id, $index, $taxonomy, $recursive = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_set_term_order' );
	return wc_set_term_order( $term_id, $index, $taxonomy, $recursive );
}

/**
 * @deprecated 3.0
 */
function woocommerce_terms_clauses( $clauses, $taxonomies, $args ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_terms_clauses' );
	return wc_terms_clauses( $clauses, $taxonomies, $args );
}

/**
 * @deprecated 3.0
 */
function _woocommerce_term_recount( $terms, $taxonomy, $callback, $terms_are_term_taxonomy_ids ) {
	wc_deprecated_function( __FUNCTION__, '3.0', '_wc_term_recount' );
	return _wc_term_recount( $terms, $taxonomy, $callback, $terms_are_term_taxonomy_ids );
}

/**
 * @deprecated 3.0
 */
function woocommerce_recount_after_stock_change( $product_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_recount_after_stock_change' );
	return wc_recount_after_stock_change( $product_id );
}

/**
 * @deprecated 3.0
 */
function woocommerce_change_term_counts( $terms, $taxonomies, $args ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_change_term_counts' );
	return wc_change_term_counts( $terms, $taxonomies );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_product_ids_on_sale() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_product_ids_on_sale' );
	return wc_get_product_ids_on_sale();
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_featured_product_ids() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_featured_product_ids' );
	return wc_get_featured_product_ids();
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_product_terms( $object_id, $taxonomy, $fields = 'all' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_product_terms' );
	return wc_get_product_terms( $object_id, $taxonomy, array( 'fields' => $fields ) );
}

/**
 * @deprecated 3.0
 */
function woocommerce_product_post_type_link( $permalink, $post ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_product_post_type_link' );
	return wc_product_post_type_link( $permalink, $post );
}

/**
 * @deprecated 3.0
 */
function woocommerce_placeholder_img_src() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_placeholder_img_src' );
	return wc_placeholder_img_src();
}

/**
 * @deprecated 3.0
 */
function woocommerce_placeholder_img( $size = 'shop_thumbnail' ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_placeholder_img' );
	return wc_placeholder_img( $size );
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_formatted_variation( $variation = '', $flat = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_formatted_variation' );
	return wc_get_formatted_variation( $variation, $flat );
}

/**
 * @deprecated 3.0
 */
function woocommerce_scheduled_sales() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_scheduled_sales' );
	return wc_scheduled_sales();
}

/**
 * @deprecated 3.0
 */
function woocommerce_get_attachment_image_attributes( $attr ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_get_attachment_image_attributes' );
	return wc_get_attachment_image_attributes( $attr );
}

/**
 * @deprecated 3.0
 */
function woocommerce_prepare_attachment_for_js( $response ) {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_prepare_attachment_for_js' );
	return wc_prepare_attachment_for_js( $response );
}

/**
 * @deprecated 3.0
 */
function woocommerce_track_product_view() {
	wc_deprecated_function( __FUNCTION__, '3.0', 'wc_track_product_view' );
	return wc_track_product_view();
}

/**
 * @since 2.3
 * @deprecated has no replacement
 */
function woocommerce_compile_less_styles() {
	wc_deprecated_function( 'woocommerce_compile_less_styles', '2.3' );
}

/**
 * woocommerce_calc_shipping was an option used to determine if shipping was enabled prior to version 2.6.0. This has since been replaced with wc_shipping_enabled() function and
 * the woocommerce_ship_to_countries setting.
 * @since 2.6.0
 * @return string
 */
function woocommerce_calc_shipping_backwards_compatibility( $value ) {
	if ( defined( 'WC_UPDATING' ) ) {
		return $value;
	}
	return 'disabled' === get_option( 'woocommerce_ship_to_countries' ) ? 'no' : 'yes';
}
add_filter( 'pre_option_woocommerce_calc_shipping', 'woocommerce_calc_shipping_backwards_compatibility' );

/**
 * @deprecated 3.0.0
 * @see WC_Structured_Data class
 *
 * @return string
 */
function woocommerce_get_product_schema() {
	wc_deprecated_function( 'woocommerce_get_product_schema', '3.0' );

	global $product;

	$schema = "Product";

	// Downloadable product schema handling
	if ( $product->is_downloadable() ) {
		switch ( $product->download_type ) {
			case 'application' :
				$schema = "SoftwareApplication";
				break;
			case 'music' :
				$schema = "MusicAlbum";
				break;
			default :
				$schema = "Product";
				break;
		}
	}

	return 'http://schema.org/' . $schema;
}

/**
 * Save product price.
 *
 * This is a private function (internal use ONLY) used until a data manipulation api is built.
 *
 * @deprecated 3.0.0
 * @param int $product_id
 * @param float $regular_price
 * @param float $sale_price
 * @param string $date_from
 * @param string $date_to
 */
function _wc_save_product_price( $product_id, $regular_price, $sale_price = '', $date_from = '', $date_to = '' ) {
	wc_doing_it_wrong( '_wc_save_product_price()', 'This function is not for developer use and is deprecated.', '3.0' );

	$product_id    = absint( $product_id );
	$regular_price = wc_format_decimal( $regular_price );
	$sale_price    = '' === $sale_price ? '' : wc_format_decimal( $sale_price );
	$date_from     = wc_clean( $date_from );
	$date_to       = wc_clean( $date_to );

	update_post_meta( $product_id, '_regular_price', $regular_price );
	update_post_meta( $product_id, '_sale_price', $sale_price );

	// Save Dates
	update_post_meta( $product_id, '_sale_price_dates_from', $date_from ? strtotime( $date_from ) : '' );
	update_post_meta( $product_id, '_sale_price_dates_to', $date_to ? strtotime( $date_to ) : '' );

	if ( $date_to && ! $date_from ) {
		$date_from = strtotime( 'NOW', current_time( 'timestamp' ) );
		update_post_meta( $product_id, '_sale_price_dates_from', $date_from );
	}

	// Update price if on sale
	if ( '' !== $sale_price && '' === $date_to && '' === $date_from ) {
		update_post_meta( $product_id, '_price', $sale_price );
	} else {
		update_post_meta( $product_id, '_price', $regular_price );
	}

	if ( '' !== $sale_price && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
		update_post_meta( $product_id, '_price', $sale_price );
	}

	if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
		update_post_meta( $product_id, '_price', $regular_price );
		update_post_meta( $product_id, '_sale_price_dates_from', '' );
		update_post_meta( $product_id, '_sale_price_dates_to', '' );
	}
}

/**
 * Return customer avatar URL.
 *
 * @deprecated 3.1.0
 * @since 2.6.0
 * @param string $email the customer's email.
 * @return string the URL to the customer's avatar.
 */
function wc_get_customer_avatar_url( $email ) {
	// Deprecated in favor of WordPress get_avatar_url() function.
	wc_deprecated_function( 'wc_get_customer_avatar_url()', '3.1', 'get_avatar_url()' );

	return get_avatar_url( $email );
}
