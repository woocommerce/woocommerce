<?php
/**
 * Shop Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Builds Open Graph metadata for the shop page.
 *
 * @internal
 */
class ShopOpenGraphTags {
	/**
	 * Build metadata from the configured shop page.
	 *
	 * @since 11.4.0
	 * @param WP_Post $page Shop page.
	 * @return array<array<string, string>>
	 */
	public function get_tags( WP_Post $page ): array {
		$description = wc_get_container()->get( OpenGraphDescription::class )->clean( $page->post_excerpt );
		$tags        = array(
			array( 'og:title' => get_the_title( $page ) ),
			array( 'og:type' => 'website' ),
			array( 'og:url' => get_permalink( $page ) ),
			array( 'og:description' => wc_trim_string( $description ) ),
			array( 'og:site_name' => get_bloginfo( 'name' ) ),
		);

		$image_id = get_post_thumbnail_id( $page );
		$image    = $image_id ? wc_get_container()->get( OpenGraphImageTags::class )->get_tags( $image_id, null, true ) : array();
		if ( $image ) {
			$tags[] = $image;
		}
		$tags[] = array( 'twitter:card' => $image ? 'summary_large_image' : 'summary' );

		return $tags;
	}
}
