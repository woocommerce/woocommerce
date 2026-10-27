<?php
/**
 * Product Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WP_HTML_Tag_Processor;
use WP_HTML_Text_Replacement;

defined( 'ABSPATH' ) || exit;

/**
 * Fills missing Open Graph metadata in a rendered document.
 *
 * @internal
 */
class OpenGraphMarkup {

	/**
	 * Add metadata only after inspecting the rendered head for existing tags.
	 *
	 * @since 11.4.0
	 *
	 * @param string                                                             $html         Template output.
	 * @param array<string, string>                                              $candidates   Validated metadata.
	 * @param string|null                                                        $default_card Twitter card before filtering.
	 * @param array<array{properties: string[], matches: callable(array): bool}> $groups Related tags and their coexistence rules.
	 * @return string
	 */
	public function merge( string $html, array $candidates, ?string $default_card, array $groups = array() ): string {
		if ( ! $candidates ) {
			return $html;
		}

		$processor      = new WP_HTML_Tag_Processor( $html );
		$tags           = $candidates;
		$has_image      = false;
		$existing       = array();
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
				if ( 'og:type' === $property && isset( $candidates['og:type'] ) ) {
					$processor->set_attribute( 'content', html_entity_decode( $candidates['og:type'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				} elseif ( ! is_string( $content ) || '' === trim( $content ) ) {
					continue;
				}
				$existing[ $property ][] = $content;
				if ( in_array( $property, array( 'og:image', 'og:image:url' ), true ) ) {
					$has_image = true;
				}
				if ( ! isset( $tags['og:image'] ) || 0 !== strpos( $property, 'og:image:' ) ) {
					unset( $tags[ $property ] );
				}
			}
		}

		foreach ( $groups as $group ) {
			if ( ! $group['matches']( $existing ) ) {
				$tags = array_diff_key( $tags, array_flip( $group['properties'] ) );
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

		if ( isset( $tags['twitter:card'] ) && $tags['twitter:card'] === $default_card ) {
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
}
