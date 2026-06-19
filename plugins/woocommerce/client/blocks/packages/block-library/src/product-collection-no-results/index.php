<?php
/**
 * Server-side rendering of the `woocommerce/product-collection-no-results` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;

/**
 * Renders the `woocommerce/product-collection-no-results` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_collection_no_results( $attributes, $content, $block ): string {
	$content = trim( $content );

	if ( empty( $content ) || ! $block instanceof WP_Block ) {
		return '';
	}

	$query = ProductCollectionUtils::prepare_and_execute_query( $block );

	if ( $query->post_count > 0 ) {
		return '';
	}

	$updated_html_content = modify_block_woocommerce_product_collection_no_results_anchor_urls( $content );
	$wrapper_attributes   = get_block_wrapper_attributes();

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$updated_html_content
	);
}

/**
 * Updates the "clear filters" and "Store's home" link URLs.
 *
 * @since 11.0.0
 *
 * @param string $content Block content.
 * @return string Updated block content.
 */
function modify_block_woocommerce_product_collection_no_results_anchor_urls( string $content ): string {
	$processor = new WP_HTML_Tag_Processor( trim( $content ) );

	if ( $processor->next_tag(
		array(
			'tag_name'   => 'a',
			'class_name' => 'wc-link-clear-any-filters',
		)
	) ) {
		$processor->set_attribute( 'href', get_block_woocommerce_product_collection_no_results_current_url_without_filters() );
	}

	if ( $processor->next_tag(
		array(
			'tag_name'   => 'a',
			'class_name' => 'wc-link-stores-home',
		)
	) ) {
		$processor->set_attribute( 'href', home_url() );
	}

	return $processor->get_updated_html();
}

/**
 * Gets the current URL without product filter query parameters.
 *
 * @since 11.0.0
 *
 * @return string Current URL without product filter query parameters.
 */
function get_block_woocommerce_product_collection_no_results_current_url_without_filters(): string {
	$protocol = is_ssl() ? 'https' : 'http';

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$http_host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	$http_host   = sanitize_text_field( $http_host );
	$request_uri = esc_url_raw( $request_uri );
	$current_url = $protocol . '://' . $http_host . $request_uri;
	$parsed_url  = wp_parse_url( $current_url );

	if ( ! is_array( $parsed_url ) ) {
		return home_url();
	}

	$query_string = $parsed_url['query'] ?? '';
	$query_params = array();

	if ( is_string( $query_string ) ) {
		parse_str( $query_string, $query_params );
	}

	$params_to_remove = array( 'min_price', 'max_price', 'rating_filter', 'filter_', 'query_type_' );
	foreach ( array_keys( $query_params ) as $key ) {
		if ( ! is_string( $key ) ) {
			continue;
		}

		foreach ( $params_to_remove as $param ) {
			if ( 0 === strpos( $key, $param ) ) {
				unset( $query_params[ $key ] );
				break;
			}
		}
	}

	$new_query_string = http_build_query( $query_params );
	$scheme           = is_string( $parsed_url['scheme'] ?? null ) ? $parsed_url['scheme'] : $protocol;
	$host             = is_string( $parsed_url['host'] ?? null ) ? $parsed_url['host'] : $http_host;
	$path             = is_string( $parsed_url['path'] ?? null ) ? $parsed_url['path'] : '';

	$new_url = $scheme . '://' . $host . $path;
	$new_url .= $new_query_string ? '?' . $new_query_string : '';

	return $new_url;
}

/**
 * Registers the `woocommerce/product-collection-no-results` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_collection_no_results(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_collection_no_results',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_collection_no_results' );
