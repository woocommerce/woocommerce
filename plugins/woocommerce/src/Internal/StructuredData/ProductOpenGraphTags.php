<?php
/**
 * Product Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Builds Open Graph metadata for a product page.
 *
 * @internal
 */
class ProductOpenGraphTags {

	/**
	 * Build metadata from the product and its gallery images.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<array<string, string>>
	 */
	public function get_tags( WC_Product $product ): array {
		$description = $product->get_short_description();
		$description = '' !== $description ? $description : $product->get_description();
		$description = wp_strip_all_tags( strip_shortcodes( $description ) );
		// Collapse Unicode whitespace, including NBSP, which wp_strip_all_tags() leaves intact.
		$description = trim( (string) preg_replace( '/\s+/u', ' ', $description ) );
		$tags        = array(
			array( 'og:title' => $product->get_name() ),
			array( 'og:type' => 'product' ),
			array( 'og:url' => $product->get_permalink() ),
			array( 'og:description' => wc_trim_string( $description ) ),
		);
		foreach ( $this->get_site_tags() as $property => $value ) {
			$tags[] = array( $property => $value );
		}
		$gallery   = $product->get_gallery_image_ids();
		$image_ids = array_merge( array( $product->get_image_id() ), is_array( $gallery ) ? $gallery : array() );
		$image_ids = array_slice( array_unique( array_filter( array_map( 'intval', $image_ids ) ) ), 0, 6 );
		if ( ! empty( $image_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $image_ids );
		}
		$urls = array();
		foreach ( $image_ids as $image_id ) {
			$image = $this->get_image_tags( $image_id, $product->get_name() );
			if ( $image && ! in_array( $image['og:image'], $urls, true ) ) {
				$tags[] = $image;
				$urls[] = $image['og:image'];
			}
		}
		$tags[] = array( 'twitter:card' => $urls ? 'summary_large_image' : 'summary' );

		return $tags;
	}

	/**
	 * Build metadata shared by all supported pages.
	 *
	 * @return array<string, string>
	 */
	private function get_site_tags(): array {
		$tags   = array( 'og:site_name' => get_bloginfo( 'name' ) );
		$locale = get_locale();
		// Accept only language_TERRITORY locales, such as en_US.
		if ( preg_match( '/^[a-z]{2,3}_[A-Z]{2}$/', $locale ) ) {
			$tags['og:locale'] = $locale;
		}

		return $tags;
	}

	/**
	 * Build metadata for an attachment and its image properties.
	 *
	 * @param int    $image_id Attachment ID.
	 * @param string $title Fallback alternative text.
	 * @return array<string, string>
	 */
	private function get_image_tags( int $image_id, string $title ): array {
		$tags  = array();
		$image = wp_get_attachment_image_src( $image_id, 'full' );
		if ( $image ) {
			$tags['og:image'] = $image[0];
			if ( $image[1] > 0 && $image[2] > 0 ) {
				$tags['og:image:width']  = (string) $image[1];
				$tags['og:image:height'] = (string) $image[2];
			}
			$alt                   = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$tags['og:image:alt']  = is_string( $alt ) && '' !== trim( $alt ) ? $alt : $title;
			$tags['og:image:type'] = (string) get_post_mime_type( $image_id );
		}

		return $tags;
	}
}
