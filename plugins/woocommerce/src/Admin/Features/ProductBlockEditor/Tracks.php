<?php
/**
 * WooCommerce Product Block Editor
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Add tracks for the product block editor.
 */
class Tracks {

	/**
	 * Initialize the tracks.
	 */
	public function init() {
		add_filter( 'woocommerce_product_source', array( $this, 'add_product_source' ) );
	}

	/**
	 * Check if a URL is a product editor page.
	 *
	 * @param string $url Url to check.
	 * @return boolean
	 */
	protected function is_product_editor_page( $url ) {
		$query_string = wp_parse_url( wp_get_referer(), PHP_URL_QUERY );
		parse_str( $query_string, $query );

		if ( ! isset( $query['page'] ) || 'wc-admin' !== $query['page'] || ! isset( $query['path'] ) ) {
			return false;
		}

		$path_pieces = explode( '/', $query['path'] );
		$route       = $path_pieces[1];

		return 'add-product' === $route || 'product' === $route;
	}

	/**
	 * Update the product source if we're on the product editor page.
	 *
	 * @param string $source Source of product.
	 * @return string
	 */
	public function add_product_source( $source ) {
		if ( $this->is_product_editor_page( wp_get_referer() ) ) {
			return 'product-block-editor-v1';
		}

		return $source;
	}

}
