<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Utils;

use WP_HTML_Tag_Processor;

/**
 * Utility methods for filtering block icon SVGs.
 *
 * @internal
 */
final class BlockIconUtils {
	/**
	 * Positive grammar for supported flat SVG paint values.
	 */
	private const SAFE_PAINT_PATTERN = '
		/\A(?:
			[A-Z][A-Z-]*
			|
			\#(?:[0-9A-F]{3}|[0-9A-F]{4}|[0-9A-F]{6}|[0-9A-F]{8})
			|
			rgba?\(
				[\x20\t\r\n\f]*
				(?:
					[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?[\x20\t\r\n\f]*,[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?[\x20\t\r\n\f]*,[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?(?:[\x20\t\r\n\f]*,[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?)?
					|
					[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?[\x20\t\r\n\f]+[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?[\x20\t\r\n\f]+[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?(?:[\x20\t\r\n\f]*\/[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?)?
				)
				[\x20\t\r\n\f]*
			\)
			|
			hsla?\(
				[\x20\t\r\n\f]*
				(?:
					[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:deg|grad|rad|turn)?[\x20\t\r\n\f]*,[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)%[\x20\t\r\n\f]*,[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)% (?:[\x20\t\r\n\f]*,[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?)?
					|
					[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:deg|grad|rad|turn)?[\x20\t\r\n\f]+[+-]?(?:\d+(?:\.\d*)?|\.\d+)%[\x20\t\r\n\f]+[+-]?(?:\d+(?:\.\d*)?|\.\d+)%(?:[\x20\t\r\n\f]*\/[\x20\t\r\n\f]*[+-]?(?:\d+(?:\.\d*)?|\.\d+)%?)?
				)
				[\x20\t\r\n\f]*
			\)
		)\z/ix';

	/**
	 * Gets a filtered cart icon while preserving the legacy bundled SVG.
	 *
	 * @param mixed  $requested_icon   Requested cart icon.
	 * @param string $icon_color      Icon color.
	 * @param string $block_name      Block name.
	 * @param array  $block_attributes Caller-local block attributes.
	 * @return string Filtered SVG markup.
	 *
	 * @since 11.2.0
	 */
	public static function get_cart_icon( $requested_icon, $icon_color, $block_name, $block_attributes ) {
		$icon_name   = in_array( $requested_icon, array( 'bag', 'bag-alt' ), true ) ? $requested_icon : 'cart';
		$default_svg = MiniCartUtils::get_svg_icon( $requested_icon, $icon_color );

		return self::filter_block_icon( $default_svg, $icon_name, $block_name, $block_attributes );
	}

	/**
	 * Filters and validates a block icon SVG.
	 *
	 * @param string $default_svg      Default SVG markup.
	 * @param string $icon_name       Canonical icon name.
	 * @param string $block_name      Block name.
	 * @param array  $block_attributes Caller-local block attributes.
	 * @return string Filtered SVG markup.
	 *
	 * @since 11.2.0
	 */
	public static function filter_block_icon( $default_svg, $icon_name, $block_name, $block_attributes ) {
		/**
		 * Filters eligible decorative block icon SVG markup during frontend/server rendering.
		 *
		 * Supported callers are the Mini-Cart, Cart Link, and Customer Account primary icons. Block attributes contain caller-local render-time context and are not a normalized cross-block schema.
		 * Mini-Cart and Cart Link report `cart`, `bag`, or `bag-alt`; Customer Account reports `default`, `line`, or `alt`. Match both the block name and icon name; future callers may add names.
		 * This filter does not change editor previews, avatars, or functional controls such as the account dropdown caret. Text-only account blocks do not call it.
		 * The exact unchanged default is byte-preserved. Changed non-empty strings are sanitized and validated as one static SVG.
		 * Sanitization strips unsupported elements and attributes. The remaining markup must be one complete SVG containing only groups, paths, and basic shapes, without text or comments.
		 * Inline styles, scripts, events, animation, images, references, gradients, masks, and filters are unsupported. Fill and stroke accept flat keywords/named colors, hex, and numeric RGB(A)/HSL(A); CSS escapes, variables, and resource URLs are rejected.
		 * WooCommerce restores caller-owned root classes and decorative accessibility attributes, but the replacement owns its geometry and paint.
		 * Use `currentColor` to inherit the control color. To retain an explicit Mini-Cart icon color, read it from the supplied block attributes and emit it in the replacement.
		 * Empty or whitespace-only strings remove the icon. Non-string or invalid changed values fall back to the exact default.
		 *
		 * @param string $default_svg      Current SVG markup, initially the bundled default. Earlier callbacks may have replaced it; validation runs after all callbacks.
		 * @param string $icon_name       Canonical icon name.
		 * @param string $block_name      Block name.
		 * @param array  $block_attributes Caller-local render-time block attributes.
		 *
		 * @since 11.2.0
		 */
		$filtered_svg = apply_filters( 'woocommerce_blocks_icon_svg', $default_svg, $icon_name, $block_name, $block_attributes );

		if ( ! is_string( $filtered_svg ) || $default_svg === $filtered_svg ) {
			return $default_svg;
		}

		$filtered_svg = trim( $filtered_svg, " \t\r\n\f" );
		if ( '' === $filtered_svg ) {
			return '';
		}

		$sanitized_svg = trim( wp_kses( $filtered_svg, self::get_allowed_svg_html() ), " \t\r\n\f" );
		if ( ! self::is_valid_svg( $sanitized_svg ) ) {
			return $default_svg;
		}

		return self::enforce_root_invariants( $sanitized_svg, $default_svg );
	}

	/**
	 * Gets the exact static SVG vocabulary accepted at the filter boundary.
	 *
	 * @return array<string, array<string, bool>> Allowed SVG elements and attributes.
	 */
	private static function get_allowed_svg_html(): array {
		$presentation_attributes = array(
			'fill'              => true,
			'fill-opacity'      => true,
			'stroke'            => true,
			'stroke-opacity'    => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-miterlimit' => true,
			'opacity'           => true,
			'transform'         => true,
		);

		return array(
			'svg'      => array(
				'class'               => true,
				'xmlns'               => true,
				'width'               => true,
				'height'              => true,
				'viewbox'             => true,
				'preserveaspectratio' => true,
				'fill'                => true,
				'fill-opacity'        => true,
				'stroke'              => true,
				'stroke-opacity'      => true,
				'stroke-width'        => true,
				'stroke-linecap'      => true,
				'stroke-linejoin'     => true,
				'stroke-miterlimit'   => true,
				'opacity'             => true,
				'aria-hidden'         => true,
				'focusable'           => true,
				'role'                => true,
			),
			'g'        => array_merge( array( 'class' => true ), $presentation_attributes ),
			'path'     => array_merge(
				array(
					'class'     => true,
					'd'         => true,
					'fill-rule' => true,
					'clip-rule' => true,
				),
				$presentation_attributes
			),
			'circle'   => array_merge( array_fill_keys( array( 'class', 'cx', 'cy', 'r' ), true ), $presentation_attributes ),
			'ellipse'  => array_merge( array_fill_keys( array( 'class', 'cx', 'cy', 'rx', 'ry' ), true ), $presentation_attributes ),
			'rect'     => array_merge( array_fill_keys( array( 'class', 'x', 'y', 'width', 'height', 'rx', 'ry' ), true ), $presentation_attributes ),
			'line'     => array_merge( array_fill_keys( array( 'class', 'x1', 'y1', 'x2', 'y2' ), true ), $presentation_attributes ),
			'polyline' => array_merge(
				array(
					'class'  => true,
					'points' => true,
				),
				$presentation_attributes
			),
			'polygon'  => array_merge(
				array(
					'class'  => true,
					'points' => true,
				),
				$presentation_attributes
			),
		);
	}

	/**
	 * Validates that sanitized markup is one complete, static SVG tree.
	 *
	 * @param string $svg Sanitized SVG markup.
	 * @return bool Whether the markup is valid.
	 */
	private static function is_valid_svg( string $svg ): bool {
		$allowed_svg_html = self::get_allowed_svg_html();
		$processor        = new WP_HTML_Tag_Processor( $svg );
		$processor->change_parsing_namespace( 'svg' );
		$stack       = array();
		$root_count  = 0;
		$root_closed = false;

		while ( $processor->next_token() ) {
			$token_type = $processor->get_token_type();
			if ( '#text' === $token_type ) {
				if ( '' !== trim( $processor->get_modifiable_text(), " \t\r\n\f" ) ) {
					return false;
				}
				continue;
			}

			if ( '#tag' !== $token_type ) {
				return false;
			}

			$tag_name = strtolower( (string) $processor->get_token_name() );
			if ( ! array_key_exists( $tag_name, $allowed_svg_html ) ) {
				return false;
			}

			if ( $processor->is_tag_closer() ) {
				$open_tag = array_pop( $stack );
				if ( null === $open_tag || $open_tag !== $tag_name ) {
					return false;
				}

				if ( empty( $stack ) ) {
					$root_closed = true;
				}
				continue;
			}

			if ( empty( $stack ) ) {
				if ( 0 !== $root_count || 'svg' !== $tag_name || $root_closed ) {
					return false;
				}
				++$root_count;
			}

			foreach ( array( 'fill', 'stroke' ) as $paint_attribute ) {
				$paint = $processor->get_attribute( $paint_attribute );
				if ( null !== $paint && ( ! is_string( $paint ) || ! self::is_safe_paint( $paint ) ) ) {
					return false;
				}
			}

			if ( $processor->has_self_closing_flag() ) {
				if ( empty( $stack ) ) {
					$root_closed = true;
				}
				continue;
			}

			$stack[] = $tag_name;
		}

		return ! $processor->paused_at_incomplete_token() && 1 === $root_count && $root_closed && empty( $stack );
	}

	/**
	 * Checks a paint value against the supported positive grammar.
	 *
	 * @param string $paint Paint value.
	 * @return bool Whether the value is supported.
	 */
	private static function is_safe_paint( string $paint ): bool {
		return 1 === preg_match( self::SAFE_PAINT_PATTERN, trim( $paint, " \t\r\n\f" ) );
	}

	/**
	 * Adds caller-owned root classes and decorative accessibility attributes.
	 *
	 * @param string $svg         Valid sanitized replacement SVG.
	 * @param string $default_svg Default SVG markup.
	 * @return string Updated SVG, or the exact default when a root cannot be found.
	 */
	private static function enforce_root_invariants( string $svg, string $default_svg ): string {
		$default_processor = new WP_HTML_Tag_Processor( $default_svg );
		if ( ! $default_processor->next_tag( array( 'tag_name' => 'svg' ) ) || $default_processor->is_tag_closer() ) {
			return $default_svg;
		}

		$default_class_attribute = $default_processor->get_attribute( 'class' );
		$default_classes         = is_string( $default_class_attribute )
			? preg_split( '/[\x20\t\r\n\f]+/', trim( $default_class_attribute ), -1, PREG_SPLIT_NO_EMPTY )
			: array();
		if ( false === $default_classes ) {
			return $default_svg;
		}

		$processor = new WP_HTML_Tag_Processor( $svg );
		if ( ! $processor->next_tag( array( 'tag_name' => 'svg' ) ) || $processor->is_tag_closer() ) {
			return $default_svg;
		}

		foreach ( $default_classes as $default_class ) {
			if ( ! $processor->add_class( $default_class ) ) {
				return $default_svg;
			}
		}

		if ( ! $processor->set_attribute( 'aria-hidden', 'true' ) || ! $processor->set_attribute( 'focusable', 'false' ) ) {
			return $default_svg;
		}

		return $processor->get_updated_html();
	}
}
