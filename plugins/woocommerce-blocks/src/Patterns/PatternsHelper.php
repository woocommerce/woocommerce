<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

use WP_Error;

/**
 * Pattern Images Helper class.
 */
class PatternsHelper {
	/**
	 * Returns the pattern content.
	 *
	 * @param string $pattern_slug The pattern slug.
	 *
	 * @return array The pattern content.
	 */
	public static function get_pattern_content( string $pattern_slug ) {
		$pattern = self::get_patterns_dictionary( $pattern_slug );

		if ( empty( $pattern ) ) {
			return array();
		}

		if ( ! isset( $pattern['content'] ) ) {
			return array();
		}

		return $pattern['content'];
	}

	/**
	 * Returns the pattern images.
	 *
	 * @param string $pattern_slug The pattern slug.
	 *
	 * @return array The pattern images.
	 */
	public static function get_pattern_images( string $pattern_slug ): array {
		$pattern = self::get_patterns_dictionary( $pattern_slug );

		if ( empty( $pattern ) ) {
			return array();
		}

		if ( ! isset( $pattern['images'] ) ) {
			return array();
		}

		if ( ! isset( $pattern['images_total'] ) ) {
			return array();
		}

		return self::get_random_images( $pattern['images'], $pattern['images_total'] );
	}

	/**
	 * Returns the image for the given pattern.
	 *
	 * @param array  $images The array of images.
	 * @param int    $index The index of the image to return.
	 * @param string $default_image The default image to return.
	 *
	 * @return string The image.
	 */
	public static function get_image_url( array $images, int $index, string $default_image ): string {
		$image = filter_var( $default_image, FILTER_VALIDATE_URL )
			? $default_image
			: plugins_url( $default_image, dirname( __DIR__ ) );

		if ( isset( $images[ $index ] ) ) {
			$image = $images[ $index ];
		}

		return $image;
	}

	/**
	 * Returns an array of random images.
	 *
	 * @param array $images The pattern images.
	 * @param int   $images_total The total number of images needed for the pattern.
	 *
	 * @return array The random images.
	 */
	private static function get_random_images( array $images, int $images_total ): array {
		shuffle( $images );

		return array_slice( $images, 0, $images_total );
	}

	/**
	 * Get the Patterns Dictionary.
	 *
	 * @param string|null $pattern_slug The pattern slug.
	 *
	 * @return mixed|WP_Error|null
	 */
	private static function get_patterns_dictionary( $pattern_slug = null ) {
		$patterns_dictionary = get_option( PatternUpdater::WC_BLOCKS_PATTERNS_CONTENT );

		if ( ! empty( $patterns_dictionary ) ) {
			if ( empty( $pattern_slug ) ) {
				return $patterns_dictionary;
			}

			foreach ( $patterns_dictionary as $pattern_dictionary ) {
				if ( $pattern_dictionary['slug'] === $pattern_slug ) {
					return $pattern_dictionary;
				}
			}
		}

		$patterns_dictionary_file = plugin_dir_path( __FILE__ ) . 'dictionary.json';

		if ( ! file_exists( $patterns_dictionary_file ) ) {
			return new WP_Error( 'missing_patterns_dictionary', __( 'The patterns dictionary is missing.', 'woo-gutenberg-products-block' ) );
		}

		$patterns_dictionary = wp_json_file_decode( $patterns_dictionary_file, array( 'associative' => true ) );

		if ( ! empty( $pattern_slug ) ) {
			foreach ( $patterns_dictionary as $pattern_dictionary ) {
				if ( $pattern_dictionary['slug'] === $pattern_slug ) {
					return $pattern_dictionary;
				}
			}
		}

		return $patterns_dictionary;
	}
}
