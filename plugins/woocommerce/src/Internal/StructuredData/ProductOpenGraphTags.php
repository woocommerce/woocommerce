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
