<?php
/**
 * Product Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductType;
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
		$gallery   = $product->get_gallery_image_ids();
		$image_ids = array_merge( array( $product->get_image_id() ), is_array( $gallery ) ? $gallery : array() );
		$image_ids = array_slice( array_unique( array_filter( array_map( 'intval', $image_ids ) ) ), 0, 6 );
		if ( ! empty( $image_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $image_ids );
		}
		$urls = array();
		foreach ( $image_ids as $image_id ) {
			$image = wc_get_container()->get( OpenGraphImageTags::class )->get_tags( $image_id, $product->get_name() );
			if ( $image && ! in_array( $image['og:image'], $urls, true ) ) {
				$tags[] = $image;
				$urls[] = $image['og:image'];
			}
		}
		$tags[] = array( 'twitter:card' => $urls ? 'summary_large_image' : 'summary' );

		$availability = array(
			ProductStockStatus::IN_STOCK     => array(
				'product:availability' => 'in stock',
				'og:availability'      => 'instock',
			),
			ProductStockStatus::OUT_OF_STOCK => array(
				'product:availability' => 'out of stock',
				'og:availability'      => 'out of stock',
			),
			ProductStockStatus::ON_BACKORDER => array(
				'product:availability' => 'available for order',
				'og:availability'      => 'backorder',
			),
		);
		foreach ( $availability[ $product->get_stock_status() ] ?? array() as $property => $value ) {
			$tags[] = array( $property => $value );
		}

		foreach ( $this->get_price_tags( $product ) as $property => $value ) {
			$tags[] = array( $property => $value );
		}

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
	 * Build prices and sale dates for products with a single price.
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<string, string>
	 */
	private function get_price_tags( WC_Product $product ): array {
		if ( ! $product->is_type( array( ProductType::SIMPLE, ProductType::EXTERNAL ) ) || '' === $product->get_price() ) {
			return array();
		}

		$price    = wc_format_decimal( wc_get_price_to_display( $product ), wc_get_price_decimals() );
		$currency = get_woocommerce_currency();
		$tags     = array(
			'product:price:amount'   => $price,
			'product:price:currency' => $currency,
			'og:price:amount'        => $price,
			'og:price:currency'      => $currency,
		);
		if ( $product->is_on_sale() ) {
			$tags['product:sale_price:amount']   = $price;
			$tags['product:sale_price:currency'] = $currency;
			$tags['og:price:standard_amount']    = wc_format_decimal( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_decimals() );
			foreach ( array(
				'start' => $product->get_date_on_sale_from(),
				'end'   => $product->get_date_on_sale_to(),
			) as $boundary => $date ) {
				if ( $date ) {
					$tags[ 'product:sale_price_dates:' . $boundary ] = $date->format( DATE_ATOM );
					$tags[ 'og:price:' . $boundary . '_date' ]       = $date->format( DATE_ATOM );
				}
			}
		}

		return $tags;
	}
}
