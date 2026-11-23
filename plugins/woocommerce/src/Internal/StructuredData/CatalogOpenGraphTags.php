<?php
/**
 * Shared catalog Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

defined( 'ABSPATH' ) || exit;

/**
 * Builds metadata shared by product archives and search results.
 *
 * @internal
 */
class CatalogOpenGraphTags {
	/**
	 * Build metadata from a title, URL and optional term details.
	 *
	 * @since 11.4.0
	 * @param string $title Page title.
	 * @param string $url Canonical page URL.
	 * @param string $description Term description, when available.
	 * @param mixed  $term_image_id Term image ID, when available.
	 * @return array<array<string, string>>
	 */
	public function get_tags( string $title, string $url, string $description = '', $term_image_id = 0 ): array {
		$shop = get_post( wc_get_page_id( 'shop' ) );
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
			$term_image_id,
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
