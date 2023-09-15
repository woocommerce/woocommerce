<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\Verticals\Client;
use WP_Error;

/**
 * Pattern Images class.
 */
class PatternImages {
	/**
	 * The patterns content option name.
	 */
	const WC_BLOCKS_PATTERNS_CONTENT = 'wc_blocks_patterns_content';

	/**
	 * Get the Patterns Dictionary.
	 *
	 * @return mixed|WP_Error|null
	 */
	public function get_patterns_dictionary() {
		$patterns_dictionary = plugin_dir_path( __FILE__ ) . 'dictionary.json';

		if ( ! file_exists( $patterns_dictionary ) ) {
			return new WP_Error( 'missing_patterns_dictionary', __( 'The patterns dictionary is missing.', 'woo-gutenberg-products-block' ) );
		}

		return wp_json_file_decode( $patterns_dictionary, array( 'associative' => true ) );
	}

	/**
	 * Creates the patterns content for the given vertical.
	 *
	 * @param  int    $vertical_id  The vertical id.
	 * @param  Client $verticals_api_client  The verticals API client.
	 *
	 * @return bool|WP_Error
	 */
	public function create_patterns_content( $vertical_id, $verticals_api_client ) {
		if ( ! is_int( $vertical_id ) ) {
			return new WP_Error( 'invalid_vertical_id', __( 'The vertical id is invalid.', 'woo-gutenberg-products-block' ) );
		}

		$vertical_images = $verticals_api_client->get_vertical_images( $vertical_id );

		if ( is_wp_error( $vertical_images ) ) {
			return $vertical_images;
		}

		$patterns_with_images = $this->get_patterns_with_images( $vertical_images );

		if ( get_option( self::WC_BLOCKS_PATTERNS_CONTENT ) === $patterns_with_images ) {
			return true;
		}

		$updated_content = update_option( self::WC_BLOCKS_PATTERNS_CONTENT, $patterns_with_images );

		if ( ! $updated_content ) {
			return new WP_Error( 'failed_to_update_patterns_content', __( 'Failed to update patterns content.', 'woo-gutenberg-products-block' ) );
		}

		return $updated_content;
	}

	/**
	 * Returns the patterns with images.
	 *
	 * @param array $vertical_images The array of vertical images.
	 *
	 * @return array The patterns with images.
	 */
	private function get_patterns_with_images( $vertical_images ) {
		$patterns_dictionary = $this->get_patterns_dictionary();

		if ( is_wp_error( $patterns_dictionary ) ) {
			return $patterns_dictionary;
		}

		$patterns_with_images = array();

		foreach ( $patterns_dictionary as $pattern ) {
			if ( ! $this->pattern_has_images( $pattern ) ) {
				continue;
			}

			$images = $this->get_images_for_pattern( $pattern, $vertical_images );
			if ( empty( $images ) ) {
				continue;
			}

			$pattern['images']      = $images;
			$patterns_with_images[] = $pattern;
		}

		return $patterns_with_images;
	}

	/**
	 * Returns whether the pattern has images.
	 *
	 * @param array $pattern The array representing the pattern.
	 *
	 * @return bool True if the pattern has images, false otherwise.
	 */
	private function pattern_has_images( array $pattern ): bool {
		return isset( $pattern['images_total'] ) && $pattern['images_total'] > 0;
	}

	/**
	 * Returns the images for the given pattern.
	 *
	 * @param array $pattern The array representing the pattern.
	 * @param array $vertical_images The array of vertical images.
	 *
	 * @return string[]
	 */
	private function get_images_for_pattern( array $pattern, array $vertical_images ): array {
		$images = array();
		if ( count( $vertical_images ) < $pattern['images_total'] ) {
			return $images;
		}

		foreach ( $vertical_images as $vertical_image ) {
			if ( $pattern['images_format'] === $this->get_image_format( $vertical_image ) ) {
				$images[] = str_replace( 'http://', 'https://', $vertical_image['guid'] );
			}
		}

		return $images;
	}

	/**
	 * Returns the image format for the given vertical image.
	 *
	 * @param array $vertical_image The vertical image.
	 *
	 * @return string The image format, or an empty string if the image format is invalid.
	 */
	private function get_image_format( array $vertical_image ): string {
		if ( ! isset( $vertical_image['width'] ) || ! isset( $vertical_image['height'] ) ) {
			return '';
		}

		if ( 0 === $vertical_image['width'] || 0 === $vertical_image['height'] ) {
			return '';
		}

		if ( $vertical_image['width'] === $vertical_image['height'] ) {
			return 'square';
		}

		if ( $vertical_image['width'] < $vertical_image['height'] ) {
			return 'portrait';
		}

		return 'landscape';
	}
}
