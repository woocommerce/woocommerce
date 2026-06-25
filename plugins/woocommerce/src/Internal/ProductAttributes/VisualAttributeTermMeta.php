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
	 * Get the default color terms to create for a new wc-visual attribute.
	 *
	 * @return array<string, array{label: string, color: string}>
	 *
	 * @since 11.0.0
	 */
	private static function get_default_color_terms(): array {
		return array(
			'black'  => array(
				'label' => __( 'Black', 'woocommerce' ),
				'color' => '#121212',
			),
			'white'  => array(
				'label' => __( 'White', 'woocommerce' ),
				'color' => '#FFFFFF',
			),
			'gray'   => array(
				'label' => __( 'Gray', 'woocommerce' ),
				'color' => '#6E6E6E',
			),
			'red'    => array(
				'label' => __( 'Red', 'woocommerce' ),
				'color' => '#D32F2F',
			),
			'blue'   => array(
				'label' => __( 'Blue', 'woocommerce' ),
				'color' => '#1976D2',
			),
			'green'  => array(
				'label' => __( 'Green', 'woocommerce' ),
				'color' => '#388E3C',
			),
			'yellow' => array(
				'label' => __( 'Yellow', 'woocommerce' ),
				'color' => '#FBE02D',
			),
			'pink'   => array(
				'label' => __( 'Pink', 'woocommerce' ),
				'color' => '#EC407A',
			),
			'brown'  => array(
				'label' => __( 'Brown', 'woocommerce' ),
				'color' => '#5D4037',
			),
		);
	}

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

	/**
	 * Create default color terms for a newly created wc-visual attribute.
	 *
	 * @param int   $attribute_id Attribute ID.
	 * @param array $data         Attribute data from woocommerce_attribute_added.
	 * @return void
	 *
	 * @internal
	 *
	 * @since 11.0.0
	 */
	public static function seed_visual_attribute_terms( int $attribute_id, array $data ): void {
		if (
			0 >= $attribute_id ||
			! isset( $data['attribute_type'], $data['attribute_name'] ) ||
			! is_string( $data['attribute_type'] ) ||
			'wc-visual' !== $data['attribute_type'] ||
			! is_string( $data['attribute_name'] ) ||
			'' === trim( $data['attribute_name'] )
		) {
			return;
		}

		$taxonomy = wc_attribute_taxonomy_name( $data['attribute_name'] );

		// Taxonomy is registered on init from the cached list but not yet available
		// at woocommerce_attribute_added time (cache invalidated after the hook).
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, array( 'product' ) );
		}

		foreach ( self::get_default_color_terms() as $slug => $term ) {
			if ( get_term_by( 'slug', $slug, $taxonomy ) ) {
				continue;
			}

			$result = wp_insert_term( $term['label'], $taxonomy, array( 'slug' => $slug ) );

			if ( is_wp_error( $result ) || empty( $result['term_id'] ) ) {
				continue;
			}

			self::save_term_visual( (int) $result['term_id'], $term['color'], 0 );
		}
	}
}
