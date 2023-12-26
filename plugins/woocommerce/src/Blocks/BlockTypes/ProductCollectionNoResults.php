<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductCollectionUtils;

/**
 * ProductCollectionNoResults class.
 */
class ProductCollectionNoResults extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-collection-no-results';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$content = trim( $content );
		if ( empty( $content ) ) {
			return '';
		}

		$query = ProductCollectionUtils::prepare_and_execute_query( $block );

		// If the query has products, don't render the block.
		if ( $query->post_count > 0 ) {
			return '';
		}

		// Update the anchor tag URLs.
		$updated_html_content = $this->modify_anchor_tag_urls( trim( $content ) );

		$wrapper_attributes = get_block_wrapper_attributes();
		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$updated_html_content
		);
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Set the URL attributes for "clearing any filters" and "Store's home" links.
	 *
	 * @param string $content Block content.
	 */
	protected function modify_anchor_tag_urls( $content ) {
		$processor = new \WP_HTML_Tag_Processor( trim( $content ) );

		// Set the URL attribute for the "clear any filters" link.
		if ( $processor->next_tag(
			array(
				'tag_name'   => 'a',
				'class_name' => 'wc-link-clear-any-filters',
			)
		) ) {
			$processor->set_attribute( 'href', $this->get_current_url_without_filters() );
		}

		// Set the URL attribute for the "Store's home" link.
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
	 * Get current URL without filter query parameters which will be used
	 * for the "clear any filters" link.
	 */
	protected function get_current_url_without_filters() {
		$protocol = is_ssl() ? 'https' : 'http';

		// Check the existence and sanitize HTTP_HOST and REQUEST_URI in the $_SERVER superglobal.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$http_host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		// Sanitize the host and URI.
		$http_host   = sanitize_text_field( $http_host );
		$request_uri = esc_url_raw( $request_uri );

		// Construct the full URL.
		$current_url = $protocol . '://' . $http_host . $request_uri;

		// Parse the URL to extract the query string.
		$parsed_url   = wp_parse_url( $current_url );
		$query_string = isset( $parsed_url['query'] ) ? $parsed_url['query'] : '';

		// Convert the query string into an associative array.
		parse_str( $query_string, $query_params );

		// Remove the filter query parameters.
		$params_to_remove = array( 'min_price', 'max_price', 'rating_filter', 'filter_', 'query_type_' );
		foreach ( $query_params as $key => $value ) {
			foreach ( $params_to_remove as $param ) {
				if ( strpos( $key, $param ) === 0 ) {
					unset( $query_params[ $key ] );
					break;
				}
			}
		}

		// Rebuild the query string without the removed parameters.
		$new_query_string = http_build_query( $query_params );

		// Reconstruct the URL.
		$new_url  = $parsed_url['scheme'] . '://' . $parsed_url['host'];
		$new_url .= isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
		$new_url .= $new_query_string ? '?' . $new_query_string : '';

		return $new_url;
	}

}
