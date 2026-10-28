<?php
/**
 * Product Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Enums\ProductStatus;
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
	 * @var array<string, string>
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
	 * @param array<string, string>                           $tags   Metadata candidates.
	 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
	 * @return array<string, string>
	 */
	private function prepare_tags( array $tags, $context ): array {
		$this->default_card = $tags['twitter:card'] ?? null;

		/**
		 * Filters Open Graph metadata before output. Currently applied only to product pages.
		 * Other page types may be supported later; callbacks must check the object type.
		 * Removing og:type from $tags also preserves an existing provider's type.
		 *
		 * @since 11.4.0
		 *
		 * @param array<string, string> $tags Metadata keyed by og:, twitter:, or product: property name. Remove a key to omit a tag.
		 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
		 */
		$tags = apply_filters( 'woocommerce_open_graph_tags', $tags, $context );
		if ( ! is_array( $tags ) ) {
			return array();
		}

		foreach ( $tags as $property => $value ) {
			if ( ! is_string( $property ) || ! is_string( $value ) || '' === trim( $value ) || ! preg_match( '/^(og|twitter|product):[a-z_:]+$/', $property ) ) {
				unset( $tags[ $property ] );
				continue;
			}
			if ( in_array( $property, array( 'og:url', 'og:image' ), true ) ) {
				$tags[ $property ] = esc_url_raw( $value );
				if ( '' === $tags[ $property ] ) {
					unset( $tags[ $property ] );
				}
			}
		}

		return $tags;
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

		$processor      = new WP_HTML_Tag_Processor( $html );
		$tags           = $this->tags;
		$has_image      = false;
		$head_complete  = false;
		$template_depth = 0;

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
					$processor->set_attribute( 'content', html_entity_decode( $this->tags['og:type'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				} elseif ( ! is_string( $content ) || '' === trim( $content ) ) {
					continue;
				}
				if ( in_array( $property, array( 'og:image', 'og:image:url' ), true ) ) {
					$has_image = true;
				}
				if ( ! isset( $tags['og:image'] ) || 0 !== strpos( $property, 'og:image:' ) ) {
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

		if ( isset( $tags['twitter:card'] ) && $tags['twitter:card'] === $this->default_card ) {
			$tags['twitter:card'] = $has_image || ! empty( $tags['og:image'] ) ? 'summary_large_image' : 'summary';
		}

		return self::insert_tags( $processor->get_updated_html(), $tags );
	}

	/**
	 * Escape metadata and insert it at the end of the head.
	 *
	 * @param string                $html Template output.
	 * @param array<string, string> $tags Metadata to insert.
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
		$image_details['og:image:url'] = $image_details['og:image:url'] ?? $tags['og:image'] ?? '';
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
					$inserter->set_attribute( 'content', html_entity_decode( $image_details[ $property ] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					unset( $tags[ $property ] );
				} elseif ( isset( $tags[ $property ] ) && ( ! is_string( $content ) || '' === trim( $content ) ) ) {
					// Decode existing entities because set_attribute() always encodes ampersands.
					$inserter->set_attribute( 'content', html_entity_decode( $tags[ $property ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					unset( $tags[ $property ] );
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
	 * @param array<string, string> $tags Metadata to render.
	 * @return string
	 */
	private static function render_tags( array $tags ): string {
		$markup = '';
		foreach ( $tags as $property => $value ) {
			$content = in_array( $property, array( 'og:url', 'og:image' ), true ) ? esc_url( $value ) : esc_attr( $value );
			if ( '' !== $content ) {
				$attribute = 0 === strpos( $property, 'twitter:' ) ? 'name' : 'property';
				$markup   .= '<meta ' . esc_attr( $attribute ) . '="' . esc_attr( $property ) . '" content="' . $content . '" />' . "\n";
			}
		}

		return $markup;
	}

	/**
	 * Build metadata from the product and its featured image.
	 *
	 * @param WC_Product $product Product being shared.
	 * @return array<string, string>
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
