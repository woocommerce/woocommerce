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

		$shop        = get_post( wc_get_page_id( 'shop' ) );
		$description = $term->description;
		if ( '' === trim( wp_strip_all_tags( strip_shortcodes( $description ) ) ) && $shop instanceof \WP_Post && 'publish' === $shop->post_status && '' === $shop->post_password ) {
			$description = $shop->post_excerpt;
		}
		if ( '' === trim( wp_strip_all_tags( strip_shortcodes( $description ) ) ) ) {
			$description = get_bloginfo( 'description' );
		}
		$description = wc_get_container()->get( OpenGraphDescription::class )->clean( $description );
		$tags        = array(
			array( 'og:title' => $title ),
			array( 'og:type' => 'website' ),
			array( 'og:url' => $url ),
			array( 'og:description' => wc_trim_string( $description ) ),
			array( 'og:site_name' => get_bloginfo( 'name' ) ),
		);
		$locale      = get_locale();
		if ( preg_match( '/^[a-z]{2,3}_[A-Z]{2}$/', $locale ) ) {
			$tags[] = array( 'og:locale' => $locale );
		}

		$image_ids = array(
			get_term_meta( $term->term_id, 'thumbnail_id', true ),
			$shop instanceof \WP_Post && 'publish' === $shop->post_status && '' === $shop->post_password ? get_post_thumbnail_id( $shop ) : 0,
			get_theme_mod( 'custom_logo' ),
			get_option( 'site_icon' ),
		);
		$has_image = false;
		foreach ( $image_ids as $image_id ) {
			if ( ! is_numeric( $image_id ) || (int) $image_id <= 0 ) {
				continue;
			}
			$image = wc_get_container()->get( OpenGraphImageTags::class )->get_tags( (int) $image_id, $title, true );
			if ( ! $image ) {
				continue;
			}
			$tags[]    = $image;
			$has_image = true;
			break;
		}
		$tags[] = array( 'twitter:card' => $has_image ? 'summary_large_image' : 'summary' );

		return $tags;
	}
}
