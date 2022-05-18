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
	 *     Optional and defaults to self::TRIMMED_BALANCED_LOW_HTML_NO_LINKS. Otherwise, one or more of the following
	 *     keys should be set.
	 *
	 *     @type array $pre_processors  Callbacks to run before invoking `wp_kses()`.
	 *     @type array $wp_kses_rules   Element names and attributes to allow, per `wp_kses()`.
	 *     @type array $post_processors Callbacks to run after invoking `wp_kses()`.
	 * }
	 *
	 * @return string
	 */
	public function apply( array $sanitizer_rules = self::TRIMMED_BALANCED_LOW_HTML_NO_LINKS ): string {
		$output = $this->input;

		if ( isset( $sanitizer_rules['pre_processors'] ) && is_array( $sanitizer_rules['pre_processors'] ) ) {
			$output = $this->apply_string_callbacks( $sanitizer_rules['pre_processors'], $output );
		}

		if ( isset( $sanitizer_rules['wp_kses_rules'] ) && is_array( $sanitizer_rules['wp_kses_rules'] ) ) {
			$output = wp_kses( $output, $sanitizer_rules['wp_kses_rules'] );
		}

		if ( isset( $sanitizer_rules['post_processors'] ) && is_array( $sanitizer_rules['post_processors'] ) ) {
			$output = $this->apply_string_callbacks( $sanitizer_rules['post_processors'], $output );
		}

		return $output;
	}

	/**
	 * Applies callbacks used to process the string before and after wp_kses().
	 *
	 * If a callback is invalid we will short-circuit and return an empty string, on the grounds that it is better to
	 * output nothing than risky HTML. We also call the problem out via _doing_it_wrong() to highlight the problem (and
	 * increase the chances of this being caught during development).
	 *
	 * @param callable[] $callbacks The callbacks used to mutate the string.
	 * @param string     $string    The string being processed.
	 *
	 * @return string
	 */
	private function apply_string_callbacks( array $callbacks, string $string ): string {
		foreach ( $callbacks as $callback ) {
			if ( ! is_callable( $callback ) ) {
				_doing_it_wrong( __CLASS__ . '::apply', esc_html__( 'String processors must be valid callbacks.', 'woocommerce' ), esc_html( WC()->version ) );
				return '';
			}

			$string = (string) $callback( $string );
		}

		return $string;
	}
}
