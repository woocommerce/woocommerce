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

		return $pattern['images'];
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
	 * Returns the post that has the generated data by the AI for the patterns.
	 *
	 * @return WP_Post|null
	 */
	public static function get_patterns_ai_data_post() {
		$arg = array(
			'post_type'      => 'patterns_ai_data',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'cache_results'  => true,
		);

		$query = new \WP_Query( $arg );

		$posts = $query->get_posts();
		return isset( $posts[0] ) ? $posts[0] : null;
	}

	/**
	 * Upsert the patterns AI data.
	 *
	 * @param array $patterns_dictionary The patterns dictionary.
	 *
	 * @return WP_Error|null
	 */
	public static function upsert_patterns_ai_data_post( $patterns_dictionary ) {
		$patterns_ai_data_post = self::get_patterns_ai_data_post();

		if ( isset( $patterns_ai_data_post ) ) {
			$patterns_ai_data_post->post_content = wp_json_encode( $patterns_dictionary );
			return wp_update_post( $patterns_ai_data_post, true );
		} else {
			$patterns_ai_data_post = array(
				'post_title'   => 'Patterns AI Data',
				'post_content' => wp_json_encode( $patterns_dictionary ),
				'post_status'  => 'publish',
				'post_type'    => 'patterns_ai_data',
			);
			return wp_insert_post( $patterns_ai_data_post, true );
		}
	}

	/**
	 * Get the Patterns Dictionary.
	 *
	 * @param string|null $pattern_slug The pattern slug.
	 *
	 * @return mixed|WP_Error|null
	 */
	public static function get_patterns_dictionary( $pattern_slug = null ) {

		$patterns_ai_data_post = self::get_patterns_ai_data_post();

		if ( isset( $patterns_ai_data_post ) ) {
			$patterns_dictionary = json_decode( $patterns_ai_data_post->post_content, true );
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
