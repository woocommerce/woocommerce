<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the JSON-LD blocks in a page, for store checks that look for schema.org
 * items whoever added them: WooCommerce, an SEO plugin or the theme.
 *
 * @since 11.4.0
 */
class JsonLd {

	/**
	 * The schema.org items of a type in a page's JSON-LD: items at the top of a block,
	 * in its `@graph`, or the `mainEntity` of one of those. Blocks that don't parse are skipped.
	 *
	 * @param string $html Page HTML.
	 * @param string ...$types Types to match, such as Product.
	 * @return array<int, array<string, mixed>>
	 */
	public static function items( string $html, string ...$types ): array {
		if ( ! preg_match_all( '#<script\b[^>]*\btype\s*=\s*["\']?application/ld\+json["\']?[^>]*>(.*?)</script>#is', $html, $blocks ) ) {
			return array();
		}
		$items = array();
		foreach ( $blocks[1] as $block ) {
			$data = json_decode( trim( $block ), true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			foreach ( self::top_level( $data ) as $item ) {
				$items[] = $item;
				if ( isset( $item['mainEntity'] ) && is_array( $item['mainEntity'] ) ) {
					array_push( $items, ...self::top_level( $item['mainEntity'] ) );
				}
			}
		}
		return array_values( array_filter( $items, static fn( array $item ): bool => self::is_type( $item, $types ) ) );
	}

	/**
	 * Whether an item has one of the types, written as `Product`, `schema:Product` or `https://schema.org/Product`.
	 *
	 * @param array<string, mixed> $item  Item.
	 * @param array<int, string>   $types Types.
	 */
	private static function is_type( array $item, array $types ): bool {
		foreach ( (array) ( $item['@type'] ?? array() ) as $type ) {
			if ( is_string( $type ) && in_array( preg_replace( '#^.*[/:]#', '', $type ), $types, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * An item, a list of items or an object with a `@graph`, as a list of items.
	 *
	 * @param array<mixed> $data Decoded JSON.
	 * @return array<int, array<string, mixed>>
	 */
	private static function top_level( array $data ): array {
		$list  = wp_is_numeric_array( $data ) ? $data : array( $data );
		$items = array();
		foreach ( $list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( isset( $item['@graph'] ) && is_array( $item['@graph'] ) ) {
				array_push( $items, ...array_filter( array_values( $item['@graph'] ), 'is_array' ) );
			}
			if ( isset( $item['@type'] ) ) {
				$items[] = $item;
			}
		}
		return $items;
	}
}
