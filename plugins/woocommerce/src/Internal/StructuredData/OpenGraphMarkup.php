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
	 * @param string                       $html         Template output.
	 * @param array<array<string, string>> $candidates   Validated metadata.
	 * @param string|null                  $default_card Twitter card before filtering.
	 * @return string
	 */
	public function merge( string $html, array $candidates, ?string $default_card ): string {
		if ( ! $candidates ) {
			return $html;
		}

		/**
		 * Metadata ownership rules.
		 *
		 * @var array<string, array{detect?: string, match: string, exists: bool}> $tag_groups
		 */
		$tag_groups = array(
			'image'        => array(
				'detect' => '/^og:image(?::url)?$/D',
				'match'  => '/^og:image(?::|$)/D',
				'exists' => false,
			),
			'prices'       => array(
				'match'  => '/^(product:(sale_)?price(:|_)|og:price:)/',
				'exists' => false,
			),
			'availability' => array(
				'match'  => '/^(product|og):availability$/D',
				'exists' => false,
			),
		);

		$processor = new WP_HTML_Tag_Processor( $html );
		$tags      = $candidates;
		$values    = array();
		foreach ( $candidates as $group ) {
			$values += $group;
		}
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
				if ( 'og:type' === $property && isset( $values['og:type'] ) ) {
					$processor->set_attribute( 'content', html_entity_decode( $values['og:type'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				} elseif ( ! is_string( $content ) || '' === trim( $content ) ) {
					continue;
				}
				foreach ( $tag_groups as $group_name => $group ) {
					if ( preg_match( $group['detect'] ?? $group['match'], $property ) ) {
						$tag_groups[ $group_name ]['exists'] = true;
					}
				}
				$existing[ $property ] = true;
			}
		}

		// Keep related metadata with the provider that supplied it.
		foreach ( $tags as $index => $candidate ) {
			if ( isset( $existing[ array_key_first( $candidate ) ] ) ) {
				unset( $tags[ $index ] );
				continue;
			}
			foreach ( $candidate as $property => $value ) {
				foreach ( $tag_groups as $group ) {
					if ( $group['exists'] && preg_match( $group['match'], $property ) ) {
						unset( $tags[ $index ] );
						continue 3;
					}
				}
			}
		}

		if ( ! $head_complete ) {
			return $html;
		}

		foreach ( $tags as $index => $group ) {
			if ( isset( $group['twitter:card'] ) && $group['twitter:card'] === $default_card ) {
				$has_image                      = $tag_groups['image']['exists'] || isset( $values['og:image'] ) || isset( $values['og:image:url'] );
				$tags[ $index ]['twitter:card'] = $has_image ? 'summary_large_image' : 'summary';
			}
		}

		return self::insert_tags( $processor->get_updated_html(), $tags );
	}

	/**
	 * Escape metadata and insert it at the end of the head.
	 *
	 * @param string                       $html Template output.
	 * @param array<array<string, string>> $tags Metadata to insert.
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
			 *
			 * @internal
			 */
			public function remove_tag(): void {
				$this->set_bookmark( 'here' );
				$this->lexical_updates[] = new WP_HTML_Text_Replacement( $this->bookmarks['here']->start, $this->bookmarks['here']->length, '' );
			}
		};

		$media_roots = array();
		foreach ( $tags as $group ) {
			$root = array_key_first( $group );
			if ( in_array( $root, array( 'og:image', 'og:video', 'og:audio' ), true ) ) {
				$media_roots[ $root ] = true;
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
			$properties = self::get_meta_properties( $inserter );
			if ( $media_roots ) {
				// Rebuild empty media roots and orphaned details together in group order.
				$remaining = array_filter(
					$properties,
					static function ( string $property ) use ( $media_roots ): bool {
						foreach ( $media_roots as $root => $unused ) {
							if ( $root === $property || 0 === strpos( $property, $root . ':' ) ) {
								return false;
							}
						}
						return true;
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
			$content = $inserter->get_attribute( 'content' );
			if ( is_string( $content ) && '' !== trim( $content ) ) {
				continue;
			}
			foreach ( $properties as $property ) {
				foreach ( $tags as $index => $group ) {
					if ( isset( $group[ $property ] ) ) {
						// Decode existing entities because set_attribute() always encodes ampersands.
						$inserter->set_attribute( 'content', html_entity_decode( $group[ $property ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
						unset( $tags[ $index ][ $property ] );
						if ( ! $tags[ $index ] ) {
							unset( $tags[ $index ] );
						}
						break;
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
	 * @param array<array<string, string>> $tags Metadata to render.
	 * @return string
	 */
	private static function render_tags( array $tags ): string {
		$markup = '';
		foreach ( $tags as $group ) {
			foreach ( $group as $property => $value ) {
				$is_url  = 'og:url' === $property || preg_match( '/^og:(image|video|audio)(:(url|secure_url))?$/D', $property );
				$content = $is_url ? esc_url( $value, array( 'http', 'https' ) ) : esc_attr( $value );
				if ( '' !== $content ) {
					$attribute = 0 === strpos( $property, 'twitter:' ) ? 'name' : 'property';
					$markup   .= '<meta ' . esc_attr( $attribute ) . '="' . esc_attr( $property ) . '" content="' . $content . '" />' . "\n";
				}
			}
		}

		return $markup;
	}
}
