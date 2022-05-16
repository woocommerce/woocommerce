<?php

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Utility for re-using WP Kses-based sanitization rules.
 */
class HtmlSanitizer {
	/**
	 * Rules for allowing minimal HTML (breaks, images, paragraphs and spans) without any links.
	 */
	public const TRIMMED_BALANCED_LOW_HTML_NO_LINKS = array(
		'pre_processors'  => array(
			'stripslashes',
		),
		'wp_kses_rules'   => array(
			'br'   => true,
			'img'  => array(
				'src'   => true,
				'style' => true,
				'title' => true,
			),
			'p'    => true,
			'span' => true,
		),
		'post_processors' => array(
			'force_balance_tags',
			'trim',
		),
	);

	/**
	 * Input HTML string.
	 *
	 * @var string
	 */
	private $input;

	/**
	 * Sanitizes the provided HTML.
	 *
	 * @param string $input_html The HTML to be sanitized.
	 */
	public function __construct( string $input_html ) {
		$this->input = $input_html;
	}

	/**
	 * Sanitizes the HTML according to the provided rules.
	 *
	 * @see wp_kses()
	 *
	 * @param array $sanitizer_rules {
	 *     Optional. If provided, one or more of the following keys should be set.
	 *
	 *     @type array $pre_processors  Callbacks to run before invoking `wp_kses()`.
	 *     @type array $wp_kses_rules   Element names and attributes to allow, per `wp_kses()`.
	 *     @type array $post_processors Callbacks to run after invoking `wp_kses()`.
	 * }
	 *
	 * @return string
	 */
	public function apply( array $sanitizer_rules ): string {
		$output = $this->input;

		if ( isset( $sanitizer_rules['pre_processors'] ) && is_array( $sanitizer_rules['pre_processors'] ) ) {
			foreach ( $sanitizer_rules['pre_processors'] as $callback ) {
				$output = $callback( $output );
			}
		}

		if ( isset( $sanitizer_rules['wp_kses_rules'] ) && is_array( $sanitizer_rules['wp_kses_rules'] ) ) {
			$output = wp_kses( $output, $sanitizer_rules['wp_kses_rules'] );
		}

		if ( isset( $sanitizer_rules['post_processors'] ) && is_array( $sanitizer_rules['post_processors'] ) ) {
			foreach ( $sanitizer_rules['post_processors'] as $callback ) {
				$output = $callback( $output );
			}
		}

		return $output;
	}
}
