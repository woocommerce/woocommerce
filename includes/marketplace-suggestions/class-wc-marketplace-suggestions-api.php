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
				"slug": "products-empty-header-product-types",
				"context": "products-list-empty-header",
				"title": "Other types of products",
				"allow-dismiss": false
			},
			{
				"slug": "products-empty-footer-browse-all",
				"context": "products-list-empty-footer",
				"link-text": "Browse all extensions",
				"url": "https://woocommerce.com/product-category/woocommerce-extensions/",
				"allow-dismiss": false
			},
			{
				"slug": "products-empty-memberships",
				"context": "products-list-empty-body",
				"hide-if-installed": "woocommerce-memberships",
				"title": "Memberships",
				"copy": "Give members access to restricted content or products, for a fee or for free.",
				"button-text": "From $149",
				"url": "https://woocommerce.com/products/woocommerce-memberships/"
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
 * Return an array of suggestions the user has dismissed.
 */
function wc_marketplace_suggestions_get_dismissed() {
	$dismissed_suggestions = array();

	$dismissed_suggestions_data = get_user_option( 'wc_marketplace_suggestions_dismissed_suggestions', get_current_user_id() );
	if ( $dismissed_suggestions_data ) {
		$dismissed_suggestions = json_decode( $dismissed_suggestions_data );
		if ( ! is_array( $dismissed_suggestions ) ) {
			$dismissed_suggestions = array();
		}
	}

	return $dismissed_suggestions;
}

/**
 * Suggestion data GET handler.
 */
function wc_marketplace_suggestions_dismiss_handler() {
	if ( ! check_ajax_referer( 'add_dismissed_marketplace_suggestion' ) ) {
		wp_die();
	}

	$post_data       = wp_unslash( $_POST );
	$suggestion_slug = sanitize_text_field( $post_data['slug'] );
	if ( ! $suggestion_slug ) {
		wp_die();
	}

	$dismissed_suggestions = wc_marketplace_suggestions_get_dismissed();

	if ( in_array( $suggestion_slug, $dismissed_suggestions, true ) ) {
		wp_die();
	}

	$dismissed_suggestions[] = $suggestion_slug;

	// Could also store these in transient, with a long expiry (e.g. 6 months).
	update_user_option(
		get_current_user_id(),
		'wc_marketplace_suggestions_dismissed_suggestions',
		wp_json_encode( $dismissed_suggestions )
	);

	wp_die();
}

/**
 * Initialise
 */
function wc_marketplace_suggestions_api_init() {
	add_action( 'wp_ajax_marketplace_suggestions', 'wc_marketplace_suggestions_ajax_handler' );
	add_action( 'wp_ajax_add_dismissed_marketplace_suggestion', 'wc_marketplace_suggestions_dismiss_handler' );
}
wc_marketplace_suggestions_api_init();
