<?php
/**
 * API initialization for ai plugin.
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

/**
 * API initialization for ai plugin.
 *
 * @package Woo_AI
 */

/**
 * Register the Woo AI route.
 */
function woo_ai_rest_api_init() {
	error_log( 'this one is not' );
	require_once dirname( __FILE__ ) . '/product-data-suggestion/class-product-data-suggestion-api.php';
}
error_log( 'this log entry is showing up' );
add_action( 'rest_api_init', 'woo_ai_rest_api_init' );
