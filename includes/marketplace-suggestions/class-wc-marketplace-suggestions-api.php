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
 * Initialise
 */
function wc_marketplace_suggestions_api_init() {
	add_action( 'wp_ajax_marketplace_suggestions', 'get_marketplace_suggestions' );
}

/**
 * Suggestion data GET handler.
 */
function get_marketplace_suggestions() {
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

	wp_die();
}

wc_marketplace_suggestions_api_init();
