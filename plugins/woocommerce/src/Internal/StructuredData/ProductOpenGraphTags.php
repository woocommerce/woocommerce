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
		$description = wc_get_container()->get( OpenGraphDescription::class )->clean( $description );
		$tags        = array(
			array( 'og:title' => $product->get_name() ),
			array( 'og:type' => 'product' ),
			array( 'og:url' => $product->get_permalink() ),
			array( 'og:description' => wc_trim_string( $description ) ),
		);
		foreach ( $this->get_site_tags() as $property => $value ) {
			$tags[] = array( $property => $value );
		}
		$image = wc_get_container()->get( OpenGraphImageTags::class )->get_tags( (int) $product->get_image_id(), $product->get_name() );
		if ( $image ) {
			$tags[] = $image;
		}
		$tags[] = array( 'twitter:card' => $image ? 'summary_large_image' : 'summary' );

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

}
