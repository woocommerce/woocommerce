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
	 * Remove from an element the class names whose styles the renderer applies to the wrapping table cell.
	 *
	 * Background and border classes are resolved by the CSS inliner wherever they appear. The wrapping
	 * cell keeps the block's original class list *and* receives the same styles inline, so leaving these
	 * classes on the inner element paints them a second time. For an opaque color that is invisible, but
	 * a translucent palette color composites over itself and renders as a visibly darker band inside the
	 * cell's padding.
	 *
	 * @param \WP_HTML_Tag_Processor $html Tag processor positioned on the element to clean.
	 */
	public static function remove_wrapper_handled_classes( \WP_HTML_Tag_Processor $html ): void {
		$class_attribute = $html->get_attribute( 'class' );
		if ( ! is_string( $class_attribute ) ) {
			return;
		}

		$class_names = preg_split( '/\s+/', trim( $class_attribute ) );
		if ( ! is_array( $class_names ) ) {
			return;
		}

		// Whole class names are compared and removed, so a class that merely contains one of these
		// names as a substring is left intact instead of being reduced to a fragment.
		foreach ( $class_names as $class_name ) {
			if ( '' !== $class_name && self::is_wrapper_handled_class( $class_name ) ) {
				$html->remove_class( $class_name );
			}
		}
	}

	/**
	 * Whether a single class name applies a background or border that the wrapping table cell already renders.
	 *
	 * @param string $class_name Class name to test.
	 * @return bool True when the class should not stay on the inner element.
	 */
	private static function is_wrapper_handled_class( string $class_name ): bool {
		// `has-background` is added for any background. Preset palette backgrounds add
		// `has-<slug>-background-color` on top of it, which is why matching the bare name is not enough.
		if ( 'has-background' === $class_name ) {
			return true;
		}

		if ( str_starts_with( $class_name, 'has-' ) && str_ends_with( $class_name, '-background-color' ) ) {
			return true;
		}

		// Border classes, e.g. `has-border-color`, `has-<slug>-border-color`.
		return false !== strpos( $class_name, '-border-' );
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
	 * Sanitize dimension value to ensure it's a valid CSS dimension.
	 *
	 * Supports numeric values (converted to px) and standard CSS units.
	 *
	 * @param mixed $value The dimension value to sanitize.
	 * @return string Sanitized dimension value or empty string if invalid.
	 */
	public static function sanitize_dimension_value( $value ): string {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		$value = (string) $value;

		// If it's just a number, assume pixels.
		if ( is_numeric( $value ) ) {
			$value = $value . 'px';
		}

		// Use existing CSS value sanitization for security.
		$sanitized_value = self::sanitize_css_value( $value );

		// Additional validation for dimension-specific units.
		if ( ! empty( $sanitized_value ) && preg_match( '/^(\d+(?:\.\d+)?)(px|em|rem|%|vh|vw|ex|ch|in|cm|mm|pt|pc)$/', $sanitized_value ) ) {
			return $sanitized_value;
		}

		return '';
	}

	/**
	 * Sanitize color value to ensure it's a valid color format.
	 *
	 * Supports hex colors, rgb/rgba, hsl/hsla, named colors, and CSS variables.
	 *
	 * @param string $color The color value to sanitize.
	 * @return string Sanitized color value or safe default if invalid.
	 */
	public static function sanitize_color( string $color ): string {
		// Remove any whitespace.
		$color = trim( $color );

		// Check if it's a valid hex color (#fff, #ffffff, #ffffffff).
		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color ) ) {
			return strtolower( $color );
		}

		// Check for rgb/rgba colors.
		if ( preg_match( '/^rgba?\(\s*(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\s*,\s*(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\s*,\s*(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\s*(?:,\s*(?:1(?:\.0+)?|0(?:\.\d+)?|\.\d+)\s*)?\)$/', $color ) ) {
			return $color;
		}

		// Check for hsl/hsla colors.
		if ( preg_match( '/^hsla?\(\s*(360|3[0-5]\d|[12]\d{2}|\d{1,2})\s*,\s*(100|[1-9]?\d)%\s*,\s*(100|[1-9]?\d)%\s*(?:,\s*(?:1(?:\.0+)?|0(?:\.\d+)?|\.\d+)\s*)?\)$/', $color ) ) {
			return $color;
		}

		// Check for named colors and other valid CSS color values.
		// We use a permissive approach: accept any string that doesn't contain dangerous characters
		// and let the CSS engine handle the actual validation.
		if ( preg_match( '/^[a-zA-Z][a-zA-Z0-9-]*$/', $color ) && ! preg_match( '/^(expression|javascript|vbscript|data|import|behavior|binding|filter|progid)/i', $color ) ) {
			return strtolower( $color );
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

		// Block all event handler attributes (on*) - Critical security fix.
		if ( str_starts_with( $attr_name, 'on' ) ) {
			$html->remove_attribute( $attr_name );
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

			default:
				// Handle data-* attributes with strict validation.
				if ( str_starts_with( $attr_name, 'data-' ) ) {
					if ( ! preg_match( '/^[a-zA-Z0-9\-_]+$/', (string) $attr_value ) ) {
						$html->remove_attribute( $attr_name );
					}
					break;
				}
				// Default deny policy: Remove any attribute not explicitly allowed.
				$html->remove_attribute( $attr_name );
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
	 * Validate HTML container attributes for security before content extraction.
	 * This method checks if a container element (like figcaption, span) has safe attributes.
	 *
	 * @param string $container_html Full container HTML (e.g., <figcaption class="...">content</figcaption>).
	 * @return bool True if container attributes are safe, false otherwise.
	 */
	public static function validate_container_attributes( string $container_html ): bool {
		// Use WP_HTML_Tag_Processor to validate container attributes.
		$html = new \WP_HTML_Tag_Processor( $container_html );
		if ( ! $html->next_tag() ) {
			return false;
		}

		// Get all attributes and validate each one using our existing validation logic.
		$attributes = $html->get_attribute_names_with_prefix( '' );
		if ( is_array( $attributes ) ) {
			foreach ( $attributes as $attr_name ) {
				// Use the same validation logic as validate_caption_attribute for consistency.
				$attr_value = $html->get_attribute( $attr_name );
				if ( null === $attr_value ) {
					continue;
				}

				// Block event handlers immediately.
				if ( str_starts_with( $attr_name, 'on' ) ) {
					return false;
				}

				// Apply the same validation rules as caption attributes.
				// Create a temporary processor to test validation.
				$escaped_value = htmlspecialchars( (string) $attr_value, ENT_QUOTES, 'UTF-8' );
				$temp_html     = new \WP_HTML_Tag_Processor( '<span ' . $attr_name . '="' . $escaped_value . '">test</span>' );
				if ( $temp_html->next_tag() ) {
					$original_value = $temp_html->get_attribute( $attr_name );
					self::validate_caption_attribute( $temp_html, $attr_name );
					$validated_value = $temp_html->get_attribute( $attr_name );

					// If attribute was removed during validation, container is unsafe.
					if ( null !== $original_value && null === $validated_value ) {
						return false;
					}
				}
			}
		}

		return true;
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

		/*
		 * Remove executable elements together with their content. The allow-list
		 * pass below keeps the text of the tags it strips, which would turn a
		 * script body into visible caption text.
		 *
		 * One pattern per element rather than a single alternation that backreferences
		 * the opening name: with the closing tag spelled out as a literal, PCRE can
		 * reject a caption that never closes the element outright, instead of scanning
		 * to the end of the caption once for every opening tag it finds.
		 */
		foreach ( array( 'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button' ) as $tag ) {
			$result = preg_replace( '/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $caption_html );
			// A caption long enough to exhaust PCRE's limits keeps whatever the pass
			// managed to remove. wp_kses() below still drops the element itself, so
			// only the text that was inside it is left behind.
			if ( null !== $result ) {
				$caption_html = $result;
			}
		}

		/*
		 * Reduce the markup to the allowed elements and attributes.
		 *
		 * wp_kses() rebuilds each tag it keeps from the allow-list rather than
		 * passing the authored text through, which is what makes malformed markup
		 * safe here. A ">" inside an attribute value still ends the tag span early,
		 * but the allowed tag that falls out of that split is re-emitted with only
		 * its allowed attributes. A tag left without a closing ">" cannot survive as
		 * markup either, though which way it goes depends on what is attached to the
		 * pre_kses hook: core escapes it to text there, and without that callback
		 * kses drops the tag or supplies the missing ">" itself.
		 */
		$caption_html = wp_kses( $caption_html, self::get_allowed_caption_html(), array( 'http', 'https', 'mailto', 'tel' ) );

		/*
		 * Narrow the attribute values that survived: protocol allow-list on href,
		 * safe CSS properties on style, rel and target normalization. Every
		 * remaining tag is allowed, so every attribute on it is validated.
		 */
		$html = new \WP_HTML_Tag_Processor( $caption_html );
		while ( $html->next_tag() ) {
			$attributes = $html->get_attribute_names_with_prefix( '' );
			if ( is_array( $attributes ) ) {
				foreach ( $attributes as $attr_name ) {
					self::validate_caption_attribute( $html, $attr_name );
				}
			}
		}

		return $html->get_updated_html();
	}

	/**
	 * Elements and attributes allowed in captions, in wp_kses() format.
	 *
	 * Attribute values are narrowed further by validate_caption_attribute().
	 *
	 * @return array<string, array<string, bool>> Allowed HTML for wp_kses().
	 */
	private static function get_allowed_caption_html(): array {
		$common_attributes = array(
			'class'  => true,
			'style'  => true,
			'data-*' => true,
		);

		return array(
			'a'      => array_merge(
				$common_attributes,
				array(
					'href'   => true,
					'target' => true,
					'rel'    => true,
				)
			),
			'br'     => $common_attributes,
			'em'     => $common_attributes,
			'kbd'    => $common_attributes,
			'mark'   => $common_attributes,
			's'      => $common_attributes,
			'span'   => $common_attributes,
			'strong' => $common_attributes,
			'sub'    => $common_attributes,
			'sup'    => $common_attributes,
		);
	}

	/**
	 * Sanitize image HTML while preserving necessary attributes for email rendering.
	 *
	 * @param string $image_html Raw image HTML.
	 * @return string Sanitized image HTML.
	 */
	public static function sanitize_image_html( string $image_html ): string {
		// If no HTML tags, return as-is.
		if ( false === strpos( $image_html, '<' ) ) {
			return $image_html;
		}

		// Extract img tag using regex for reliable processing.
		if ( ! preg_match( '/<img[^>]*>/i', $image_html, $matches ) ) {
			return $image_html;
		}

		$img_tag              = $matches[0];
		$sanitized_attributes = array();
		$has_src              = false;

		// Extract and sanitize individual attributes using WP_HTML_Tag_Processor for attribute processing.
		$html = new \WP_HTML_Tag_Processor( $img_tag );
		if ( $html->next_tag() ) {
			$attributes = $html->get_attribute_names_with_prefix( '' );
			if ( is_array( $attributes ) ) {
				foreach ( $attributes as $attr_name ) {
					$attr_value = $html->get_attribute( $attr_name );

					// Sanitize specific attributes.
					switch ( $attr_name ) {
						case 'src':
							// Sanitize image source URL.
							$sanitized_src = esc_url( (string) $attr_value );
							if ( ! empty( $sanitized_src ) ) {
								$sanitized_attributes[] = $attr_name . '="' . $sanitized_src . '"';
								$has_src                = true;
							}
							break;

						case 'alt':
						case 'width':
						case 'height':
							// Sanitize text attributes.
							$sanitized_attributes[] = $attr_name . '="' . esc_attr( (string) $attr_value ) . '"';
							break;

						case 'class':
							// Clean CSS classes.
							$cleaned_classes = self::clean_css_classes( (string) $attr_value );
							if ( ! empty( $cleaned_classes ) ) {
								$sanitized_attributes[] = $attr_name . '="' . esc_attr( $cleaned_classes ) . '"';
							}
							break;

						case 'style':
							// Sanitize inline styles - only allow safe properties for email rendering.
							$sanitized_styles = self::sanitize_image_styles( (string) $attr_value );
							if ( ! empty( $sanitized_styles ) ) {
								$sanitized_attributes[] = $attr_name . '="' . esc_attr( $sanitized_styles ) . '"';
							}
							break;
					}
				}
			}
		}

		// If no valid src attribute, return empty string.
		if ( ! $has_src ) {
			return '';
		}

		// Rebuild the img tag with sanitized attributes.
		if ( empty( $sanitized_attributes ) ) {
			return '';
		}

		return '<img ' . implode( ' ', $sanitized_attributes ) . '>';
	}

	/**
	 * Extract the first HTTP/HTTPS URL from a text string.
	 *
	 * @param string $text Text to search for URLs.
	 * @return string Extracted URL or empty string if not found.
	 */
	public static function extract_url_from_text( string $text ): string {
		if ( preg_match( '/(?<![a-zA-Z0-9.-])https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}[a-zA-Z0-9\/?=&%_.~+#-]*(?![a-zA-Z0-9._~+#-])/', $text, $matches ) ) {
			return $matches[0];
		}

		return '';
	}

	/**
	 * Sanitize inline styles for image elements - only allow safe properties for email rendering.
	 *
	 * @param string $style_value Raw style value.
	 * @return string Sanitized style value.
	 */
	private static function sanitize_image_styles( string $style_value ): string {
		$sanitized_styles = array();
		$style_parts      = explode( ';', $style_value );

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

			// Allow safe CSS properties for images in email rendering.
			$safe_properties = array( 'width', 'height', 'max-width', 'max-height', 'display', 'margin', 'padding', 'border', 'border-radius' );
			if ( in_array( $property, $safe_properties, true ) ) {
				$sanitized_value = self::sanitize_css_value( $value );
				if ( ! empty( $sanitized_value ) ) {
					$sanitized_styles[] = $property . ': ' . $sanitized_value;
				}
			}
		}

		return implode( '; ', $sanitized_styles );
	}
}
