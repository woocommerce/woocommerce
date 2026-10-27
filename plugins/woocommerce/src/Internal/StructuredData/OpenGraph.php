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
use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;
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
		 * @param array<string, scalar|scalar[]> $tags Metadata keyed by namespaced property name, including custom namespaces. Arrays repeat a property; structured details use the same indexes as their parent values. Remove a key to omit a tag.
		 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
		 */
		$tags = apply_filters( 'woocommerce_open_graph_tags', $tags, $context );
		if ( ! is_array( $tags ) ) {
			return array();
		}

		$prepared = array();
		foreach ( $tags as $property => $value ) {
			if ( ! is_string( $property ) || ! preg_match( '/^[a-z][a-z0-9_.-]*(?::[a-z0-9_.-]+)+$/D', $property ) ) {
				continue;
			}
			$values = is_array( $value ) ? $value : array( $value );
			foreach ( $values as $index => $content ) {
				if ( ! is_scalar( $content ) ) {
					continue;
				}
				$content = is_bool( $content ) ? ( $content ? 'true' : 'false' ) : (string) $content;
				if ( '' === trim( $content ) && ! ( 'og:determiner' === $property && '' === $content ) ) {
					continue;
				}
				if ( self::is_url_property( $property ) ) {
					$content = esc_url_raw( $content, array( 'http', 'https' ) );
					if ( '' === $content ) {
						continue;
					}
				}
				$prepared[ $property ][ $index ] = $content;
			}
		}
		// Media :url properties are aliases of their roots and must group the same details.
		foreach ( array( 'og:image', 'og:video', 'og:audio' ) as $root ) {
			if ( ! isset( $prepared[ $root ] ) && isset( $prepared[ $root . ':url' ] ) ) {
				$prepared[ $root ] = $prepared[ $root . ':url' ];
				unset( $prepared[ $root . ':url' ] );
			}
		}

		foreach ( array(
			array( 'product:price:amount', 'og:price:amount', 'product:sale_price:amount' ),
			array( 'product:price:currency', 'og:price:currency', 'product:sale_price:currency' ),
		) as $properties ) {
			$changed_values = array();
			foreach ( $properties as $property ) {
				if ( isset( $prepared[ $property ], $default_tags[ $property ] ) && reset( $prepared[ $property ] ) !== $default_tags[ $property ] ) {
					$changed_values[] = reset( $prepared[ $property ] );
				}
			}
			if ( 1 !== count( array_unique( $changed_values ) ) ) {
				continue;
			}
			foreach ( $properties as $property ) {
				if ( isset( $prepared[ $property ], $default_tags[ $property ] ) && reset( $prepared[ $property ] ) === $default_tags[ $property ] ) {
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
		$existing_roots    = array();
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
					$processor->set_attribute( 'content', html_entity_decode( (string) reset( $this->tags['og:type'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				} elseif ( ! is_string( $content ) || ( '' === trim( $content ) && 'og:determiner' !== $property ) ) {
					continue;
				}
				if ( is_string( $content ) && $this->has_conflicting_price( $property, $content ) ) {
					$conflicting_price = true;
				}
				$root = self::get_structured_root( $property );
				if ( null !== $root && ( $root === $property || $root . ':url' === $property ) ) {
					$existing_roots[ $root ] = true;
				}
				if ( null === $root || ! isset( $tags[ $root ] ) || $root === $property ) {
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

		// Keep each provider's objects and their structured details together.
		foreach ( array_keys( $tags ) as $property ) {
			$root = self::get_structured_root( $property );
			if ( null !== $root && isset( $existing_roots[ $root ] ) ) {
				unset( $tags[ $property ] );
			}
		}

		if ( ! $head_complete ) {
			return $html;
		}

		if ( isset( $tags['twitter:card'] ) && reset( $tags['twitter:card'] ) === $this->default_card ) {
			$tags['twitter:card'] = array( isset( $existing_roots['og:image'] ) || ! empty( $tags['og:image'] ) ? 'summary_large_image' : 'summary' );
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
			$values = $this->tags[ $property ] ?? $this->tags['product:price:currency'] ?? $this->tags['og:price:currency'] ?? $this->tags['product:sale_price:currency'] ?? array();
			return $values && strtoupper( trim( $content ) ) !== strtoupper( trim( (string) reset( $values ) ) );
		}
		$values = $this->tags[ $property ] ?? array();
		if ( ! $values && 'product:sale_price:amount' === $property ) {
			$values = $this->tags['product:price:amount'] ?? $this->tags['og:price:amount'] ?? array();
		}
		if ( ! $values ) {
			return false;
		}
		$fallback = reset( $values );
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

			/**
			 * Remove the current tag.
			 */
			public function remove_tag(): void {
				$this->set_bookmark( 'here' );
				$this->lexical_updates[] = new WP_HTML_Text_Replacement( $this->bookmarks['here']->start, $this->bookmarks['here']->length, '' );
			}
		};

		$replace_roots = array();
		foreach ( $tags as $property => $values ) {
			if ( self::get_structured_root( $property ) === $property ) {
				$replace_roots[ $property ] = true;
			}
		}
		$template_depth = 0;
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
			$content    = $inserter->get_attribute( 'content' );
			$properties = self::get_meta_properties( $inserter );
			// Rebuild empty roots and orphaned details together to preserve grouping.
			if ( $replace_roots ) {
				$remaining = array_filter(
					$properties,
					static function ( string $property ) use ( $replace_roots ): bool {
						$root = self::get_structured_root( $property );
						return null === $root || ! isset( $replace_roots[ $root ] );
					}
				);
				if ( $properties !== $remaining ) {
					if ( ! $remaining ) {
						$inserter->remove_tag();
						continue;
					}
					foreach ( array( 'property', 'name' ) as $attribute ) {
						$value = $inserter->get_attribute( $attribute );
						if ( is_string( $value ) ) {
							$names = preg_split( '/\s+/', strtolower( trim( $value ) ) );
							$names = false !== $names ? array_intersect( $names, $remaining ) : array();
							if ( $names ) {
								$inserter->set_attribute( $attribute, implode( ' ', $names ) );
							} else {
								$inserter->remove_attribute( $attribute );
							}
						}
					}
					$properties = $remaining;
				}
			}
			foreach ( $properties as $property ) {
				if ( isset( $tags[ $property ] ) && ( ! is_string( $content ) || '' === trim( $content ) ) ) {
					// Decode existing entities because set_attribute() always encodes ampersands.
					$inserter->set_attribute( 'content', html_entity_decode( array_shift( $tags[ $property ] ) ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					if ( ! $tags[ $property ] ) {
						unset( $tags[ $property ] );
					}
				}
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
		$details = array();
		foreach ( $tags as $property => $values ) {
			$root = self::get_structured_root( $property );
			if ( null !== $root && $root !== $property && isset( $tags[ $root ] ) ) {
				$details[ $root ][ $property ] = $values;
				unset( $tags[ $property ] );
			}
		}

		$markup = '';
		foreach ( $tags as $property => $values ) {
			foreach ( $values as $index => $value ) {
				$markup .= self::render_tag( $property, $value );
				foreach ( $details[ $property ] ?? array() as $detail => $contents ) {
					if ( isset( $contents[ $index ] ) ) {
						$markup .= self::render_tag( $detail, $contents[ $index ] );
					}
				}
			}
		}

		return $markup;
	}

	/**
	 * Escape and render a single metadata tag.
	 *
	 * @param string $property Metadata property.
	 * @param string $value Metadata value.
	 * @return string
	 */
	private static function render_tag( string $property, string $value ): string {
		$content = self::is_url_property( $property ) ? esc_url( $value, array( 'http', 'https' ) ) : esc_attr( $value );
		if ( '' === $content && 'og:determiner' !== $property ) {
			return '';
		}
		$attribute = 0 === strpos( $property, 'twitter:' ) ? 'name' : 'property';
		return '<meta ' . esc_attr( $attribute ) . '="' . esc_attr( $property ) . '" content="' . $content . '" />' . "\n";
	}

	/**
	 * Build metadata from the product and its images.
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
		$tags                += $this->get_image_tags( $product );
		$tags                += $this->get_media_tags( $product );
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
	 * Build metadata for up to six featured and gallery images.
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<string, string[]>
	 */
	private function get_image_tags( WC_Product $product ): array {
		$tags      = array();
		$image_ids = array_slice( array_unique( array_filter( array_map( 'intval', array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) ) ), 0, 6 );
		if ( ! empty( $image_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $image_ids );
		}
		foreach ( $image_ids as $image_id ) {
			$image = wp_get_attachment_image_src( $image_id, 'full' );
			if ( ! $image || in_array( $image[0], $tags['og:image'] ?? array(), true ) ) {
				continue;
			}
			$index                          = count( $tags['og:image'] ?? array() );
			$tags['og:image'][ $index ]     = $image[0];
			$tags['og:image:url'][ $index ] = $image[0];
			if ( 'https' === wp_parse_url( $image[0], PHP_URL_SCHEME ) ) {
				$tags['og:image:secure_url'][ $index ] = $image[0];
			}
			if ( $image[1] > 0 && $image[2] > 0 ) {
				$tags['og:image:width'][ $index ]  = (string) $image[1];
				$tags['og:image:height'][ $index ] = (string) $image[2];
			}
			$alt                             = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$tags['og:image:alt'][ $index ]  = is_string( $alt ) && '' !== trim( $alt ) ? $alt : $product->get_name();
			$tags['og:image:type'][ $index ] = (string) get_post_mime_type( $image_id );
		}

		return $tags;
	}

	/**
	 * Get the root property that groups structured details.
	 *
	 * @param string $property Metadata property.
	 * @return string|null
	 */
	private static function get_structured_root( string $property ): ?string {
		$parts = explode( ':', $property, 3 );
		if ( count( $parts ) < 2 ) {
			return null;
		}
		$root = $parts[0] . ':' . $parts[1];

		// Alternate locales are independent values, rather than details of the primary locale.
		return 'og:locale' === $root ? null : $root;
	}

	/**
	 * Check whether a metadata property contains a URL.
	 *
	 * @param string $property Metadata property.
	 * @return bool
	 */
	private static function is_url_property( string $property ): bool {
		return in_array(
			$property,
			array(
				'og:url',
				'og:see_also',
				'article:author',
				'book:author',
				'music:album',
				'music:song',
				'music:musician',
				'music:creator',
				'video:actor',
				'video:director',
				'video:writer',
				'video:series',
				'payment:success_url',
			),
			true
		) || (bool) preg_match( '/^og:(image|video|audio)(:(url|secure_url))?$/', $property );
	}

	/**
	 * Build metadata for the first video and audio used by a product.
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<string, string>
	 */
	private function get_media_tags( WC_Product $product ): array {
		$tags = array();
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
			$tags = self::get_media_url_tags( 'video', is_string( $url ) ? $url : '', $metadata );
			if ( $tags ) {
				break;
			}
		}

		$content = $product->get_description() . "\n" . $product->get_short_description();
		// Parse core media shortcodes without running shortcode callbacks or fetching embeds.
		$content        = preg_replace_callback(
			'/' . get_shortcode_regex( array( 'video', 'audio' ) ) . '/s',
			static function ( array $shortcode ): string {
				if ( '[' === $shortcode[1] && ']' === $shortcode[6] ) {
					return '';
				}
				$attributes = shortcode_parse_atts( $shortcode[3] );
				$attributes = is_array( $attributes ) ? $attributes : array();
				$extensions = 'video' === $shortcode[2] ? wp_get_video_extensions() : wp_get_audio_extensions();
				foreach ( array_merge( array( 'src' ), $extensions ) as $key ) {
					if ( ! empty( $attributes[ $key ] ) && is_string( $attributes[ $key ] ) ) {
						$media_attributes = array( 'src' => $attributes[ $key ] );
						foreach ( array( 'width', 'height' ) as $dimension ) {
							if ( isset( $attributes[ $dimension ] ) && is_string( $attributes[ $dimension ] ) ) {
								$media_attributes[ $dimension ] = $attributes[ $dimension ];
							}
						}
						return '<' . $shortcode[2] . ' ' . wc_implode_html_attributes( $media_attributes ) . '></' . $shortcode[2] . '>';
					}
				}
				return '';
			},
			$content
		);
		$processor      = new WP_HTML_Tag_Processor( $content ?? '' );
		$media          = null;
		$metadata       = array();
		$template_depth = 0;
		while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag = $processor->get_tag();
			if ( 'TEMPLATE' === $tag ) {
				$template_depth = max( 0, $template_depth + ( $processor->is_tag_closer() ? -1 : 1 ) );
			}
			if ( $template_depth > 0 ) {
				continue;
			}
			if ( in_array( $tag, array( 'VIDEO', 'AUDIO' ), true ) ) {
				$media    = $processor->is_tag_closer() ? null : strtolower( $tag );
				$metadata = array(
					'width'  => $processor->get_attribute( 'width' ),
					'height' => $processor->get_attribute( 'height' ),
				);
			}
			if ( null === $media || isset( $tags[ 'og:' . $media ] ) || ! in_array( $tag, array( 'VIDEO', 'AUDIO', 'SOURCE' ), true ) || $processor->is_tag_closer() ) {
				continue;
			}
			$url = $processor->get_attribute( 'src' );
			if ( is_string( $url ) ) {
				$metadata['type'] = $processor->get_attribute( 'type' );
				$tags            += self::get_media_url_tags( $media, $url, $metadata );
			}
		}
		return $tags;
	}

	/**
	 * Build media properties from a URL and available MIME type and dimensions.
	 *
	 * @param string               $media Media type.
	 * @param string               $url Media URL.
	 * @param array<string, mixed> $metadata Media details.
	 * @return array<string, string>
	 */
	private static function get_media_url_tags( string $media, string $url, array $metadata ): array {
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		if ( ! in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
			return array();
		}
		$root = 'og:' . $media;
		$tags = array(
			$root          => $url,
			$root . ':url' => $url,
		);
		if ( 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			$tags[ $root . ':secure_url' ] = $url;
		}
		$type = $metadata['type'] ?? wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ) )['type'];
		if ( is_string( $type ) && 0 === strpos( $type, $media . '/' ) ) {
			$tags[ $root . ':type' ] = $type;
		}
		if ( 'video' === $media ) {
			foreach ( array( 'width', 'height' ) as $dimension ) {
				$value = $metadata[ $dimension ] ?? null;
				if ( is_scalar( $value ) && ctype_digit( (string) $value ) && (int) $value > 0 ) {
					$tags[ $root . ':' . $dimension ] = (string) $value;
				}
			}
		}
		return $tags;
	}
}
