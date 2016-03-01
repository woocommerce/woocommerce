<?php
/**
 * WooCommerce API Functions
 *
 * Functions for API specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses and formats a MySQL datetime (Y-m-d H:i:s) for ISO8601/RFC3339.
 *
 * Requered WP 4.4 or later.
 * See https://developer.wordpress.org/reference/functions/mysql_to_rfc3339/
 *
 * @param string       $date_gmt
 * @param string|null  $date
 * @return string|null ISO8601/RFC3339 formatted datetime.
 */
function wc_api_prepare_date_response( $date_gmt, $date = null ) {
	// Check if mysql_to_rfc3339 exists first!
	if ( ! function_exists( 'mysql_to_rfc3339' ) ) {
		return null;
	}

	// Use the date if passed.
	if ( isset( $date ) ) {
		return mysql_to_rfc3339( $date );
	}

	// Return null if $date_gmt is empty/zeros.
	if ( '0000-00-00 00:00:00' === $date_gmt ) {
		return null;
	}

	// Return the formatted datetime.
	return mysql_to_rfc3339( $date_gmt );
}
