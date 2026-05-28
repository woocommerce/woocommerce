<?php
/**
 * Visual attribute term meta utilities.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductAttributes;

/**
 * Utilities for wc-visual attribute term metadata.
 *
 * @internal
 *
 * @since 10.9.0
 */
class VisualAttributeTermMeta {

	/**
	 * Color visual type.
	 */
	public const TYPE_COLOR = 'color';

	/**
	 * Image visual type.
	 */
	public const TYPE_IMAGE = 'image';

	/**
	 * Empty visual type.
	 */
	public const TYPE_NONE = 'none';

	/**
	 * Get an empty visual term value.
	 *
	 * @return array{type: string, value: string}
	 *
	 * @since 10.9.0
	 */
	public static function get_empty_visual(): array {
		return array(
			'type'  => self::TYPE_NONE,
			'value' => '',
		);
	}

	/**
	 * Get the normalized visual value for a term.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $image_size Image size for image visual URLs.
	 * @return array{type: string, value: string}
	 *
	 * @since 10.9.0
	 */
	public static function get_term_visual( int $term_id, string $image_size = 'thumbnail' ): array {
		$image_id = absint( get_term_meta( $term_id, 'image', true ) );

		if ( $image_id && wp_attachment_is_image( $image_id ) ) {
			$image_url = wp_get_attachment_image_url( $image_id, $image_size );

			if ( $image_url ) {
				return array(
					'type'  => self::TYPE_IMAGE,
					'value' => $image_url,
				);
			}
		}

		$color = sanitize_hex_color( get_term_meta( $term_id, 'color', true ) );

		if ( $color ) {
			return array(
				'type'  => self::TYPE_COLOR,
				'value' => $color,
			);
		}

		return self::get_empty_visual();
	}

	/**
	 * Get normalized visual values for wc-visual attribute terms.
	 *
	 * @param string|null $attribute_name Optional product attribute taxonomy name, e.g. `pa_color`.
	 * @param string      $image_size Image size for image visual URLs.
	 * @return array<int, array{type: string, value: string}> Map of term ID to visual values.
	 *
	 * @since 10.9.0
	 */
	public static function get_attribute_term_visuals( ?string $attribute_name = null, string $image_size = 'thumbnail' ): array {
		$visuals        = array();
		$attribute_slug = $attribute_name ? wc_attribute_taxonomy_slug( $attribute_name ) : null;

		$attributes = wc_get_attribute_taxonomies();

		foreach ( $attributes as $attribute ) {
			if ( 'wc-visual' !== $attribute->attribute_type ) {
				continue;
			}

			if ( $attribute_slug && $attribute_slug !== $attribute->attribute_name ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => wc_attribute_taxonomy_name( $attribute->attribute_name ),
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( ! $term instanceof \WP_Term ) {
					continue;
				}

				$visuals[ $term->term_id ] = self::get_term_visual( (int) $term->term_id, $image_size );
			}
		}

		return $visuals;
	}

	/**
	 * Save mutually exclusive visual attribute term meta.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $color Hex color value.
	 * @param int    $image_id Attachment ID for the term image.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function save_term_visual( int $term_id, string $color = '', int $image_id = 0 ): void {
		if ( $image_id && wp_attachment_is_image( $image_id ) ) {
			update_term_meta( $term_id, 'image', absint( $image_id ) );
			delete_term_meta( $term_id, 'color' );
			return;
		}

		$sanitized_color = sanitize_hex_color( $color );
		if ( $sanitized_color ) {
			update_term_meta( $term_id, 'color', $sanitized_color );
			delete_term_meta( $term_id, 'image' );
			return;
		}

		delete_term_meta( $term_id, 'color' );
		delete_term_meta( $term_id, 'image' );
	}

	/**
	 * Build an inline swatch style from a normalized visual value.
	 *
	 * @param array{type?: string, value?: string} $visual Normalized visual value.
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_swatch_style( array $visual ): string {
		$type  = isset( $visual['type'] ) ? (string) $visual['type'] : self::TYPE_NONE;
		$value = isset( $visual['value'] ) ? (string) $visual['value'] : '';

		if ( self::TYPE_IMAGE === $type ) {
			$image = esc_url_raw( $value );

			if ( $image ) {
				return sprintf( "background-image:url('%s')", str_replace( "'", '%27', $image ) );
			}
		}

		if ( self::TYPE_COLOR === $type ) {
			$color = sanitize_hex_color( $value );

			if ( $color ) {
				return sprintf( 'background-color:%s', $color );
			}
		}

		return '';
	}
}
