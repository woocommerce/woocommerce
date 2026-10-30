<?php
/**
 * Product taxonomy Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Builds Open Graph metadata for product taxonomy archives.
 *
 * @internal
 */
class ProductTaxonomyOpenGraphTags {
	/**
	 * Build metadata for a product category, brand or tag.
	 *
	 * @since 11.4.0
	 * @param WP_Term $term Current product taxonomy term.
	 * @return array<array<string, string>>
	 */
	public function get_tags( WP_Term $term ): array {
		$page  = max( 1, (int) get_query_var( 'paged' ) );
		$title = $term->name;
		if ( $page > 1 ) {
			/* translators: 1: archive name, 2: page number. */
			$title = sprintf( __( '%1$s – Page %2$d', 'woocommerce' ), $title, $page );
		}
		$url = wc_get_container()->get( CatalogOpenGraphUrl::class )->get_url();

		return wc_get_container()->get( CatalogOpenGraphTags::class )->get_tags( $title, $url, $term->description, get_term_meta( $term->term_id, 'thumbnail_id', true ) );
	}
}
