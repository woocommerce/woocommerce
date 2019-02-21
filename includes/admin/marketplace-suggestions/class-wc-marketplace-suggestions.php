<?php
/**
 * REST API Marketplace suggestions API
 *
 * Handles requests for marketplace suggestions data & rendering
 * templates for suggestion DOM content.
 *
 * @package WooCommerce\Classes
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Marketplace suggestions API and related logic.
 */
class WC_Marketplace_Suggestions {

	/**
	 * Initialise.
	 */
	public static function init() {
		if ( ! self::allow_suggestions() ) {
			return;
		}

		// Register ajax api handlers.
		add_action( 'wp_ajax_woocommerce_marketplace_suggestions', array( __CLASS__, 'get_suggestion_json_data_handler' ) );
		add_action( 'wp_ajax_woocommerce_add_dismissed_marketplace_suggestion', array( __CLASS__, 'post_add_dismissed_suggestion_handler' ) );

		// Register hooks for rendering suggestions container markup.
		add_action( 'wc_marketplace_suggestions_products_empty_state', array( __CLASS__, 'render_products_list_empty_state' ) );
	}

	/**
	 * Suggestion data GET handler.
	 */
	public static function get_suggestion_json_data_handler() {
		$suggestion_data = self::get_suggestions_api_data();
		wp_send_json_success( $suggestion_data );
	}

	/**
	 * Return an array of suggestions the user has dismissed.
	 */
	public static function get_dismissed_suggestions() {
		$dismissed_suggestions = array();

		$dismissed_suggestions_data = get_user_meta( get_current_user_id(), 'wc_marketplace_suggestions_dismissed_suggestions', true );
		if ( $dismissed_suggestions_data ) {
			$dismissed_suggestions = $dismissed_suggestions_data;
			if ( ! is_array( $dismissed_suggestions ) ) {
				$dismissed_suggestions = array();
			}
		}

		return $dismissed_suggestions;
	}

	/**
	 * POST handler for adding a dismissed suggestion.
	 */
	public static function post_add_dismissed_suggestion_handler() {
		if ( ! check_ajax_referer( 'add_dismissed_marketplace_suggestion' ) ) {
			wp_die();
		}

		$post_data       = wp_unslash( $_POST );
		$suggestion_slug = sanitize_text_field( $post_data['slug'] );
		if ( ! $suggestion_slug ) {
			wp_die();
		}

		$dismissed_suggestions = self::get_dismissed_suggestions();

		if ( in_array( $suggestion_slug, $dismissed_suggestions, true ) ) {
			wp_die();
		}

		$dismissed_suggestions[] = $suggestion_slug;
		update_user_meta(
			get_current_user_id(),
			'wc_marketplace_suggestions_dismissed_suggestions',
			$dismissed_suggestions
		);

		wp_die();
	}

	/**
	 * Render suggestions containers in products list empty state.
	 */
	public static function render_products_list_empty_state() {
		self::render_suggestions_container( 'products-list-empty-header' );
		self::render_suggestions_container( 'products-list-empty-body' );
		self::render_suggestions_container( 'products-list-empty-footer' );
	}

	/**
	 * Render a suggestions container element, with the specified context.
	 *
	 * @param string $context Suggestion context name (rendered as a css class).
	 */
	public static function render_suggestions_container( $context ) {
		include 'templates/container.php';
	}

	/**
	 * Should suggestions be displayed?
	 *
	 * User can disabled all suggestions via option.
	 * We also currently only support English suggestions.
	 *
	 * @return bool
	 */
	public static function allow_suggestions() {
		$option = get_option( 'woocommerce_allow_marketplace_suggestions', 'yes' );
		$allow  = ( 'yes' === $option || 'true' === $option || 1 === $option );

		$locale             = get_locale();
		$suggestion_locales = array(
			'en_AU',
			'en_CA',
			'en_GB',
			'en_NZ',
			'en_US',
			'en_ZA',
		);
		$language_supported = in_array( $locale, $suggestion_locales, true );

		return ( $allow && $language_supported );
	}

	/**
	 * Pull suggestion data from remote endpoint & cache in a transient.
	 *
	 * @return array of json API data
	 */
	private static function get_suggestions_api_data() {
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
}

WC_Marketplace_Suggestions::init();
