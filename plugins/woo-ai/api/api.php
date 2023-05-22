<?php
/**
 * API initialization for ai plugin.
 *
 * @package Woo_AI
 */

/**
 * Register the Woo AI route.
 */
function woo_ai_rest_api_init(): void {
	require_once 'text-generation/class-text-generation.php';
}

add_action( 'rest_api_init', 'woo_ai_rest_api_init' );
