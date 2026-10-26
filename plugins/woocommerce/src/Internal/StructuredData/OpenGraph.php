<?php
/**
 * Product Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductType;
use WC_Product;
use WP_HTML_Tag_Processor;
use WP_HTML_Text_Replacement;

defined( 'ABSPATH' ) || exit;

/**
 * Provides product link previews and fills missing Open Graph metadata.
 */
class OpenGraph {

	/**
	 * Metadata candidates keyed by property.
	 *
	 * @var array<string, string[]>
	 */
	private $tags = array();

	/**
	 * Default Twitter card before filtering, or null when omitted.
	 *
	 * @var string|null
	 */
	private $default_card = null;

	/**
	 * Register the frontend callback.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_template_redirect' ) );
	}

	/**
	 * Prepare metadata for a public, unprotected product before rendering the template.
	 *
	 * @internal
	 */
	public function handle_template_redirect(): void {
		/**
		 * Filters whether WooCommerce may output Open Graph metadata.
		 *
		 * @since 11.4.0
		 *
		 * @param bool $enabled Whether metadata is enabled.
		 */
		if ( ! apply_filters( 'woocommerce_enable_open_graph', true ) ) {
			return;
		}

		if ( ! is_singular( 'product' ) || is_preview() || 'yes' === get_option( 'woocommerce_coming_soon' ) ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product instanceof WC_Product || ProductStatus::PUBLISH !== $product->get_status() || '' !== $product->get_post_password() ) {
			return;
		}

		$this->tags = $this->prepare_tags( $this->get_product_tags( $product ), $product );
		if ( $this->tags ) {
			add_filter( 'wp_template_enhancement_output_buffer', array( $this, 'handle_wp_template_enhancement_output_buffer' ), PHP_INT_MAX );
		}
	}

	/**
	 * Filter and validate metadata before rendering starts.
	 *
	 * @param array<string, string|string[]>                  $tags   Metadata candidates.
	 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
	 * @return array<string, string[]>
	 */
	private function prepare_tags( array $tags, $context ): array {
		$this->default_card = isset( $tags['twitter:card'] ) && is_string( $tags['twitter:card'] ) ? $tags['twitter:card'] : null;
		$default_tags       = $tags;

		/**
		 * Filters Open Graph metadata before output. Currently applied only to product pages.
		 * Other page types may be supported later; callbacks must check the object type.
		 * Removing og:type from $tags also preserves an existing provider's type.
		 * Unchanged price aliases follow filtered prices and currencies.
		 *
		 * @since 11.4.0
		 *
		 * @param array<string, string|string[]> $tags Metadata keyed by og:, twitter:, or product: property name. Use an array for multiple og:see_also URLs. Remove a key to omit a tag.
		 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
		 */
		$tags = apply_filters( 'woocommerce_open_graph_tags', $tags, $context );
		if ( ! is_array( $tags ) ) {
			return array();
		}

		$prepared = array();
		foreach ( $tags as $property => $value ) {
			if ( ! is_string( $property ) || ! preg_match( '/^(og|twitter|product):[a-z_0-9:]+$/', $property ) ) {
				continue;
			}
			$values = 'og:see_also' === $property && is_array( $value ) ? $value : array( $value );
			foreach ( $values as $content ) {
				if ( ! is_string( $content ) || '' === trim( $content ) ) {
					continue;
				}
				if ( in_array( $property, array( 'og:url', 'og:image', 'og:see_also' ), true ) ) {
					$content = esc_url_raw( $content );
				}
				if ( '' !== $content ) {
					$prepared[ $property ][] = $content;
				}
			}
			if ( 'og:see_also' === $property && isset( $prepared[ $property ] ) ) {
				$prepared[ $property ] = array_slice( array_values( array_unique( $prepared[ $property ] ) ), 0, 6 );
			}
		}

		foreach ( array(
			array( 'product:price:amount', 'og:price:amount', 'product:sale_price:amount' ),
			array( 'product:price:currency', 'og:price:currency', 'product:sale_price:currency' ),
		) as $properties ) {
			$changed_values = array();
			foreach ( $properties as $property ) {
				if ( isset( $prepared[ $property ], $default_tags[ $property ] ) && $prepared[ $property ][0] !== $default_tags[ $property ] ) {
					$changed_values[] = $prepared[ $property ][0];
				}
			}
			if ( 1 !== count( array_unique( $changed_values ) ) ) {
				continue;
			}
			foreach ( $properties as $property ) {
				if ( isset( $prepared[ $property ], $default_tags[ $property ] ) && $prepared[ $property ][0] === $default_tags[ $property ] ) {
					$prepared[ $property ] = array( $changed_values[0] );
				}
			}
		}

		return $prepared;
	}

	/**
	 * Add metadata only after inspecting the rendered head for existing tags.
	 *
	 * @internal
	 *
	 * @param mixed $html Filtered template output.
	 * @return mixed Template output, with WooCommerce metadata added when appropriate.
	 */
	public function handle_wp_template_enhancement_output_buffer( $html ) {
		if ( ! is_string( $html ) || ! $this->tags ) {
			return $html;
		}

		$processor         = new WP_HTML_Tag_Processor( $html );
		$tags              = $this->tags;
		$has_image         = false;
		$head_complete     = false;
		$template_depth    = 0;
		$conflicting_price = false;

		while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			if ( 'TEMPLATE' === $processor->get_tag() ) {
				$template_depth = max( 0, $template_depth + ( $processor->is_tag_closer() ? -1 : 1 ) );
			}
			if ( $template_depth > 0 ) {
				continue;
			}
			// The HTML spec allows the closing head tag to be omitted, so we also check for the body tag to determine head completion.
			if ( ( 'HEAD' === $processor->get_tag() && $processor->is_tag_closer() ) || ( 'BODY' === $processor->get_tag() && ! $processor->is_tag_closer() ) ) {
				$head_complete = true;
				break;
			}

			if ( 'META' !== $processor->get_tag() ) {
				continue;
			}

			foreach ( self::get_meta_properties( $processor ) as $property ) {
				$content = $processor->get_attribute( 'content' );
				if ( 'og:type' === $property && isset( $this->tags['og:type'] ) ) {
					$processor->set_attribute( 'content', html_entity_decode( $this->tags['og:type'][0], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				} elseif ( ! is_string( $content ) || '' === trim( $content ) ) {
					continue;
				}
				if ( is_string( $content ) && $this->has_conflicting_price( $property, $content ) ) {
					$conflicting_price = true;
				}
				if ( in_array( $property, array( 'og:image', 'og:image:url' ), true ) ) {
					$has_image = true;
				}
				if ( ! isset( $tags['og:image'] ) || 0 !== strpos( $property, 'og:image:' ) ) {
					unset( $tags[ $property ] );
				}
			}
		}

		if ( $conflicting_price ) {
			// Don't combine fallback prices with a different currency or price from another provider.
			foreach ( array_keys( $tags ) as $property ) {
				if ( preg_match( '/^(product:(sale_)?price(:|_)|og:price:)/', $property ) ) {
					unset( $tags[ $property ] );
				}
			}
		}

		if ( $has_image ) {
			// WooCommerce's image details may not describe the existing provider's image.
			foreach ( array_keys( $tags ) as $property ) {
				if ( 'og:image' === $property || 0 === strpos( $property, 'og:image:' ) ) {
					unset( $tags[ $property ] );
				}
			}
		}

		if ( ! $head_complete ) {
			return $html;
		}

		if ( isset( $tags['twitter:card'] ) && $tags['twitter:card'][0] === $this->default_card ) {
			$tags['twitter:card'] = array( $has_image || ! empty( $tags['og:image'] ) ? 'summary_large_image' : 'summary' );
		}

		return self::insert_tags( $processor->get_updated_html(), $tags );
	}

	/**
	 * Check whether existing pricing disagrees with the fallback metadata.
	 *
	 * @param string $property Existing metadata property.
	 * @param string $content Existing metadata value.
	 * @return bool
	 */
	private function has_conflicting_price( string $property, string $content ): bool {
		if ( in_array( $property, array( 'product:price:currency', 'og:price:currency', 'product:sale_price:currency' ), true ) ) {
			$fallback = $this->tags[ $property ][0] ?? $this->tags['product:price:currency'][0] ?? $this->tags['og:price:currency'][0] ?? $this->tags['product:sale_price:currency'][0] ?? null;
			return null !== $fallback && strtoupper( trim( $content ) ) !== strtoupper( trim( $fallback ) );
		}
		$fallback = $this->tags[ $property ][0] ?? null;
		if ( null === $fallback && 'product:sale_price:amount' === $property ) {
			$fallback = $this->tags['product:price:amount'][0] ?? $this->tags['og:price:amount'][0] ?? null;
		}
		if ( null === $fallback ) {
			return false;
		}
		if ( in_array( $property, array( 'product:price:amount', 'og:price:amount', 'product:sale_price:amount', 'og:price:standard_amount' ), true ) ) {
			return ! is_numeric( $content ) || (float) $content !== (float) $fallback;
		}

		return false;
	}

	/**
	 * Escape metadata and insert it at the end of the head.
	 *
	 * @param string                  $html Template output.
	 * @param array<string, string[]> $tags Metadata to insert.
	 * @return string
	 */
	private static function insert_tags( string $html, array $tags ): string {
		// Match core's insertion pattern until the HTML API provides a public insertion method.
		$inserter = new class( $html ) extends WP_HTML_Tag_Processor {
			/**
			 * Insert markup before the current tag.
			 *
			 * @param string $markup HTML to insert.
			 */
			public function insert_before( string $markup ): void {
				$this->set_bookmark( 'here' );
				$this->lexical_updates[] = new WP_HTML_Text_Replacement( $this->bookmarks['here']->start, 0, $markup );
			}
		};

		$replace_image_details         = isset( $tags['og:image'] );
		$image_details                 = array_filter(
			$tags,
			static function ( string $property ): bool {
				return 0 === strpos( $property, 'og:image:' );
			},
			ARRAY_FILTER_USE_KEY
		);
		$image_details['og:image:url'] = $image_details['og:image:url'] ?? $tags['og:image'] ?? array();
		$template_depth                = 0;
		while ( $inserter->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			if ( 'TEMPLATE' === $inserter->get_tag() ) {
				$template_depth = max( 0, $template_depth + ( $inserter->is_tag_closer() ? -1 : 1 ) );
			}
			if ( $template_depth > 0 ) {
				continue;
			}
			if ( ( 'HEAD' === $inserter->get_tag() && $inserter->is_tag_closer() ) || ( 'BODY' === $inserter->get_tag() && ! $inserter->is_tag_closer() ) ) {
				$inserter->insert_before( self::render_tags( $tags ) );
				return $inserter->get_updated_html();
			}
			$content = $inserter->get_attribute( 'content' );
			foreach ( self::get_meta_properties( $inserter ) as $property ) {
				if ( $replace_image_details && 0 === strpos( $property, 'og:image:' ) ) {
					$inserter->set_attribute( 'content', html_entity_decode( $image_details[ $property ][0] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					unset( $tags[ $property ] );
				} elseif ( isset( $tags[ $property ] ) && ( ! is_string( $content ) || '' === trim( $content ) ) ) {
					// Decode existing entities because set_attribute() always encodes ampersands.
					$inserter->set_attribute( 'content', html_entity_decode( array_shift( $tags[ $property ] ) ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					if ( ! $tags[ $property ] ) {
						unset( $tags[ $property ] );
					}
				}
			}
			if ( 'BODY' === $inserter->get_tag() ) {
				break;
			}
		}

		return $html;
	}

	/**
	 * Read metadata property names from the current tag.
	 *
	 * @param WP_HTML_Tag_Processor $processor HTML processor at a tag.
	 * @return string[]
	 */
	private static function get_meta_properties( WP_HTML_Tag_Processor $processor ): array {
		$properties = array();
		if ( 'META' !== $processor->get_tag() ) {
			return $properties;
		}
		foreach ( array( 'property', 'name' ) as $attribute ) {
			$value = $processor->get_attribute( $attribute );
			if ( is_string( $value ) ) {
				$names = preg_split( '/\s+/', strtolower( trim( $value ) ) );
				if ( false !== $names ) {
					$properties = array_merge( $properties, $names );
				}
			}
		}
		return $properties;
	}

	/**
	 * Escape metadata for output in the head.
	 *
	 * @param array<string, string[]> $tags Metadata to render.
	 * @return string
	 */
	private static function render_tags( array $tags ): string {
		$markup = '';
		foreach ( $tags as $property => $values ) {
			foreach ( $values as $value ) {
				$content = in_array( $property, array( 'og:url', 'og:image', 'og:see_also' ), true ) ? esc_url( $value ) : esc_attr( $value );
				if ( '' !== $content ) {
					$attribute = 0 === strpos( $property, 'twitter:' ) ? 'name' : 'property';
					$markup   .= '<meta ' . esc_attr( $attribute ) . '="' . esc_attr( $property ) . '" content="' . $content . '" />' . "\n";
				}
			}
		}

		return $markup;
	}

	/**
	 * Build metadata from the product and its featured image.
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<string, string|string[]>
	 */
	private function get_product_tags( WC_Product $product ): array {
		$description          = $product->get_short_description();
		$description          = '' !== $description ? $description : $product->get_description();
		$description          = wp_strip_all_tags( strip_shortcodes( $description ) );
		$description          = trim( (string) preg_replace( '/\s+/u', ' ', $description ) );
		$tags                 = array(
			'og:title'       => $product->get_name(),
			'og:type'        => 'product',
			'og:url'         => $product->get_permalink(),
			'og:description' => wc_trim_string( $description ),
		);
		$tags                += $this->get_site_tags();
		$tags                += $this->get_image_tags( (int) $product->get_image_id(), $product->get_name() );
		$tags['twitter:card'] = isset( $tags['og:image'] ) ? 'summary_large_image' : 'summary';

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
		$tags        += $availability[ $product->get_stock_status() ] ?? array();
		if ( isset( $tags['og:locale'] ) ) {
			$tags['product:locale'] = $tags['og:locale'];
		}

		$brand_ids = $product->get_brand_ids();
		if ( 1 === count( $brand_ids ) ) {
			$brand = get_term( reset( $brand_ids ), 'product_brand' );
			if ( $brand instanceof \WP_Term ) {
				$tags['product:brand'] = $brand->name;
				$tags['og:brand']      = $brand->name;
			}
		}
		if ( wc_review_ratings_enabled() && $product->get_rating_count() > 0 ) {
			$tags['og:rating']       = (string) $product->get_average_rating();
			$tags['og:rating_scale'] = '5';
			$tags['og:rating_count'] = (string) $product->get_rating_count();
		}

		$tags += $this->get_price_tags( $product );
		$tags += $this->get_identifier_tags( $product->get_global_unique_id() );
		$urls  = $this->get_related_urls( $product );
		if ( $urls ) {
			$tags['og:see_also'] = $urls;
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

	/**
	 * Identify GTIN, UPC, EAN and ISBN values without treating arbitrary IDs as barcodes.
	 *
	 * @param string $identifier Product's global unique ID.
	 * @return array<string, string>
	 */
	private function get_identifier_tags( string $identifier ): array {
		$gtin = preg_replace( '/[\s-]+/', '', $identifier );
		if ( ! is_string( $gtin ) || ! preg_match( '/^(?!0+$)([0-9]{8}|[0-9]{12,14})$/', $gtin ) ) {
			return array();
		}
		$sum    = 0;
		$weight = 1;
		for ( $i = strlen( $gtin ) - 1; $i >= 0; --$i ) {
			$sum   += (int) $gtin[ $i ] * $weight;
			$weight = 4 - $weight;
		}
		if ( 0 !== $sum % 10 ) {
			return array();
		}

		$tags = array( 'product:gtin' => $gtin );
		if ( 12 === strlen( $gtin ) ) {
			$tags['og:upc'] = $gtin;
		} elseif ( in_array( strlen( $gtin ), array( 8, 13 ), true ) ) {
			$tags['og:ean'] = $gtin;
			if ( 13 === strlen( $gtin ) && ( 0 === strpos( $gtin, '978' ) || preg_match( '/^979[1-9]/', $gtin ) ) ) {
				$tags['product:isbn'] = $gtin;
				$tags['og:isbn']      = $gtin;
			}
		}

		return $tags;
	}

	/**
	 * Get up to six public upsell URLs on the same domain.
	 *
	 * @param WC_Product $product Product being shared.
	 * @return string[]
	 */
	private function get_related_urls( WC_Product $product ): array {
		$urls = array();
		$ids  = $product->get_upsell_ids();
		if ( ! empty( $ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $ids );
			foreach ( $ids as $id ) {
				$related = wc_get_product( $id );
				if ( ! $related || $related->get_id() === $product->get_id() || ProductStatus::PUBLISH !== $related->get_status() || '' !== $related->get_post_password() || ! $related->is_visible() ) {
					continue;
				}
				$url = $related->get_permalink();
				if ( wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( $product->get_permalink(), PHP_URL_HOST ) ) {
					$urls[] = $url;
				}
				if ( 6 === count( $urls ) ) {
					break;
				}
			}
		}

		return $urls;
	}

	/**
	 * Build metadata shared by all supported pages.
	 *
	 * @return array<string, string>
	 */
	private function get_site_tags(): array {
		$tags   = array( 'og:site_name' => get_bloginfo( 'name' ) );
		$locale = get_locale();
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
