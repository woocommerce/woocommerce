<?php
/**
 * REST API Marketplace suggestions API
 *
 * Handles requests for marketplace suggestions data
 *
 * @package WooCommerce/API
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pull suggestion data from remote endpoint & cache in a transient.
 *
 * @return array of json API data
 */
function wc_marketplace_suggestions_get_api_data() {
	$suggestion_data_url = 'https://d3t0oesq8995hv.cloudfront.net/add-ons/suggestions.json';

	$suggestion_data = get_transient( 'wc_marketplace_suggestions' );

	if ( false !== $suggestion_data ) {
		return $suggestion_data;
	}

	$raw_suggestions = wp_safe_remote_get(
		$suggestion_data_url,
		array( 'user-agent' => 'WooCommerce Marketplace Suggestions' )
	);
	if ( is_wp_error( $raw_suggestions ) ) {
		return array();
	}

	// Parse the data to check for any errors.
	// If it's valid, store structure in transient.
	$suggestions = json_decode( wp_remote_retrieve_body( $raw_suggestions ) );
	if ( $suggestions && is_object( $suggestions ) ) {
		set_transient( 'wc_marketplace_suggestions', $suggestions, WEEK_IN_SECONDS );
		return $suggestions;
	}

	return array();
}

/**
 * Suggestion data GET handler.
 */
function wc_marketplace_suggestions_ajax_handler() {
	$suggestion_data = wc_marketplace_suggestions_get_api_data();

	if ( $suggestion_data ) {
		echo wp_json_encode( $suggestion_data );
	} else {
		// Return temporary hard-coded test data until we get our API data in place.
		echo '[
			{
				"slug": "products-empty-memberships",
				"context": "products-list-empty-body",
				"title": "Selling something else?",
				"copy": "Extensions allow you to sell other types of products including bookings, subscriptions, or memberships.",
				"button-text": "Browse extensions",
				"url": "https://woocommerce.com/product-category/woocommerce-extensions/product-extensions/"
			},
			{
				"slug": "products-empty-addons",
				"context": "products-list-empty-body",
				"show-if-installed": [
					"woocommerce-subscriptions",
					"woocommerce-memberships"
				],
				"title": "Product Add-Ons",
				"copy": "Offer add-ons like gift wrapping, special messages or other special options for your products.",
				"button-text": "From $149",
				"url": "https://woocommerce.com/products/product-add-ons/"
			},
			{
				"slug": "products-empty-product-bundles",
				"context": "products-list-empty-body",
				"hide-if-installed": "woocommerce-product-bundles",
				"title": "Product Bundles",
				"copy": "Offer customizable bundles and assembled products.",
				"button-text": "From $49",
				"url": "https://woocommerce.com/products/product-bundles/"
			},
			{
				"slug": "products-empty-composite-products",
				"context": "products-list-empty-body",
				"title": "Composite Products",
				"copy": "Create and offer product kits with configurable components.",
				"button-text": "From $79",
				"url": "https://woocommerce.com/products/composite-products/"
			},
			{
				"slug": "products-list-enhancements-category",
				"context": "products-list-inline",
				"title": "Looking to optimize your product pages?",
				"button-text": "Explore enhancements",
				"url": "https://woocommerce.com/product-category/woocommerce-extensions/product-extensions/"
			}
		]';
	}

	wp_die();
}

/**
 * Initialise
 */
function wc_marketplace_suggestions_api_init() {
	add_action( 'wp_ajax_marketplace_suggestions', 'wc_marketplace_suggestions_ajax_handler' );
}
wc_marketplace_suggestions_api_init();
