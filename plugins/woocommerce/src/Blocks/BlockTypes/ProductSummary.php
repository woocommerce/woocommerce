<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductSummary class.
 */
class ProductSummary extends AbstractBlock {


	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-summary';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '3';

	/**
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
	}

	/**
	 * Get product description depends on config.
	 *
	 * @param WC_Product $product                   Product object.
	 * @param bool       $show_description_if_empty Defines if fallback to full description.
	 * @return string
	 */
	private function get_source( $product, $show_description_if_empty ) {
		$short_description = $product->get_short_description();

		if ( $short_description ) {
			// Logic copied from https://github.com/woocommerce/woocommerce/blob/637dde283057ed6667ff81c73ed08774552f631d/plugins/woocommerce/includes/wc-core-functions.php#L53-L62.
			$short_description = wp_kses_post( $short_description );
			$short_description = $GLOBALS['wp_embed']->run_shortcode( $short_description );
			$short_description = shortcode_unautop( $short_description );
			$short_description = do_shortcode( $short_description );
			return $short_description;
		}

		$description = $product->get_description();

		if ( $show_description_if_empty && $description ) {
			// Logic copied from https://github.com/woocommerce/woocommerce/blob/637dde283057ed6667ff81c73ed08774552f631d/plugins/woocommerce/includes/wc-core-functions.php#L53-L62.
			$description = wp_kses_post( $description );
			$description = $GLOBALS['wp_embed']->run_shortcode( $description );
			$description = shortcode_unautop( $description );
			$description = do_shortcode( $description );
			return $description;
		}

		return '';
	}

	/**
	 * Create anchor element based on input.
	 *
	 * @param WC_Product $product   Product object.
	 * @param string     $link_text Link text.
	 * @return string
	 */
	private function create_anchor( $product, $link_text ) {
		$href = esc_url( $product->get_permalink() );
		$text = wp_kses_post( $link_text );

		return '<a class="wp-block-woocommerce-product-summary__read_more" href="' . $href . '#tab-description">' . $text . '</a>';
	}

	/**
	 * Get first paragraph from some HTML text, or return the whole string.
	 *
	 * @param string $source Source text.
	 * @return string First paragraph found in string.
	 */
	private function get_first_paragraph( $source ) {
		$p_index = strpos( $source, '</p>' );

		if ( false === $p_index ) {
			return $source;
		}

		return substr( $source, 0, $p_index + 4 );
	}


	/**
	 * Count words, characters (excluding spaces), or characters (including spaces).
	 *
	 * @param string $text      Text to count.
	 * @param string $count_type Count type: 'words', 'characters_excluding_spaces', or 'characters_including_spaces'.
	 * @return int Count of specified type.
	 */
	private function count_text( $text, $count_type ) {
		switch ( $count_type ) {
			case 'characters_excluding_spaces':
				return strlen( preg_replace( '/\s+/', '', $text ) );
			case 'characters_including_spaces':
				return strlen( $text );
			case 'words':
			default:
				return str_word_count( wp_strip_all_tags( $text ) );
		}
	}

	/**
	 * Trim characters to a specified length.
	 *
	 * @param string $text Text to trim.
	 * @param int    $max_length Maximum length of the text.
	 * @param string $count_type What is being counted. One of 'words', 'characters_excluding_spaces', or 'characters_including_spaces'.
	 * @return string Trimmed text.
	 */
	private function trim_characters( $text, $max_length, $count_type ) {
		$pure_text = wp_strip_all_tags( $text );
		$trimmed   = mb_substr( $pure_text, 0, $max_length );

		if ( 'characters_including_spaces' === $count_type ) {
			return $trimmed;
		}

		preg_match_all( '/([\s]+)/', $trimmed, $spaces );
		$space_count = ! empty( $spaces[0] ) ? count( $spaces[0] ) : 0;
		return mb_substr( $pure_text, 0, $max_length + $space_count );
	}

	/**
	 * Generates the summary text from a string of text. It's not ideal
	 * but allows keeping the editor and frontend consistent.
	 *
	 * NOTE: If editing, keep it in sync with generateSummary function from
	 * plugins/woocommerce/client/blocks/assets/js/base/components/summary/utils.ts!
	 *
	 * Once HTML API allow for HTML manipulation both functions (PHP and JS)
	 * should be updated to solution fully respecting the word count.
	 * https://github.com/woocommerce/woocommerce/issues/52835
	 *
	 * @param string $source     Source text.
	 * @param int    $max_length Limit number of items returned if text has multiple paragraphs.
	 * @return string Generated summary.
	 */
	private function generate_summary( $source, $max_length ) {
		$count_type             = wp_get_word_count_type();
		$source_with_paragraphs = wpautop( $source );
		$source_word_count      = $this->count_text( $source_with_paragraphs, $count_type );

		if ( $source_word_count <= $max_length ) {
			return $source_with_paragraphs;
		}

		$first_paragraph            = $this->get_first_paragraph( $source_with_paragraphs );
		$first_paragraph_word_count = $this->count_text( $first_paragraph, $count_type );

		if ( $first_paragraph_word_count <= $max_length ) {
			return $first_paragraph;
		}

		if ( 'words' === $count_type ) {
			return wpautop( wp_trim_words( $first_paragraph, $max_length ) );
		}

		return $this->trim_characters( $first_paragraph, $max_length, $count_type ) . '…';
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}

		$post_id = $block->context['postId'] ?? '';
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return '';
		}

		$show_description_if_empty = isset( $attributes['showDescriptionIfEmpty'] ) && $attributes['showDescriptionIfEmpty'];
		$source                    = $this->get_source( $product, $show_description_if_empty );

		if ( ! $source ) {
			return '';
		}

		$summary_length = isset( $attributes['summaryLength'] ) ? $attributes['summaryLength'] : false;
		$link_text      = isset( $attributes['linkText'] ) ? $attributes['linkText'] : '';
		$show_link      = isset( $attributes['showLink'] ) && $attributes['showLink'];
		$summary        = $summary_length ? $this->generate_summary( $source, $summary_length ) : wpautop( $source );
		$final_summary  = $show_link && $link_text ? $summary . $this->create_anchor( $product, $link_text ) : $summary;

		$styles_and_classes = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		return sprintf(
			'<div class="wp-block-woocommerce-product-summary"><div class="wc-block-components-product-summary %1$s" style="%2$s">
				%3$s
			</div></div>',
			esc_attr( $styles_and_classes['classes'] ),
			esc_attr( $styles_and_classes['styles'] ?? '' ),
			$final_summary
		);
	}
}
