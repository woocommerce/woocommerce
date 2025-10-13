<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CategoryTitle block: renders the current term title using context.
 */
class CategoryTitle extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'category-title';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block     Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$term_id       = $block->context['termId'] ?? 0;
		$term_taxonomy = $block->context['termTaxonomy'] ?? 'product_cat';

		$level      = isset( $attributes['level'] ) ? max( 0, min( 6, intval( $attributes['level'] ) ) ) : 2;
		$text_align = isset( $attributes['textAlign'] ) ? sanitize_key( $attributes['textAlign'] ) : '';
		$is_link    = ! empty( $attributes['isLink'] );
		$rel        = isset( $attributes['rel'] ) ? esc_attr( $attributes['rel'] ) : '';
		$target     = isset( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '_self';

		if ( ! $term_id ) {
			return '';
		}

		$term = get_term( $term_id, $term_taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}

		$tag_name           = 0 === $level ? 'p' : 'h' . $level;
		$classes            = $text_align ? 'has-text-align-' . $text_align : '';
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classes ) );

		$title_html = '';
		if ( $is_link ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$title_html = sprintf(
					'<%1$s %2$s><a href="%3$s" target="%4$s" rel="%5$s">%6$s</a></%1$s>',
					esc_attr( $tag_name ),
					$wrapper_attributes,
					esc_url( $link ),
					esc_attr( $target ),
					$rel,
					esc_html( $term->name )
				);
			}
		}

		if ( '' === $title_html ) {
			$title_html = sprintf(
				'<%1$s %2$s>%3$s</%1$s>',
				esc_attr( $tag_name ),
				$wrapper_attributes,
				esc_html( $term->name )
			);
		}

		return $title_html;
	}

	/**
	 * Register the context used by this block.
	 *
	 * @return array
	 */
	protected function get_block_type_uses_context() {
		return [ 'termId', 'termTaxonomy' ];
	}

	/**
	 * Disable the style handle for this block.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
