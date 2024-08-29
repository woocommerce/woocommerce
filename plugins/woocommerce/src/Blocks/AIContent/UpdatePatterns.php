<?php

namespace Automattic\WooCommerce\Blocks\AIContent;

use Automattic\WooCommerce\Blocks\AI\Connection;
use WP_Error;

/**
 * Pattern Images class.
 */
class UpdatePatterns {

	/**
	 * All patterns that are actively in use in the Assembler.
	 */
	const WC_PATTERNS_IN_THE_ASSEMBLER = [
		'woocommerce-blocks/featured-category-triple',
		'woocommerce-blocks/hero-product-3-split',
		'woocommerce-blocks/hero-product-chessboard',
		'woocommerce-blocks/hero-product-split',
		'woocommerce-blocks/product-collection-4-columns',
		'woocommerce-blocks/product-collection-5-columns',
		'woocommerce-blocks/social-follow-us-in-social-media',
		'woocommerce-blocks/testimonials-3-columns',
		'woocommerce-blocks/product-collection-featured-products-5-columns',
	];

	/**
	 * Generate AI content and assign AI-managed images to Patterns.
	 *
	 * @param Connection      $ai_connection The AI connection.
	 * @param string|WP_Error $token The JWT token.
	 * @param array|WP_Error  $images The array of images.
	 * @param string          $business_description The business description.
	 *
	 * @return bool|WP_Error
	 */
	public function generate_content( $ai_connection, $token, $images, $business_description ) {
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$images              = ContentProcessor::verify_images( $images, $ai_connection, $token, $business_description );
		$patterns_dictionary = PatternsHelper::get_patterns_dictionary();

		if ( is_wp_error( $patterns_dictionary ) ) {
			return $patterns_dictionary;
		}

		$patterns = $this->assign_selected_images_to_patterns( $patterns_dictionary, $images['images'] );

		if ( is_wp_error( $patterns ) ) {
			return new WP_Error( 'failed_to_set_pattern_images', __( 'Failed to set the pattern images.', 'woocommerce' ) );
		}

		$ai_generated_patterns_content = $this->generate_ai_content_for_patterns( $ai_connection, $token, $patterns, $business_description );

		if ( is_wp_error( $ai_generated_patterns_content ) ) {
			return new WP_Error( 'failed_to_set_pattern_content', __( 'Failed to set the pattern content.', 'woocommerce' ) );
		}

		$patterns_ai_data_post = PatternsHelper::get_patterns_ai_data_post();

		if ( isset( $patterns_ai_data_post->post_content ) && json_decode( $patterns_ai_data_post->post_content ) === $ai_generated_patterns_content ) {
			return true;
		}

		$updated_content = PatternsHelper::upsert_patterns_ai_data_post( $ai_generated_patterns_content );

		if ( is_wp_error( $updated_content ) ) {
			return new WP_Error( 'failed_to_update_patterns_content', __( 'Failed to update patterns content.', 'woocommerce' ) );
		}

		return true;
	}

	/**
	 * Returns the patterns with AI generated content.
	 *
	 * @param Connection      $ai_connection The AI connection.
	 * @param string|WP_Error $token The JWT token.
	 * @param array           $patterns The array of patterns.
	 * @param string          $business_description The business description.
	 *
	 * @return array|WP_Error The patterns with AI generated content.
	 */
	public function generate_ai_content_for_patterns( $ai_connection, $token, $patterns, $business_description ) {
		$prompts                 = $this->prepare_prompts( $patterns );
		$expected_results_format = $this->prepare_expected_results_format( $prompts );
		$formatted_prompts       = $this->format_prompts_for_ai( $prompts, $business_description, $expected_results_format );
		$ai_responses            = $this->fetch_and_validate_ai_responses( $ai_connection, $token, $formatted_prompts, $expected_results_format );

		if ( is_wp_error( $ai_responses ) ) {
			return $ai_responses;
		}

		return $this->apply_ai_responses_to_patterns( $patterns, $ai_responses );
	}

	/**
	 * Prepares the prompts for the AI.
	 *
	 * @param array $patterns The array of patterns.
	 *
	 * @return array
	 */
	private function prepare_prompts( array $patterns ) {
		$prompts    = [];
		$result     = [];
		$group_size = count( self::WC_PATTERNS_IN_THE_ASSEMBLER );
		$i          = 1;
		foreach ( $patterns as $pattern ) {
			$slug = $pattern['slug'] ?? '';

			if ( ! in_array( $slug, self::WC_PATTERNS_IN_THE_ASSEMBLER, true ) ) {
				continue;
			}

			$content         = $pattern['content'] ?? '';
			$counter         = 1;
			$result[ $slug ] = [];

			if ( isset( $content['titles'] ) ) {
				foreach ( $content['titles'] as $title ) {
					$result[ $slug ][ $counter ++ ] = $title['ai_prompt'];
				}
			}

			if ( isset( $content['descriptions'] ) ) {
				foreach ( $content['descriptions'] as $description ) {
					$result[ $slug ][ $counter ++ ] = $description['ai_prompt'];
				}
			}

			if ( isset( $content['buttons'] ) ) {
				foreach ( $content['buttons'] as $button ) {
					$result[ $slug ][ $counter ++ ] = $button['ai_prompt'];
				}
			}

			$i ++;

			if ( $i === $group_size ) {
				$prompts[] = $result;
				$result    = [];
				$i         = 1;
			}
		}

		return $prompts;
	}

	/**
	 * Prepares the expected results format for the AI.
	 *
	 * @param array $prompts The array of prompts.
	 *
	 * @return array
	 */
	private function prepare_expected_results_format( array $prompts ) {
		$expected_results_format = [];
		foreach ( $prompts as $prompt ) {
			$expected_result_format = [];

			foreach ( $prompt as $key => $values ) {
				$expected_result_format[ $key ] = [];

				foreach ( $values as $sub_key => $sub_value ) {
					$expected_result_format[ $key ][ $sub_key ] = '';
				}
			}

			$expected_results_format[] = $expected_result_format;
		}

		return $expected_results_format;
	}

	/**
	 * Formats the prompts for the AI.
	 *
	 * @param array  $prompts The array of prompts.
	 * @param string $business_description The business description.
	 * @param array  $expected_results_format The expected results format.
	 *
	 * @return array
	 */
	private function format_prompts_for_ai( array $prompts, string $business_description, array $expected_results_format ) {
		$i                 = 0;
		$formatted_prompts = [];
		foreach ( $prompts as $prompt ) {
			$formatted_prompts[] = sprintf(
				"You are an experienced writer. Given a business described as: '%s', generate content for the sections using the following prompts for each one of them: `'%s'`, always making sure that the expected number of characters is respected. The response should be an array of data in JSON format. Each element should be an object with the pattern name as the key, and the generated content as values. Here's an example format: `'%s'`",
				$business_description,
				wp_json_encode( $prompt ),
				wp_json_encode( $expected_results_format[ $i ] )
			);
			$i ++;
		}

		return $formatted_prompts;
	}

	/**
	 * Fetches and validates the AI responses.
	 *
	 * @param Connection      $ai_connection The AI connection.
	 * @param string|WP_Error $token The JWT token.
	 * @param array           $formatted_prompts The array of formatted prompts.
	 * @param array           $expected_results_format The array of expected results format.
	 *
	 * @return array|mixed
	 */
	private function fetch_and_validate_ai_responses( $ai_connection, $token, $formatted_prompts, $expected_results_format ) {
		$ai_request_retries = 0;
		$ai_responses       = [];
		$success            = false;
		while ( $ai_request_retries < 5 && ! $success ) {
			$ai_request_retries ++;
			$ai_responses = $ai_connection->fetch_ai_responses( $token, $formatted_prompts, 60, 'json_object' );

			if ( is_wp_error( $ai_responses ) ) {
				continue;
			}

			if ( empty( $ai_responses ) ) {
				continue;
			}

			$loops_success = [];
			$i             = 0;
			foreach ( $ai_responses as $ai_response ) {
				if ( ! isset( $ai_response['completion'] ) ) {
					$loops_success[] = false;
					continue;
				}

				$completion = json_decode( $ai_response['completion'], true );

				if ( ! is_array( $completion ) ) {
					$loops_success[] = false;
					continue;
				}

				$diff = array_diff_key( $expected_results_format[ $i ], $completion );
				$i ++;

				if ( ! empty( $diff ) ) {
					$loops_success[] = false;
					continue;
				}

				$empty_results = false;
				foreach ( $completion as $completion_item ) {
					foreach ( $completion_item as $value ) {
						if ( empty( $value ) ) {
							$empty_results = true;
						}
					}
				}

				if ( $empty_results ) {
					$loops_success[] = false;
					continue;
				}

				$loops_success[] = true;
			}

			if ( ! in_array( false, $loops_success, true ) ) {
				$success = true;
			}
		}

		if ( ! $success ) {
			return new WP_Error( 'failed_to_fetch_ai_responses', __( 'Failed to fetch AI responses.', 'woocommerce' ) );
		}

		return $ai_responses;
	}

	/**
	 * Applies the AI responses to the patterns.
	 *
	 * @param array $patterns The array of patterns.
	 * @param array $ai_responses The array of AI responses.
	 *
	 * @return mixed
	 */
	private function apply_ai_responses_to_patterns( array $patterns, array $ai_responses ) {
		foreach ( $patterns as $i => $pattern ) {
			$pattern_slug = $pattern['slug'];

			if ( ! in_array( $pattern_slug, self::WC_PATTERNS_IN_THE_ASSEMBLER, true ) ) {
				continue;
			}

			foreach ( $ai_responses as $ai_response ) {
				$ai_response = json_decode( $ai_response['completion'], true );

				if ( isset( $ai_response[ $pattern_slug ] ) ) {
					$ai_response_content = $ai_response[ $pattern_slug ];

					$counter = 1;
					if ( isset( $patterns[ $i ]['content']['titles'] ) ) {
						foreach ( $patterns[ $i ]['content']['titles'] as $j => $title ) {
							if ( ! isset( $ai_response_content[ $counter ] ) ) {
								$ai_response_content[ $counter ] = $ai_response_content[ $counter - 1 ] ?? '';
							}

							$patterns[ $i ]['content']['titles'][ $j ]['default'] = $this->sanitize_string( $ai_response_content[ $counter ] );

							$counter ++;
						}
					}

					if ( isset( $patterns[ $i ]['content']['descriptions'] ) ) {
						foreach ( $patterns[ $i ]['content']['descriptions'] as $k => $description ) {
							if ( ! isset( $ai_response_content[ $counter ] ) ) {
								$ai_response_content[ $counter ] = $ai_response_content[ $counter - 1 ] ?? '';
							}

							$patterns[ $i ]['content']['descriptions'][ $k ]['default'] = $this->sanitize_string( $ai_response_content[ $counter ] );

							$counter ++;
						}
					}

					if ( isset( $patterns[ $i ]['content']['buttons'] ) ) {
						foreach ( $patterns[ $i ]['content']['buttons'] as $l => $button ) {
							if ( ! isset( $ai_response_content[ $counter ] ) ) {
								$ai_response_content[ $counter ] = $ai_response_content[ $counter - 1 ] ?? '';
							}

							$patterns[ $i ]['content']['buttons'][ $l ]['default'] = $this->sanitize_string( $ai_response_content[ $counter ] );

							$counter ++;
						}
					}
				}
			}
		}

		return $patterns;
	}

	/**
	 * Sanitize the string from the AI generated content. It removes double quotes that can cause issues when
	 * decoding the patterns JSON.
	 *
	 * @param string $string The string to be sanitized.
	 *
	 * @return string The sanitized string.
	 */
	private function sanitize_string( $string ) {
		return str_replace( '"', '', $string );
	}

	/**
	 * Assign selected images to patterns.
	 *
	 * @param array $patterns_dictionary The array of patterns.
	 * @param array $selected_images The array of images.
	 *
	 * @return array|WP_Error The patterns with images.
	 */
	private function assign_selected_images_to_patterns( $patterns_dictionary, $selected_images ) {
		$patterns_with_images = array();

		foreach ( $patterns_dictionary as $pattern ) {
			if ( ! $this->pattern_has_images( $pattern ) ) {
				$patterns_with_images[] = $pattern;
				continue;
			}

			list( $images, $alts ) = $this->get_images_for_pattern( $pattern, $selected_images );
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
	 * Get the Patterns Dictionary.
	 *
	 * @return mixed|WP_Error|null
	 */
	public static function get_patterns_dictionary() {
		$patterns_dictionary = PatternsDictionary::get();

		if ( empty( $patterns_dictionary ) ) {
			return new WP_Error( 'missing_patterns_dictionary', __( 'The patterns dictionary is missing.', 'woocommerce' ) );
		}

		return $patterns_dictionary;
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
	 * @param array $pattern         The array representing the pattern.
	 * @param array $selected_images The array of images.
	 *
	 * @return array An array containing an array of the images in the first position and their alts in the second.
	 */
	private function get_images_for_pattern( array $pattern, array $selected_images ): array {
		$images = array();
		$alts   = array();
		foreach ( $selected_images as $selected_image ) {
			if ( ! isset( $selected_image['title'] ) ) {
				continue;
			}

			if ( ! isset( $selected_image['URL'] ) ) {
				continue;
			}

			if ( str_contains( '.jpeg', $selected_image['title'] ) ) {
				continue;
			}

			$expected_image_format = $pattern['images_format'] ?? 'portrait';
			$selected_image_format = $this->get_selected_image_format( $selected_image );

			if ( $selected_image_format !== $expected_image_format ) {
				continue;
			}

			$selected_image_url = ContentProcessor::adjust_image_size( $selected_image['URL'], 'patterns' );

			$images[] = $selected_image_url;
			$alts[]   = $selected_image['title'];
		}

		return array( $images, $alts );
	}

	/**
	 * Returns the selected image format. Defaults to portrait.
	 *
	 * @param array $selected_image The selected image to be assigned to the pattern.
	 *
	 * @return string The selected image format.
	 */
	private function get_selected_image_format( $selected_image ) {
		if ( ! isset( $selected_image['width'], $selected_image['height'] ) ) {
			return 'portrait';
		}

		return $selected_image['width'] === $selected_image['height'] ? 'square' : ( $selected_image['width'] > $selected_image['height'] ? 'landscape' : 'portrait' );
	}
}
