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
		add_action( 'wp_ajax_woocommerce_add_dismissed_marketplace_suggestion', array( __CLASS__, 'post_add_dismissed_suggestion_handler' ) );

		// Register hooks for rendering suggestions container markup.
		add_action( 'wc_marketplace_suggestions_products_empty_state', array( __CLASS__, 'render_products_list_empty_state' ) );
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
	 * @return bool
	 */
	public static function allow_suggestions() {
		// We currently only support English suggestions.
		$locale             = get_locale();
		$suggestion_locales = array(
			'en_AU',
			'en_CA',
			'en_GB',
			'en_NZ',
			'en_US',
			'en_ZA',
		);
		if ( ! in_array( $locale, $suggestion_locales, true ) ) {
			return false;
		}

		// Suggestions are only displayed if user can install plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// User can disabled all suggestions via filter.
		return apply_filters( 'woocommerce_allow_marketplace_suggestions', true );
	}

	/**
	 * Test json API data.
	 *
	 * @return string fake JSON data
	 */
	private static function get_test_suggestions_data() {
		$fake_suggestions = '[
			{
				"slug": "product-edit-empty-header",
				"context": "product-edit-empty-header",
				"title": "Recommended extensions",
				"allow-dismiss": false
			},
			{
				"slug": "product-edit-empty-footer-browse-all",
				"context": "product-edit-empty-footer",
				"link-text": "Browse all extensions",
				"url": "https://woocommerce.com/product-category/woocommerce-extensions/",
				"allow-dismiss": false
			},
			{
				"slug": "product-edit-variation-images",
				"hide-if-installed": "woocommerce-additional-variation-images",
				"context": "product-edit-meta-tab-body",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Additional Variation Images",
				"copy": "Add gallery images.",
				"button-text": "From $49",
				"url": "https://woocommerce.com/products/woocommerce-additional-variation-images/"
			},
			{
				"slug": "product-edit-min-max-quantities",
				"hide-if-installed": "woocommerce-additional-variation-images",
				"context": "product-edit-meta-tab-body",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Min/Max Quantities",
				"copy": "Specify min & max allowed product quantities.",
				"button-text": "From $29",
				"url": "https://woocommerce.com/products/min-max-quantities/"
			},
			{
				"slug": "orders-empty-header",
				"context": "orders-list-empty-header",
				"title": "Tools for your store",
				"allow-dismiss": false
			},
			{
				"slug": "orders-empty-footer-browse-all",
				"context": "orders-list-empty-footer",
				"link-text": "Browse all extensions",
				"url": "https://woocommerce.com/product-category/woocommerce-extensions/",
				"allow-dismiss": false
			},
			{
				"slug": "orders-empty-name-your-price",
				"context": "orders-list-empty-body",
				"hide-if-installed": "woocommerce-name-your-price",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Name Your Price",
				"copy": "Allow customers to define the product price.",
				"button-text": "From $149",
				"url": "https://woocommerce.com/products/name-your-price/"
			},
			{
				"slug": "orders-empty-zapier",
				"context": "orders-list-empty-body",
				"hide-if-installed": "woocommerce-zapier",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Zapier",
				"copy": "Integrate your store with more than 1000 cloud services.",
				"button-text": "From $59",
				"url": "https://woocommerce.com/products/zapier/"
			},
			{
				"slug": "orders-empty-shipment-tracking",
				"context": "orders-list-empty-body",
				"hide-if-installed": "woocommerce-shipment-tracking",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Shipment Tracking",
				"copy": "Add shipment tracking info to your orders.",
				"button-text": "From $49",
				"url": "https://woocommerce.com/products/shipment-tracking/"
			},
			{
				"slug": "orders-empty-table-rate-shipping",
				"context": "orders-list-empty-body",
				"hide-if-installed": "woocommerce-table-rate-shipping",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Table Rate Shipping",
				"copy": "Advanced, flexible shipping.",
				"button-text": "From $99",
				"url": "https://woocommerce.com/products/table-rate-shipping/"
			},
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
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
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
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Product Add-Ons",
				"copy": "Offer add-ons like gift wrapping, special messages or other special options for your products.",
				"button-text": "From $149",
				"url": "https://woocommerce.com/products/product-add-ons/"
			},
			{
				"slug": "products-empty-product-bundles",
				"context": "products-list-empty-body",
				"hide-if-installed": "woocommerce-product-bundles",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Product Bundles",
				"copy": "Offer customizable bundles and assembled products.",
				"button-text": "From $49",
				"url": "https://woocommerce.com/products/product-bundles/"
			},
			{
				"slug": "products-empty-composite-products",
				"context": "products-list-empty-body",
				"title": "Composite Products",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"copy": "Create and offer product kits with configurable components.",
				"button-text": "From $79",
				"url": "https://woocommerce.com/products/composite-products/"
			},
			{
				"slug": "products-list-enhancements-category",
				"context": "products-list-inline",
				"icon": "https://woocommerce.com/wp-content/uploads/2018/06/icon.png",
				"title": "Looking to optimize your product pages?",
				"button-text": "Explore enhancements",
				"url": "https://woocommerce.com/product-category/woocommerce-extensions/product-extensions/"
			}
		]';
		return json_decode( $fake_suggestions );
	}

	/**
	 * Pull suggestion data from remote endpoint & cache in a transient.
	 *
	 * @return array of json API data
	 */
	public static function get_suggestions_api_data() {
		return self::get_test_suggestions_data();

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
		set_transient( 'wc_marketplace_suggestions', array(), DAY_IN_SECONDS );
		return array();
	}
}

WC_Marketplace_Suggestions::init();

