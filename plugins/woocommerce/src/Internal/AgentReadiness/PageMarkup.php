<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Reads meta tags, links and text from a page the store served, for store checks
 * that look for them whoever printed them: WordPress, WooCommerce, an SEO plugin or the theme.
 *
 * @since 11.4.0
 */
class PageMarkup {

	/**
	 * The content of every meta tag whose attribute (name or property) matches, in page order.
	 *
	 * @param string   $html    Page HTML.
	 * @param string   $attr    Attribute to match, name or property.
	 * @param callable $matches Test for the attribute's value, given in lowercase.
	 * @return array<int, string>
	 */
	public static function meta( string $html, string $attr, callable $matches ): array {
		$contents = array();
		$tags     = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( array( 'tag_name' => 'meta' ) ) ) {
			$value = $tags->get_attribute( $attr );
			if ( is_string( $value ) && $matches( strtolower( trim( $value ) ) ) ) {
				$contents[] = trim( (string) $tags->get_attribute( 'content' ) );
			}
		}
		return $contents;
	}

	/**
	 * Whether a meta tag with that name or property has content.
	 *
	 * @param string $html Page HTML.
	 * @param string $attr name or property.
	 * @param string $name Value, such as description or og:title.
	 */
	public static function has_meta( string $html, string $attr, string $name ): bool {
		$contents = self::meta( $html, $attr, static fn( string $value ): bool => $name === $value );
		return array() !== array_filter( $contents, static fn( string $content ): bool => '' !== $content );
	}

	/**
	 * The href of the page's canonical link, or ''.
	 *
	 * @param string $html Page HTML.
	 */
	public static function canonical( string $html ): string {
		$tags = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( array( 'tag_name' => 'link' ) ) ) {
			$rel = strtolower( (string) $tags->get_attribute( 'rel' ) );
			if ( in_array( 'canonical', explode( ' ', (string) preg_replace( '/\s+/', ' ', $rel ) ), true ) ) {
				return trim( (string) $tags->get_attribute( 'href' ) );
			}
		}
		return '';
	}

	/**
	 * The page's text as a visitor without JavaScript reads it: no scripts, styles or tags, entities decoded.
	 *
	 * @param string $html Page HTML.
	 */
	public static function text( string $html ): string {
		$html = (string) preg_replace( '/<!--.*?-->/s', '', $html );
		return html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
