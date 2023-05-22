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
	require_once dirname( __FILE__ ) . '/text-generation/class-text-generation.php';
	require_once dirname( __FILE__ ) . '/attribute-suggestion/class-attribute-suggestion-api.php';
}

add_action( 'rest_api_init', 'woo_ai_rest_api_init' );
