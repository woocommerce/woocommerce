<?php
/**
 * Product search Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

defined( 'ABSPATH' ) || exit;

/**
 * Builds Open Graph metadata for product search results.
 *
 * @internal
 */
class ProductSearchOpenGraphTags {
	/**
	 * Build metadata for a product search.
	 *
	 * @since 11.4.0
	 * @param string $search Search query.
	 * @return array<array<string, string>>
	 */
	public function get_tags( string $search ): array {
		/* translators: %s: search query. */
		$title = sprintf( __( 'Search results for “%s”', 'woocommerce' ), $search );
		$page  = max( 1, (int) get_query_var( 'paged' ) );
		if ( $page > 1 ) {
			/* translators: 1: search title, 2: page number. */
			$title = sprintf( __( '%1$s – Page %2$d', 'woocommerce' ), $title, $page );
		}
		$url = wc_get_container()->get( CatalogOpenGraphUrl::class )->get_url();

		return wc_get_container()->get( CatalogOpenGraphTags::class )->get_tags( $title, $url );
	}
}
