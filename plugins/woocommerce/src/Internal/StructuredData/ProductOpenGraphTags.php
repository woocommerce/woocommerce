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
	 * Build metadata from the product and its featured image.
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
		$image = $this->get_image_tags( (int) $product->get_image_id(), $product->get_name() );
		if ( $image ) {
			$tags[] = $image;
		}
		$tags[] = array( 'twitter:card' => $image ? 'summary_large_image' : 'summary' );

		$brand_ids = $product->get_brand_ids();
		if ( is_array( $brand_ids ) && 1 === count( $brand_ids ) ) {
			$brand = get_term( reset( $brand_ids ), 'product_brand' );
			if ( $brand instanceof \WP_Term ) {
				$tags[] = array( 'product:brand' => $brand->name );
				$tags[] = array( 'og:brand' => $brand->name );
			}
		}
		if ( wc_review_ratings_enabled() && $product->get_rating_count() > 0 ) {
			$tags[] = array(
				'og:rating'       => (string) $product->get_average_rating(),
				'og:rating_scale' => '5',
				'og:rating_count' => (string) $product->get_rating_count(),
			);
		}

		foreach ( wc_get_container()->get( ProductMediaSources::class )->get_sources( $product ) as $media => $source ) {
			$root  = 'og:' . $media;
			$group = array();
			foreach ( $source as $detail => $value ) {
				$group[ 'url' === $detail ? $root : $root . ':' . $detail ] = $value;
			}
			if ( 'https' === wp_parse_url( $source['url'], PHP_URL_SCHEME ) ) {
				$group[ $root . ':secure_url' ] = $source['url'];
			}
			$tags[] = $group;
		}

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
