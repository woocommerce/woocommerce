<?php
/**
 * WooCommerce Product Editor Helper
 */

 namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Product Editor helper class.
 */
class ProductEditorHelper {

	/**
	 * Get the parsed WooCommerce Admin path.
	 *
	 * @return array Parsed route details.
	 */
	public static function get_parsed_route(): array {
		if ( ! \Automattic\WooCommerce\Admin\PageController::is_admin_page() || ! isset( $_GET['path'] ) ) {
			return array(
				'page'       => null,
				'product_id' => null,
			);
		}

		$path        = esc_url_raw( wp_unslash( $_GET['path'] ) );
		$path_pieces = explode( '/', wp_parse_url( $path, PHP_URL_PATH ) );

		return array(
			'page'       => $path_pieces[1],
			'product_id' => 'product' === $path_pieces[1] ? absint( $path_pieces[2] ) : null,
		);
	}
}
