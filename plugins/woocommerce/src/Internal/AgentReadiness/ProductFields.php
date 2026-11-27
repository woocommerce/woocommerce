<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Product values as a product feed reads them, shared by the readiness checks.
 *
 * @since 11.4.0
 */
class ProductFields {

	/**
	 * Characters normalise_gtin() removes: hyphen, ASCII whitespace, no-break
	 * space, figure space, narrow no-break space and the byte order mark.
	 */
	public const GTIN_SEPARATORS = array( '-', ' ', "\t", "\n", "\r", "\x0B", "\x0C", "\u{00A0}", "\u{2007}", "\u{202F}", "\u{FEFF}" );

	/**
	 * Plain-text description, falling back to the short description, as feeds do.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function text( \WC_Product $product ): string {
		$text = self::plain_text( $product->get_description() );
		if ( '' === $text ) {
			$text = self::plain_text( $product->get_short_description() );
		}
		return $text;
	}

	/**
	 * Text left once shortcodes, HTML and entities such as &nbsp; are gone.
	 *
	 * @param string $html Stored description.
	 */
	private static function plain_text( string $html ): string {
		$text = html_entity_decode( wp_strip_all_tags( strip_shortcodes( $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/[\s\x{00A0}\x{2007}\x{202F}\x{FEFF}]+/u', ' ', $text ) );
	}

	/**
	 * The product's brand names, comma separated, or '' when it has none: the
	 * product_brand taxonomy, the Perfect Brands and YITH brand taxonomies when
	 * registered, then a "brand" attribute.
	 *
	 * @param \WC_Product $product Product, not a variation.
	 */
	public static function brand( \WC_Product $product ): string {
		foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand' ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				return implode( ', ', wp_list_pluck( $terms, 'name' ) );
			}
		}
		$values = array_map( 'trim', self::attribute_values( $product, array( 'brand' ) ) );
		return implode( ', ', array_diff( $values, array( '' ) ) );
	}

	/**
	 * The brand Google for WooCommerce sends for the product when it has its
	 * own value set, or ''. Google reads it; other channels don't.
	 *
	 * @param \WC_Product $product Product, not a variation.
	 */
	public static function google_feed_brand( \WC_Product $product ): string {
		return trim( (string) $product->get_meta( '_wc_gla_brand' ) );
	}

	/**
	 * The product's MPN from Google for WooCommerce or an "MPN" attribute, or ''.
	 *
	 * @param \WC_Product $product Product, not a variation.
	 */
	public static function mpn( \WC_Product $product ): string {
		$mpn = trim( (string) $product->get_meta( '_wc_gla_mpn' ) );
		if ( '' !== $mpn ) {
			return $mpn;
		}
		$values = array_map( 'trim', self::attribute_values( $product, array( 'mpn' ) ) );
		return implode( ', ', array_diff( $values, array( '' ) ) );
	}

	/**
	 * Whether the feeds expect a brand and GTIN for this product. Google lets a
	 * product with no assigned identifiers set identifier_exists to false; gift
	 * cards and virtual or downloadable products are treated as such here.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function needs_identifiers( \WC_Product $product ): bool {
		if ( in_array( $product->get_type(), array( 'gift-card', 'pw-gift-card' ), true ) ) {
			return false;
		}
		if ( 'yes' === $product->get_meta( '_gift_card' ) ) {
			return false;
		}
		return ! $product->is_virtual() && ! $product->is_downloadable();
	}

	/**
	 * Whether the product carries its own price. Bundles, composites and
	 * grouped products are priced from the items the shopper picks.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function has_own_price( \WC_Product $product ): bool {
		return ! in_array( $product->get_type(), array( 'bundle', 'composite', 'grouped' ), true );
	}

	/**
	 * Product instance whose variations were last read.
	 *
	 * @var \WC_Product|null
	 */
	private static ?\WC_Product $cached_variation_product = null;

	/**
	 * Published variations of the cached product instance.
	 *
	 * @var array<int, \WC_Product_Variation>
	 */
	private static array $cached_variations = array();

	/**
	 * Published variations of a variable product; empty for other types. Kept
	 * for the last product instance asked about, since every rule asks about the
	 * same instance in turn. Another instance of the same product is read again.
	 *
	 * @param \WC_Product $product Product.
	 * @return array<int, \WC_Product_Variation>
	 */
	public static function variations( \WC_Product $product ): array {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return array();
		}
		if ( self::$cached_variation_product === $product ) {
			return self::$cached_variations;
		}

		$variations = array();
		foreach ( self::published_variation_ids( $product ) as $child_id ) {
			$variation = new \WC_Product_Variation( $child_id );
			if ( $variation->get_id() > 0 && 'publish' === $variation->get_status() ) {
				$variations[] = $variation;
			}
		}
		self::$cached_variation_product = $product;
		self::$cached_variations        = $variations;
		return $variations;
	}

	/**
	 * Drop the remembered variations of a product, so the next read sees a variation removed in this request.
	 *
	 * @param int $product_id Product id.
	 */
	public static function forget_variations( int $product_id ): void {
		if ( self::$cached_variation_product instanceof \WC_Product && self::$cached_variation_product->get_id() === $product_id ) {
			self::$cached_variation_product = null;
			self::$cached_variations        = array();
		}
	}

	/**
	 * Published variation ids, from the posts table. The children transient and the
	 * posts query cache can still list a variation after this request trashed it.
	 *
	 * @param \WC_Product $product Variable product.
	 * @return array<int, int>
	 */
	private static function published_variation_ids( \WC_Product $product ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The cached children query still returns a variation this request trashed.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'product_variation' AND post_status = 'publish'",
				$product->get_id()
			)
		);
		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Normalised GTINs on the product and its published variations.
	 *
	 * @param \WC_Product $product Product.
	 * @return array<int, string>
	 */
	public static function gtins( \WC_Product $product ): array {
		$gtins = array();
		foreach ( array_merge( array( $product ), self::variations( $product ) ) as $item ) {
			$gtin = self::normalise_gtin( (string) $item->get_global_unique_id() );
			if ( '' !== $gtin ) {
				$gtins[] = $gtin;
			}
		}
		return array_values( array_unique( $gtins ) );
	}

	/**
	 * Remove the hyphens and spaces, including non-breaking ones, that core
	 * allows inside a GTIN and that imports leave around it. The duplicate
	 * GTIN query strips the same characters.
	 *
	 * @param string $gtin Stored GTIN.
	 */
	public static function normalise_gtin( string $gtin ): string {
		return str_replace( self::GTIN_SEPARATORS, '', $gtin );
	}

	/**
	 * The key two GTINs share when they name the same item: GTIN-8, -12 and -13
	 * are GTIN-14 with leading zeros dropped.
	 *
	 * @param string $gtin Normalised GTIN.
	 */
	public static function gtin_key( string $gtin ): string {
		return ltrim( $gtin, '0' );
	}

	/**
	 * GS1 GTIN-8, -12, -13 or -14 with a valid check digit.
	 *
	 * @see https://support.google.com/merchants/answer/6324461
	 *
	 * @param string $gtin Normalised GTIN.
	 */
	public static function is_valid_gtin( string $gtin ): bool {
		if ( ! preg_match( '/^(\d{8}|\d{12,14})$/', $gtin ) ) {
			return false;
		}

		$digits = array_map( 'intval', str_split( strrev( substr( $gtin, 0, -1 ) ) ) );
		$sum    = 0;
		foreach ( $digits as $position => $digit ) {
			$sum += 0 === $position % 2 ? $digit * 3 : $digit;
		}
		return ( 10 - ( $sum % 10 ) ) % 10 === (int) substr( $gtin, -1 );
	}

	/**
	 * Values of the product's attributes whose name contains one of $names.
	 *
	 * @param \WC_Product        $product Product.
	 * @param array<int, string> $names   Lower-case name fragments.
	 * @return array<int, string>
	 */
	public static function attribute_values( \WC_Product $product, array $names ): array {
		$values = array();
		foreach ( $product->get_attributes() as $key => $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute ) {
				continue;
			}
			$label = strtolower( $attribute->get_name() . ' ' . (string) $key );
			foreach ( $names as $name ) {
				if ( false === strpos( $label, $name ) ) {
					continue;
				}
				if ( $attribute->is_taxonomy() ) {
					foreach ( (array) $attribute->get_terms() as $term ) {
						$values[] = $term->name;
					}
				} else {
					$values = array_merge( $values, $attribute->get_options() );
				}
				break;
			}
		}
		return array_values( array_unique( array_map( 'strval', $values ) ) );
	}

	/**
	 * The images a channel lists the product with: its own, or, for a variable product, each
	 * published variation's image (its own, else the parent's), since every variation lists as
	 * an item. Empty when there is none.
	 *
	 * @param \WC_Product $product Product.
	 * @return int[]
	 */
	public static function listing_image_ids( \WC_Product $product ): array {
		$variations = self::variations( $product );
		if ( array() === $variations ) {
			$variations = array( $product );
		}
		return array_values( array_filter( array_unique( array_map( array( self::class, 'image_id' ), $variations ) ) ) );
	}

	/**
	 * The featured image's attachment id, or 0 when it is unset or the
	 * attachment no longer exists.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function image_id( \WC_Product $product ): int {
		$image_id = (int) $product->get_image_id();
		return $image_id > 0 && 'attachment' === get_post_type( $image_id ) ? $image_id : 0;
	}
}
