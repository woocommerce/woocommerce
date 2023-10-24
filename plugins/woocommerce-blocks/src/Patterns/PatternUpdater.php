<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Verticals\Client;
use Automattic\WooCommerce\Blocks\Verticals\VerticalsSelector;
use WP_Error;

/**
 * Pattern Images class.
 */
class PatternUpdater {
	/**
	 * The AI Connection.
	 *
	 * @var Connection
	 */
	private $ai_connection;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->ai_connection = new Connection();
	}

	/**
	 * The patterns content option name.
	 */
	const WC_BLOCKS_PATTERNS_CONTENT = 'wc_blocks_patterns_content';

	/**
	 * Creates the patterns content for the given vertical.
	 *
	 * @param array|WP_Error $vertical_images The array of vertical images.
	 * @param string         $business_description The business description.
	 *
	 * @return bool|WP_Error
	 */
	public function generate_content( $vertical_images, $business_description ) {
		if ( is_wp_error( $vertical_images ) ) {
			return $vertical_images;
		}

		$patterns_with_images = $this->get_patterns_with_images( $vertical_images );

		if ( is_wp_error( $patterns_with_images ) ) {
			return new WP_Error( 'failed_to_set_pattern_images', __( 'Failed to set the pattern images.', 'woo-gutenberg-products-block' ) );
		}

		$patterns_with_images_and_content = $this->get_patterns_with_content( $patterns_with_images, $business_description );

		if ( is_wp_error( $patterns_with_images_and_content ) ) {
			return new WP_Error( 'failed_to_set_pattern_content', __( 'Failed to set the pattern content.', 'woo-gutenberg-products-block' ) );
		}

		if ( get_option( self::WC_BLOCKS_PATTERNS_CONTENT ) === $patterns_with_images_and_content ) {
			return true;
		}

		$updated_content = update_option( self::WC_BLOCKS_PATTERNS_CONTENT, $patterns_with_images_and_content );

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
	 * @return array|WP_Error The patterns with images.
	 */
	private function get_patterns_with_images( $vertical_images ) {
		$patterns_dictionary = $this->get_patterns_dictionary();

		if ( is_wp_error( $patterns_dictionary ) ) {
			return $patterns_dictionary;
		}

		$patterns_with_images = array();

		foreach ( $patterns_dictionary as $pattern ) {
			if ( ! $this->pattern_has_images( $pattern ) ) {
				$patterns_with_images[] = $pattern;
				continue;
			}

			list($images, $alts) = $this->get_images_for_pattern( $pattern, $vertical_images );
			if ( empty( $images ) ) {
				$patterns_with_images[] = $pattern;
				continue;
			}

			$pattern['images'] = $images;

			$string = wp_json_encode( $pattern );

			foreach ( $alts as $i => $alt ) {
				$alt    = empty( $alt ) ? 'the text should be related to the store description but generic enough to adapt to any image' : $alt;
				$string = str_replace( "{image.$i}", $alt, $string );
			}

			$pattern = json_decode( $string, true );

			$patterns_with_images[] = $pattern;
		}

		return $patterns_with_images;
	}

	/**
	 * Returns the patterns with AI generated content.
	 *
	 * @param array  $patterns The array of patterns.
	 * @param string $business_description The business description.
	 *
	 * @return array|WP_Error The patterns with AI generated content.
	 */
	private function get_patterns_with_content( array $patterns, string $business_description ) {
		$site_id = $this->ai_connection->get_site_id();

		if ( is_wp_error( $site_id ) ) {
			return $site_id;
		}

		$token = $this->ai_connection->get_jwt_token( $site_id );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$patterns_with_content = $patterns;

		$prompts = array();
		foreach ( $patterns_with_content as $pattern ) {
			$prompt  = sprintf( 'Given the following store description: "%s", and the following JSON file representing the content of the "%s" pattern: %s.\n', $business_description, $pattern['name'], wp_json_encode( $pattern['content'] ) );
			$prompt .= "Replace the titles, descriptions and button texts in each 'default' key using the prompt in the corresponding 'ai_prompt' key by a text that is related to the previous store description (but not the exact text) and matches the 'ai_prompt', the length of each replacement should be similar to the 'default' text length. The response should be only a JSON string, with absolutely no intro or explanations.";

			$prompts[] = $prompt;
		}

		$responses = $this->ai_connection->fetch_ai_responses( $token, $prompts );

		foreach ( $responses as $key => $response ) {
			// If the AI response is invalid, we skip the pattern and keep the default content.
			if ( is_wp_error( $response ) || empty( $response ) ) {
				continue;
			}

			if ( ! isset( $response['completion'] ) ) {
				continue;
			}

			$pattern_content = json_decode( $response['completion'], true );
			if ( ! is_null( $pattern_content ) ) {
				$patterns_with_content[ $key ]['content'] = $pattern_content;
			}
		}

		return $patterns_with_content;
	}

	/**
	 * Get the Patterns Dictionary.
	 *
	 * @return mixed|WP_Error|null
	 */
	private function get_patterns_dictionary() {
		$patterns_dictionary = plugin_dir_path( __FILE__ ) . 'dictionary.json';

		if ( ! file_exists( $patterns_dictionary ) ) {
			return new WP_Error( 'missing_patterns_dictionary', __( 'The patterns dictionary is missing.', 'woo-gutenberg-products-block' ) );
		}

		return wp_json_file_decode( $patterns_dictionary, array( 'associative' => true ) );
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
	 * @return array An array containing an array of the images in the first position and their alts in the second.
	 */
	private function get_images_for_pattern( array $pattern, array $vertical_images ): array {
		$alts   = array();
		$images = array();
		if ( count( $vertical_images ) < $pattern['images_total'] ) {
			return array( $images, $alts );
		}

		foreach ( $vertical_images as $vertical_image ) {
			if ( $pattern['images_format'] === $this->get_image_format( $vertical_image ) ) {
				$images[] = str_replace( 'http://', 'https://', $vertical_image['guid'] );
				$alts[]   = $vertical_image['meta']['pexels_object']['alt'] ?? '';
			}
		}

		return array( $images, $alts );
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
