<?php
/**
 * Open Graph image metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

defined( 'ABSPATH' ) || exit;

/**
 * Builds metadata for an attachment image.
 *
 * @internal
 */
class OpenGraphImageTags {
	/**
	 * Build an image group, or return an empty array when the image is unavailable.
	 *
	 * @param int         $image_id Image attachment ID.
	 * @param string|null $fallback_alt Fallback when the image has no alt text.
	 * @param bool        $secure_url Whether to include the secure URL.
	 * @return array<string, string>
	 */
	public function get_tags( int $image_id, ?string $fallback_alt = null, bool $secure_url = false ): array {
		$image = wp_get_attachment_image_src( $image_id, 'full' );
		if ( ! $image ) {
			return array();
		}

		$tags = array( 'og:image' => $image[0] );
		if ( $secure_url && 'https' === wp_parse_url( $image[0], PHP_URL_SCHEME ) ) {
			$tags['og:image:secure_url'] = $image[0];
		}
		if ( $image[1] > 0 && $image[2] > 0 ) {
			$tags['og:image:width']  = (string) $image[1];
			$tags['og:image:height'] = (string) $image[2];
		}
		$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		if ( is_string( $alt ) && '' !== trim( $alt ) ) {
			$tags['og:image:alt'] = $alt;
		} elseif ( null !== $fallback_alt ) {
			$tags['og:image:alt'] = $fallback_alt;
		}
		$tags['og:image:type'] = (string) get_post_mime_type( $image_id );

		return $tags;
	}
}
