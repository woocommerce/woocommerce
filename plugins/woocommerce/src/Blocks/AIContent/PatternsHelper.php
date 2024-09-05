<?php

namespace Automattic\WooCommerce\Blocks\AIContent;

use WP_Error;

/**
 * Patterns Helper class.
 *
 * @internal
 */
class PatternsHelper {
	/**
	 * Fetches the AI-selected image for the pattern or returns the default image.
	 *
	 * @param array  $images The array of images.
	 * @param int    $index The index of the image to return.
	 * @param string $default_image The default image to return.
	 *
	 * @return string The image.
	 */
	public static function get_image_url( $images, $index, $default_image ) {
		$image = filter_var( $default_image, FILTER_VALIDATE_URL )
			? $default_image
			: plugins_url( $default_image, dirname( __DIR__, 2 ) );

		if ( isset( $images[ $index ] ) ) {
			$image = $images[ $index ];
		}

		return $image;
	}

	/**
	 * Returns the post that has the generated data by the AI for the patterns.
	 *
	 * @return \WP_Post|null
	 */
	public static function get_patterns_ai_data_post() {
		$posts = get_posts(
			array(
				'post_type'      => 'patterns_ai_data',
				'posts_per_page' => 1,
				'cache_results'  => true,
			)
		);

		return $posts[0] ?? null;
	}

	/**
	 * Delete the post that has the generated data by the AI for the patterns.
	 *
	 * @return \WP_Post|null
	 */
	public static function delete_patterns_ai_data_post() {
		$patterns_ai_data_post = self::get_patterns_ai_data_post();

		if ( isset( $patterns_ai_data_post ) ) {
			return wp_delete_post( $patterns_ai_data_post->ID, true );
		}
	}


	/**
	 * Upsert the patterns AI data.
	 *
	 * @param array $patterns_dictionary The patterns' dictionary.
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
	 * @return array|WP_Error Returns pattern dictionary or WP_Error on failure.
	 */
	public static function get_patterns_dictionary( $pattern_slug = null ) {
		$default_patterns_dictionary = PatternsDictionary::get();

		if ( empty( $default_patterns_dictionary ) ) {
			return new WP_Error( 'missing_patterns_dictionary', __( 'The patterns dictionary is missing.', 'woocommerce' ) );
		}

		$patterns_dictionary   = '';
		$ai_connection_allowed = get_option( 'woocommerce_blocks_allow_ai_connection' );

		if ( $ai_connection_allowed ) {
			$patterns_ai_data_post = self::get_patterns_ai_data_post();
			if ( ! empty( $patterns_ai_data_post->post_content ) ) {
				$patterns_dictionary = json_decode( $patterns_ai_data_post->post_content, true );

				if ( json_last_error() !== JSON_ERROR_NONE ) {
					return new WP_Error( 'json_decode_error', __( 'Error decoding JSON.', 'woocommerce' ) );
				}
			}
		}

		if ( ( $patterns_dictionary === $default_patterns_dictionary || empty( $patterns_dictionary ) ) && $pattern_slug ) {
			return self::find_pattern_by_slug( $default_patterns_dictionary, $pattern_slug );
		} elseif ( $pattern_slug && is_array( $patterns_dictionary ) ) {
			return self::find_pattern_by_slug( $patterns_dictionary, $pattern_slug );
		} elseif ( is_array( $patterns_dictionary ) ) {
			return $patterns_dictionary;
		}

		return $default_patterns_dictionary;
	}

	/**
	 * Searches for a pattern by slug in a given dictionary.
	 *
	 * @param array  $patterns_dictionary The patterns' dictionary.
	 * @param string $slug The slug to search for.
	 *
	 * @return array|null Returns the pattern if found, otherwise null.
	 */
	private static function find_pattern_by_slug( $patterns_dictionary, $slug ) {
		foreach ( $patterns_dictionary as $pattern ) {
			if ( ! is_array( $pattern ) ) {
				continue;
			}

			if ( $pattern['slug'] === $slug ) {
				return $pattern;
			}
		}

		return null;
	}
}
