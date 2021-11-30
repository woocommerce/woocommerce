<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * StyleAttributesUtils class used for getting class and style from attributes.
 */
class StyleAttributesUtils {

	/**
	 * Get class and style for font-size from attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return (array | null)
	 */
	public static function get_font_size_class_and_style( $attributes ) {

		$font_size = $attributes['fontSize'];

		$custom_font_size = isset( $attributes['style']['typography']['fontSize'] ) ? $attributes['style']['typography']['fontSize'] : null;

		if ( ! isset( $font_size ) && ! isset( $custom_font_size ) ) {
			return null;
		};

		$has_named_font_size  = ! empty( $font_size );
		$has_custom_font_size = isset( $custom_font_size );

		if ( $has_named_font_size ) {
			return array(
				'class' => sprintf( 'has-font-size has-%s-font-size', $font_size ),
				'style' => null,
			);
		} elseif ( $has_custom_font_size ) {
			return array(
				'class' => null,
				'style' => sprintf( 'font-size: %s;', $custom_font_size ),
			);
		}
		return null;
	}

	/**
	 * Get class and style for text-color from attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return (array | null)
	 */
	public static function get_text_color_class_and_style( $attributes ) {

		$text_color = $attributes['textColor'];

		$custom_text_color = isset( $attributes['style']['color']['text'] ) ? $attributes['style']['color']['text'] : null;

		if ( ! isset( $text_color ) && ! isset( $custom_text_color ) ) {
			return null;
		};

		$has_named_text_color  = ! empty( $text_color );
		$has_custom_text_color = isset( $custom_text_color );

		if ( $has_named_text_color ) {
			return array(
				'class' => sprintf( 'has-text-color has-%s-color', $text_color ),
				'style' => null,
			);
		} elseif ( $has_custom_text_color ) {
			return array(
				'class' => null,
				'style' => sprintf( 'color: %s;', $custom_text_color ),
			);
		}
		return null;
	}

	/**
	 * Get class and style for link-color from attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return (array | null)
	 */
	public static function get_link_color_class_and_style( $attributes ) {

		if ( ! isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
			return null;
		};

		$link_color = $attributes['style']['elements']['link']['color']['text'];

		// If the link color is selected from the theme color picker, the value of $link_color is var:preset|color|slug.
		// If the link color is selected from the core color picker, the value of $link_color is an hex value.
		// When the link color is a string var:preset|color|slug we parsed it for get the slug, otherwise we use the hex value.
		$index_named_link_color = strrpos( $link_color, '|' );

		if ( ! empty( $index_named_link_color ) ) {
			$parsed_named_link_color = substr( $link_color, $index_named_link_color + 1 );
			return array(
				'class' => null,
				'style' => sprintf( 'color: %s;', $parsed_named_link_color ),
			);
		} else {
			return array(
				'class' => null,
				'style' => sprintf( 'color: %s;', $link_color ),
			);
		}
	}

	/**
	 * Get class and style for line height from attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return (array | null)
	 */
	public static function get_line_height_class_and_style( $attributes ) {

		$line_height = isset( $attributes['style']['typography']['lineHeight'] ) ? $attributes['style']['typography']['lineHeight'] : null;

		if ( ! isset( $line_height ) ) {
			return null;
		};

		$line_height_style = sprintf( 'line-height: %s;', $line_height );

		return array(
			'class' => null,
			'style' => $line_height_style,
		);
	}

	/**
	 * Get classes and styles from attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $properties Properties to get classes/styles from.
	 *
	 * @return array
	 */
	public static function get_classes_and_styles_by_attributes( $attributes, $properties = array() ) {
		$classes_and_styles = array(
			'line_height' => self::get_line_height_class_and_style( $attributes ),
			'text_color'  => self::get_text_color_class_and_style( $attributes ),
			'font_size'   => self::get_font_size_class_and_style( $attributes ),
			'link_color'  => self::get_link_color_class_and_style( $attributes ),
		);

		if ( ! empty( $properties ) ) {
			foreach ( $classes_and_styles as $key => $value ) {
				if ( ! in_array( $key, $properties, true ) ) {
					unset( $classes_and_styles[ $key ] );
				}
			}
		}

		$classes_and_styles = array_filter( $classes_and_styles );

		$classes = array_map(
			function( $item ) {
				return $item['class'];
			},
			$classes_and_styles
		);

		$styles = array_map(
			function( $item ) {
				return $item['style'];
			},
			$classes_and_styles
		);

		$classes = array_filter( $classes );
		$styles  = array_filter( $styles );

		return array(
			'classes' => implode( ' ', $classes ),
			'styles'  => implode( ' ', $styles ),
		);
	}

	/**
	 * Get space-separated classes from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $properties Properties to get classes from.
	 *
	 * @return string Space-separated classes.
	 */
	public static function get_classes_by_attributes( $attributes, $properties = array() ) {
		$classes_and_styles = self::get_classes_and_styles_by_attributes( $attributes, $properties );

		return $classes_and_styles['classes'];
	}

	/**
	 * Get space-separated style rules from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $properties Properties to get styles from.
	 *
	 * @return string Space-separated style rules.
	 */
	public static function get_styles_by_attributes( $attributes, $properties = array() ) {
		$classes_and_styles = self::get_classes_and_styles_by_attributes( $attributes, $properties );

		return $classes_and_styles['styles'];
	}
}
