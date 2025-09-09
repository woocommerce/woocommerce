<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * Helper class for HTML processing and manipulation.
 */
class Html_Processing_Helper {
	/**
	 * Clean CSS classes by removing background and border related classes.
	 *
	 * @param string $classes CSS classes to clean.
	 * @return string Cleaned CSS classes.
	 */
	public static function clean_css_classes( string $classes ): string {
		// Limit input length to prevent DoS attacks.
		if ( strlen( $classes ) > 1000 ) {
			$classes = substr( $classes, 0, 1000 );
		}

		// Remove generic background classes but keep specific color classes.
		$result = preg_replace( '/\bhas-background\b/', '', $classes );
		if ( null === $result ) {
			$classes = '';
		} else {
			$classes = $result;
		}

		// Remove border classes.
		$result = preg_replace( '/\bhas-[a-z-]*border[a-z-]*\b/', '', $classes );
		if ( null === $result ) {
			$classes = '';
		} else {
			$classes = $result;
		}

		$result = preg_replace( '/\b[a-z-]+-border-[a-z-]+\b/', '', $classes );
		if ( null === $result ) {
			$classes = '';
		} else {
			$classes = $result;
		}

		// Clean up multiple spaces.
		$result = preg_replace( '/\s+/', ' ', $classes );
		if ( null === $result ) {
			$classes = '';
		} else {
			$classes = $result;
		}

		return trim( $classes );
	}

	/**
	 * Sanitize CSS value to prevent injection attacks.
	 *
	 * @param string $value CSS value to sanitize.
	 * @return string Sanitized CSS value or empty string if invalid.
	 */
	public static function sanitize_css_value( string $value ): string {
		// Remove dangerous script injection characters (angle brackets) but preserve quotes for CSS strings.
		$result = preg_replace( '/[<>]/', '', $value );
		if ( null === $result ) {
			$value = '';
		} else {
			$value = $result;
		}

		// Remove dangerous CSS functions and expressions.
		$dangerous_patterns = array(
			'/expression\s*\(/i',
			'/url\s*\(\s*javascript\s*:/i',
			'/url\s*\(\s*data\s*:/i',
			'/url\s*\(\s*vbscript\s*:/i',
			'/import\s*\(/i',
			'/behavior\s*:/i',
			'/binding\s*:/i',
			'/filter\s*:/i',
			'/progid\s*:/i',
		);

		foreach ( $dangerous_patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return '';
			}
		}

		return trim( $value );
	}

	/**
	 * Sanitize color value to ensure it's a valid hex color or CSS variable.
	 *
	 * @param string $color The color value to sanitize.
	 * @return string Sanitized color value.
	 */
	public static function sanitize_color( string $color ): string {
		// Remove any whitespace and convert to lowercase.
		$color = strtolower( trim( $color ) );

		// Check if it's a valid hex color (3 or 6 characters).
		if ( preg_match( '/^#([a-f0-9]{3}){1,2}$/i', $color ) ) {
			return $color;
		}

		// Check if it's a CSS variable (var(--variable-name)).
		if ( preg_match( '/^var\(--[a-zA-Z0-9\-_]+\)$/', $color ) ) {
			return $color;
		}

		// If not a valid color format, return a safe default.
		return '#000000';
	}

	/**
	 * Normalize rel attribute by lowercasing, deduplicating tokens, and ensuring required tokens.
	 *
	 * @param string|null $rel_value Current rel attribute value.
	 * @param bool        $require_security_tokens Whether to require noopener and noreferrer tokens.
	 * @return string Normalized rel attribute value.
	 */
	private static function normalize_rel_attribute( ?string $rel_value, bool $require_security_tokens = false ): string {
		$allowed_tokens  = array( 'noopener', 'noreferrer', 'nofollow', 'external' );
		$required_tokens = $require_security_tokens ? array( 'noopener', 'noreferrer' ) : array();

		// If no rel value and no required tokens, return empty.
		if ( null === $rel_value && empty( $required_tokens ) ) {
			return '';
		}

		// Start with required tokens.
		$tokens = $required_tokens;

		// If rel value exists, parse and normalize it.
		if ( null !== $rel_value ) {
			$existing_tokens = preg_split( '/\s+/', trim( $rel_value ) );
			if ( false !== $existing_tokens ) {
				// Normalize existing tokens: lowercase, remove empty, filter allowed.
				$normalized_existing = array_filter(
					array_map( 'strtolower', $existing_tokens ),
					function ( $token ) use ( $allowed_tokens ) {
						return ! empty( $token ) && in_array( $token, $allowed_tokens, true );
					}
				);
				// Merge with required tokens, removing duplicates.
				$tokens = array_unique( array_merge( $tokens, $normalized_existing ) );
			}
		}

		// Return normalized rel attribute or empty string if no valid tokens.
		return empty( $tokens ) ? '' : implode( ' ', $tokens );
	}

	/**
	 * Validate and sanitize specific caption attributes for security.
	 *
	 * @param \WP_HTML_Tag_Processor $html HTML tag processor.
	 * @param string                 $attr_name Attribute name to validate.
	 */
	public static function validate_caption_attribute( \WP_HTML_Tag_Processor $html, string $attr_name ): void {
		$attr_value = $html->get_attribute( $attr_name );
		if ( null === $attr_value ) {
			return;
		}

		switch ( $attr_name ) {
			case 'href':
				// Only allow http, https, mailto, and tel protocols.
				if ( ! preg_match( '/^(https?:\/\/|mailto:|tel:)/i', (string) $attr_value ) ) {
					$html->remove_attribute( $attr_name );
					break;
				}

				// Sanitize and normalize the URL using WordPress's esc_url_raw.
				$sanitized_url = esc_url_raw( (string) $attr_value );
				if ( empty( $sanitized_url ) ) {
					// If esc_url_raw returns empty, the URL was invalid - remove the attribute.
					$html->remove_attribute( $attr_name );
				} else {
					// Set the attribute to the sanitized/normalized value.
					$html->set_attribute( $attr_name, $sanitized_url );
				}
				break;

			case 'target':
				// Allow only common safe targets.
				$allowed_targets = array( '_blank', '_self' );
				$target_value    = strtolower( (string) $attr_value );
				if ( ! in_array( $target_value, $allowed_targets, true ) ) {
					$html->remove_attribute( $attr_name );
				} elseif ( '_blank' === $target_value ) {
					// When target is "_blank", ensure rel attribute has noopener and noreferrer.
					$current_rel    = $html->get_attribute( 'rel' );
					$rel_value      = is_string( $current_rel ) ? $current_rel : null;
					$normalized_rel = self::normalize_rel_attribute( $rel_value, true );
					$html->set_attribute( 'rel', $normalized_rel );
				}
				break;

			case 'rel':
				// Normalize rel attribute: lowercase, deduplicate, preserve safe tokens.
				$rel_value      = is_string( $attr_value ) ? $attr_value : null;
				$normalized_rel = self::normalize_rel_attribute( $rel_value, false );
				if ( empty( $normalized_rel ) ) {
					$html->remove_attribute( $attr_name );
				} else {
					$html->set_attribute( $attr_name, $normalized_rel );
				}
				break;

			case 'style':
				// Only allow safe CSS properties for typography and basic styling.
				$safe_properties  = self::get_safe_css_properties();
				$sanitized_styles = array();
				$style_parts      = explode( ';', (string) $attr_value );

				foreach ( $style_parts as $style_part ) {
					$style_part = trim( $style_part );
					if ( empty( $style_part ) ) {
						continue;
					}

					$property_parts = explode( ':', $style_part, 2 );
					if ( count( $property_parts ) !== 2 ) {
						continue;
					}

					$property = trim( strtolower( $property_parts[0] ) );
					$value    = trim( $property_parts[1] );

					// Only allow safe properties.
					if ( in_array( $property, $safe_properties, true ) ) {
						// Use centralized CSS value sanitization.
						$sanitized_value = self::sanitize_css_value( $value );
						if ( ! empty( $sanitized_value ) ) {
							$sanitized_styles[] = $property . ': ' . $sanitized_value;
						}
					}
				}

				if ( empty( $sanitized_styles ) ) {
					$html->remove_attribute( $attr_name );
				} else {
					$html->set_attribute( $attr_name, implode( '; ', $sanitized_styles ) );
				}
				break;

			case 'class':
				// Only allow alphanumeric characters, hyphens, and underscores.
				if ( ! preg_match( '/^[a-zA-Z0-9\s\-_]+$/', (string) $attr_value ) ) {
					$html->remove_attribute( $attr_name );
				}
				break;

			case 'data-type':
			case 'data-id':
				// Only allow alphanumeric characters, hyphens, and underscores.
				if ( ! preg_match( '/^[a-zA-Z0-9\-_]+$/', (string) $attr_value ) ) {
					$html->remove_attribute( $attr_name );
				}
				break;
		}
	}

	/**
	 * Get list of safe CSS properties for typography and basic styling.
	 *
	 * @return array Array of safe CSS property names.
	 */
	public static function get_safe_css_properties(): array {
		return array(
			'color',
			'background-color',
			'font-family',
			'font-size',
			'font-weight',
			'font-style',
			'text-decoration',
			'text-align',
			'line-height',
			'letter-spacing',
			'text-transform',
		);
	}

	/**
	 * Get list of safe CSS properties for caption typography (excludes background properties).
	 *
	 * @return array Array of safe CSS property names for captions.
	 */
	public static function get_caption_css_properties(): array {
		return array(
			'font-family',
			'font-size',
			'font-weight',
			'font-style',
			'text-decoration',
			'line-height',
			'letter-spacing',
			'text-transform',
		);
	}

	/**
	 * Sanitize caption HTML to allow only specific tags and attributes.
	 *
	 * @param string $caption_html Raw caption HTML.
	 * @return string Sanitized caption HTML.
	 */
	public static function sanitize_caption_html( string $caption_html ): string {
		// If no HTML tags, return as-is.
		if ( false === strpos( $caption_html, '<' ) ) {
			return $caption_html;
		}

		// Remove dangerous content: script, style, and other executable elements.
		$result = preg_replace( '/<(script|style|iframe|object|embed|form|input|button)\b[^>]*>.*?<\/\1>/is', '', $caption_html );
		if ( null === $result ) {
			$caption_html = '';
		} else {
			$caption_html = $result;
		}

		// Use a more conservative approach - only validate attributes, don't modify tags.
		$allowed_tags = array( 'strong', 'em', 'a', 'mark', 'kbd', 's', 'sub', 'sup', 'span', 'br' );

		$html = new \WP_HTML_Tag_Processor( $caption_html );

		// First pass: Process attributes for allowed tags only.
		while ( $html->next_tag() ) {
			$tag_name = $html->get_tag();

			// Skip processing for disallowed tags.
			if ( ! in_array( $tag_name, $allowed_tags, true ) ) {
				continue;
			}

			// Only process attributes for allowed tags.
			$attributes = $html->get_attribute_names_with_prefix( '' );
			if ( is_array( $attributes ) ) {
				foreach ( $attributes as $attr_name ) {
					// Validate and sanitize each attribute individually.
					self::validate_caption_attribute( $html, $attr_name );
				}
			}
		}

		// Second pass: Remove disallowed tags using a simple regex approach.
		$final_html = $html->get_updated_html();

		// Create a regex pattern to match disallowed tags.
		$allowed_tags_pattern = implode( '|', array_map( 'preg_quote', $allowed_tags ) );

		// Remove disallowed opening and closing tags, keeping only their content.
		$result = preg_replace( '/<(?!(?:' . $allowed_tags_pattern . ')\b)[^>]*>(.*?)<\/(?!(?:' . $allowed_tags_pattern . ')\b)[^>]*>/s', '$1', $final_html );
		if ( null === $result ) {
			$final_html = '';
		} else {
			$final_html = $result;
		}

		// Remove disallowed self-closing tags.
		$result = preg_replace( '/<(?!(?:' . $allowed_tags_pattern . ')\b)[^>]*\/>/s', '', $final_html );
		if ( null === $result ) {
			$final_html = '';
		} else {
			$final_html = $result;
		}

		return $final_html;
	}
}
