<?php
/**
 * Helper class to format a short summary of content for a product.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Product Summary class.
 */
final class ProductSummary {
	/**
	 * Reference to product object.
	 *
	 * @var \WC_Product
	 */
	private $product = '';

	/**
	 * Constructor.
	 *
	 * @param \WC_Product $product The product to generate a summary for.
	 */
	public function __construct( \WC_Product $product ) {
		$this->product = $product;
	}

	/**
	 * Return the formatted summary.
	 *
	 * @param int $max_words Word limit for summary.
	 * @return string
	 */
	public function get_summary( $max_words = 25 ) {
		$summary = $this->get_content_for_summary();

		if ( $max_words && $this->get_word_count( $summary ) > $max_words ) {
			$summary = $this->generate_summary( $summary, $max_words );
		}

		return \wc_format_content( $summary );
	}

	/**
	 * Get description to base summary on from the product object.
	 *
	 * @return string
	 */
	private function get_content_for_summary() {
		$content = $this->product->get_short_description();

		if ( ! $content ) {
			$content = $this->product->get_description();
		}

		return $content;
	}

	/**
	 * Get the word count. Based on the logic in `wp_trim_words`.
	 *
	 * @param string $content HTML Content.
	 * @return int Length
	 */
	private function get_word_count( $content ) {
		$content = wp_strip_all_tags( $content );

		/*
		* translators: If your word count is based on single characters (e.g. East Asian characters),
		* enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
		* Do not translate into your own language.
		*/
		$type = _x( 'words', 'Word count type. Do not translate!' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain

		if ( strpos( $type, 'characters' ) === 0 && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
			$content = trim( preg_replace( "/[\n\r\t ]+/", ' ', $content ), ' ' );
			preg_match_all( '/./u', $content, $words_array );
			$words_array = $words_array[0];
		} else {
			$words_array = preg_split( "/[\n\r\t ]+/", $content );
		}

		return count( array_filter( $words_array ) );
	}

	/**
	 * Get the first paragraph, or a short excerpt, from some content.
	 *
	 * @param string $content HTML Content.
	 * @param int    $max_words Maximum allowed words for summary.
	 * @return string
	 */
	private function generate_summary( $content, $max_words ) {
		$content_p = \wpautop( $content );

		// This gets the first paragraph, if <p> tags are found. Otherwise returns the entire string.
		$paragraph = \strstr( $content_p, '</p>' ) ? \substr( $content_p, 0, \strpos( $content_p, '</p>' ) + 4 ) : $content;

		if ( $this->get_word_count( $paragraph ) > $max_words ) {
			return \wp_trim_words( $paragraph, $max_words );
		}

		return $paragraph;
	}
}
