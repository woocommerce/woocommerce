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
	$suggestion_data = get_transient( 'wc_marketplace_suggestions' );
	if ( false !== $suggestion_data ) {
		return $suggestion_data;
	}

	$suggestion_data_url = 'https://d3t0oesq8995hv.cloudfront.net/add-ons/marketplace-suggestions.json';
	$raw_suggestions     = wp_remote_get(
		$suggestion_data_url,
		array( 'user-agent' => 'WooCommerce Marketplace Suggestions' )
	);

	// Parse the data to check for any errors.
	// If it's valid, store structure in transient.
	if ( ! is_wp_error( $raw_suggestions ) ) {
		$suggestions = json_decode( wp_remote_retrieve_body( $raw_suggestions ) );
		if ( $suggestions && is_array( $suggestions ) ) {
			set_transient( 'wc_marketplace_suggestions', $suggestions, WEEK_IN_SECONDS );
			return $suggestions;
		}
	}

	// Cache empty suggestions data to reduce requests if there are any issues with API.
	set_transient( 'wc_marketplace_suggestions', '[]', DAY_IN_SECONDS );
	return array();
}

/**
 * Suggestion data GET handler.
 */
function wc_marketplace_suggestions_ajax_handler() {
	$suggestion_data = wc_marketplace_suggestions_get_api_data();
	wp_send_json_success( $suggestion_data );
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
