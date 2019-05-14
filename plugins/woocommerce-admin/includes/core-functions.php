<?php
/**
 * Core Functions
 *
 * Holds core functions for wc-admin.
 *
 * @package WC_Admin\Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Format a number using the decimal and thousands separator settings in WooCommerce.
 *
 * @param mixed $number Number to be formatted.
 * @return string
 */
function wc_admin_number_format( $number ) {
	$currency_settings = WC_Admin_Loader::get_currency_settings();
	return number_format(
		$number,
		0,
		$currency_settings['decimal_separator'],
		$currency_settings['thousand_separator']
	);
}

/**
 * Retrieves a URL to relative path inside WooCommerce admin with
 * the provided query parameters.
 *
 * @param  string $path Relative path of the desired page.
 * @param  array  $query Query parameters to append to the path.
 *
 * @return string       Fully qualified URL pointing to the desired path.
 */
function wc_admin_url( $path, $query = array() ) {
	if ( ! empty( $query ) ) {
		$query_string = http_build_query( $query );
		$path         = $path . '?' . $query_string;
	}

	return admin_url( 'admin.php?page=wc-admin#' . $path, dirname( __FILE__ ) );
}
