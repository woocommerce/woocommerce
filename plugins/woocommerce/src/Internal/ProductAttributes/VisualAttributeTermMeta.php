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
		return self::build_term_visual( $term_id, $image_size );
	}

	/**
	 * Get normalized visual values for the given terms.
	 *
	 * @param array  $term_ids Term IDs.
	 * @param string $image_size Image size for image visual URLs.
	 * @return array<int, array{type: string, value: string}> Map of term ID to visual values.
	 *
	 * @since 10.9.0
	 */
	public static function get_term_visuals( array $term_ids, string $image_size = 'thumbnail' ): array {
		$visuals  = array();
		$term_ids = self::prime_term_visual_caches( $term_ids );

		foreach ( $term_ids as $term_id ) {
			$visuals[ $term_id ] = self::build_term_visual( $term_id, $image_size );
		}

		return $visuals;
	}

	/**
	 * Prime caches needed to build visual values for terms.
	 *
	 * @param array $term_ids Term IDs.
	 * @return array<int> Normalized term IDs.
	 *
	 * @since 10.9.0
	 */
	public static function prime_term_visual_caches( array $term_ids ): array {
		$term_ids = array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );

		if ( empty( $term_ids ) ) {
			return array();
		}

		update_meta_cache( 'term', $term_ids );

		$image_ids = array();
		foreach ( $term_ids as $term_id ) {
			$image_id = absint( get_term_meta( $term_id, 'image', true ) );

			if ( $image_id ) {
				$image_ids[] = $image_id;
			}
		}

		$image_ids = array_values( array_unique( $image_ids ) );
		if ( ! empty( $image_ids ) ) {
			_prime_post_caches( $image_ids, false, true );
		}

		return $term_ids;
	}

	/**
	 * Build a normalized visual value for a term.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $image_size Image size for image visual URLs.
	 * @return array{type: string, value: string}
	 */
	private static function build_term_visual( int $term_id, string $image_size ): array {
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
	 * Check whether a taxonomy is a wc-visual product attribute taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function is_visual_attribute_taxonomy( string $taxonomy ): bool {
		static $visual_attribute_taxonomies = array();
		static $cache_prefix                = '';

		$current_cache_prefix = \WC_Cache_Helper::get_cache_prefix( 'woocommerce-attributes' );
		if ( $cache_prefix !== $current_cache_prefix ) {
			$cache_prefix                = $current_cache_prefix;
			$visual_attribute_taxonomies = array();

			foreach ( wc_get_attribute_taxonomies() as $attribute ) {
				if ( 'wc-visual' === $attribute->attribute_type ) {
					$visual_attribute_taxonomies[ wc_attribute_taxonomy_name( $attribute->attribute_name ) ] = true;
				}
			}
		}

		return isset( $visual_attribute_taxonomies[ $taxonomy ] );
	}

	/**
	 * Save visual attribute term meta from request data.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param array  $request_data Request data.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function save_term_visual_from_request( int $term_id, string $taxonomy, array $request_data ): void {
		if ( ! self::is_visual_attribute_taxonomy( $taxonomy ) || ! self::has_visual_request_data( $request_data ) ) {
			return;
		}

		$visual_type = isset( $request_data['wc_visual_attribute_type'] ) ? self::sanitize_visual_type( $request_data['wc_visual_attribute_type'] ) : '';
		$color_value = isset( $request_data['term_color'] ) ? sanitize_hex_color( self::sanitize_request_string( $request_data['term_color'] ) ) : '';
		$image_id    = isset( $request_data['term_image'] ) ? absint( self::sanitize_request_string( $request_data['term_image'] ) ) : 0;

		if ( '' === $visual_type ) {
			$visual_type = $image_id ? self::TYPE_IMAGE : self::TYPE_COLOR;
		}

		self::save_term_visual_by_type( $term_id, $visual_type, $color_value ? $color_value : '', $image_id );
	}

	/**
	 * Check whether request data contains visual fields.
	 *
	 * @param array $request_data Request data.
	 * @return bool
	 */
	private static function has_visual_request_data( array $request_data ): bool {
		return isset( $request_data['wc_visual_attribute_type'] ) || isset( $request_data['term_color'] ) || isset( $request_data['term_image'] );
	}

	/**
	 * Sanitize a request value to a string.
	 *
	 * @param mixed $value Request value.
	 * @return string
	 */
	private static function sanitize_request_string( $value ): string {
		$value = wp_unslash( $value );

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Sanitize the visual type request value.
	 *
	 * @param mixed $type Visual type value.
	 * @return string
	 */
	private static function sanitize_visual_type( $type ): string {
		$type = self::sanitize_request_string( $type );

		return in_array( $type, array( self::TYPE_COLOR, self::TYPE_IMAGE ), true ) ? $type : '';
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
	 * Save visual attribute term meta using the selected visual type.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $type Selected visual type.
	 * @param string $color Hex color value.
	 * @param int    $image_id Attachment ID for the term image.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function save_term_visual_by_type( int $term_id, string $type, string $color = '', int $image_id = 0 ): void {
		if ( self::TYPE_IMAGE === $type ) {
			self::save_term_visual( $term_id, '', $image_id );
			return;
		}

		self::save_term_visual( $term_id, $color, 0 );
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
