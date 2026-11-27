<?php
/**
 * Product gallery video sources.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Reads configured gallery video independently of its metadata output format.
 *
 * @internal
 */
class ProductMediaSources {

	/**
	 * Find the first usable gallery video when product gallery videos are enabled.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<string, array<string, string>>
	 */
	public function get_sources( WC_Product $product ): array {
		if ( ProductMediaGallery::is_feature_enabled() ) {
			$videos = ProductMediaGallery::get_stored_video_gallery_items( $product );
			$ids    = array_filter( array_map( 'absint', array_merge( array_column( $videos, 'id' ), array_column( $videos, 'poster_id' ) ) ) );
			if ( ! empty( $ids ) ) {
				// Prime caches to reduce future queries.
				_prime_post_caches( array_unique( $ids ), false, false );
			}
		}

		$sources = array();
		foreach ( ProductMediaGallery::get_product_media_gallery_items( $product, array( 'resolve_video_posters' => false ) ) as $item ) {
			if ( 'video' !== ( $item['media_type'] ?? '' ) ) {
				continue;
			}
			$id       = (int) ( $item['id'] ?? 0 );
			$url      = $id ? wp_get_attachment_url( $id ) : ( $item['url'] ?? '' );
			$metadata = $id ? wp_get_attachment_metadata( $id ) : array();
			$metadata = is_array( $metadata ) ? $metadata : array();
			if ( $id ) {
				$metadata['type'] = get_post_mime_type( $id );
			}
			$source = self::get_media_details( is_string( $url ) ? $url : '', $metadata );
			if ( $source ) {
				$sources['video'] = $source;
				break;
			}
		}

		return $sources;
	}

	/**
	 * Validate a gallery video URL and its available MIME type and dimensions.
	 *
	 * @param string               $url Media URL.
	 * @param array<string, mixed> $metadata Media details.
	 * @return array<string, string>
	 */
	private static function get_media_details( string $url, array $metadata ): array {
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		if ( ! in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
			return array();
		}
		$details = array( 'url' => $url );
		$type    = $metadata['type'] ?? wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ) )['type'];
		if ( is_string( $type ) && 0 === strpos( $type, 'video/' ) ) {
			$details['type'] = $type;
		}
		foreach ( array( 'width', 'height' ) as $dimension ) {
			$value = $metadata[ $dimension ] ?? null;
			if ( is_scalar( $value ) && ctype_digit( (string) $value ) && (int) $value > 0 ) {
				$details[ $dimension ] = (string) $value;
			}
		}
		return $details;
	}
}
