<?php
/**
 * Product prices for shared links.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Enums\ProductType;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Builds product prices and keeps each provider's offer together.
 *
 * @internal
 */
class ProductOpenGraphPrices {

	/**
	 * Build prices and sale dates for products with a single price.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<string, string>
	 */
	public function get_tags( WC_Product $product ): array {
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


	/**
	 * Keep unchanged aliases in step with a filtered price or currency.
	 *
	 * @since 11.4.0
	 *
	 * @param array<string, string> $tags Filtered tags.
	 * @param array<string, string> $defaults Original tags.
	 * @return array<string, string>
	 */
	public function synchronize_aliases( array $tags, array $defaults ): array {
		foreach ( array(
			array( 'product:price:amount', 'og:price:amount', 'product:sale_price:amount' ),
			array( 'product:price:currency', 'og:price:currency', 'product:sale_price:currency' ),
		) as $properties ) {
			$changed = array();
			foreach ( $properties as $property ) {
				if ( isset( $tags[ $property ], $defaults[ $property ] ) && $tags[ $property ] !== $defaults[ $property ] ) {
					$changed[] = $tags[ $property ];
				}
			}
			if ( 1 !== count( array_unique( $changed ) ) ) {
				continue;
			}
			foreach ( $properties as $property ) {
				if ( isset( $tags[ $property ], $defaults[ $property ] ) && $tags[ $property ] === $defaults[ $property ] ) {
					$tags[ $property ] = $changed[0];
				}
			}
		}
		return $tags;
	}

	/**
	 * Describe the price tags that must stand down together on a conflict.
	 *
	 * @since 11.4.0
	 *
	 * @param array<string, string> $tags Filtered candidates.
	 * @return array{properties: string[], matches: callable(array): bool}
	 */
	public function get_group( array $tags ): array {
		return array(
			'properties' => array_values(
				array_filter(
					array_keys( $tags ),
					static function ( string $property ): bool {
						return (bool) preg_match( '/^(product:(sale_)?price(:|_)|og:price:)/', $property );
					}
				)
			),
			'matches'    => function ( array $existing ) use ( $tags ): bool {
				foreach ( $existing as $property => $values ) {
					foreach ( $values as $content ) {
						if ( $this->has_conflicting_price( $property, $content, $tags ) ) {
							return false;
						}
					}
				}
				return true;
			},
		);
	}

	/**
	 * Check whether existing pricing disagrees with the fallback metadata.
	 *
	 * @param string                $property Existing metadata property.
	 * @param string                $content Existing metadata value.
	 * @param array<string, string> $tags Filtered candidates.
	 * @return bool
	 */
	private function has_conflicting_price( string $property, string $content, array $tags ): bool {
		if ( in_array( $property, array( 'product:price:currency', 'og:price:currency', 'product:sale_price:currency' ), true ) ) {
			$values = $tags[ $property ] ?? $tags['product:price:currency'] ?? $tags['og:price:currency'] ?? $tags['product:sale_price:currency'] ?? '';
			return '' !== $values && strtoupper( trim( $content ) ) !== strtoupper( trim( (string) $values ) );
		}
		$values = $tags[ $property ] ?? '';
		if ( '' === $values && 'product:sale_price:amount' === $property ) {
			$values = $tags['product:price:amount'] ?? $tags['og:price:amount'] ?? '';
		}
		if ( '' === $values ) {
			return false;
		}
		$fallback = $values;
		if ( in_array( $property, array( 'product:price:amount', 'og:price:amount', 'product:sale_price:amount', 'og:price:standard_amount' ), true ) ) {
			return ! is_numeric( $content ) || (float) $content !== (float) $fallback;
		}

		return false;
	}
}
