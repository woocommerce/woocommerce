<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Featured Product Title block: renders the current product title using context.
 *
 * Used inside the Featured Product block. The `content` attribute holds a
 * locally edited title that overrides the underlying product name, so editing
 * the title in the block does not change the product data itself.
 */
class FeaturedProductTitle extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'featured-product-title';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$post_id = $block->context['postId'] ?? 0;

		$level      = isset( $attributes['level'] ) ? max( 0, min( 6, intval( $attributes['level'] ) ) ) : 2;
		$text_align = isset( $attributes['textAlign'] ) ? sanitize_key( $attributes['textAlign'] ) : '';
		$is_link    = ! empty( $attributes['isLink'] );
		$rel        = isset( $attributes['rel'] ) ? esc_attr( $attributes['rel'] ) : '';
		$target     = isset( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '_self';

		if ( ! $post_id ) {
			return '';
		}

		// Use the locally edited content when decoupled editing is enabled (e.g. inside a
		// Featured Product block), falling back to the product title otherwise.
		$decoupled = ! empty( $block->context['decoupledEdit'] );
		// Once `content` has been set (even to an empty string) the block stays
		// detached from the product, falling back to the product title only when
		// the attribute is absent.
		$title = $decoupled && array_key_exists( 'content', $attributes )
			? (string) $attributes['content']
			: get_the_title( $post_id );

		if ( '' === trim( (string) $title ) ) {
			return '';
		}

		$tag_name           = 0 === $level ? 'p' : 'h' . $level;
		$classes            = $text_align ? 'has-text-align-' . $text_align : '';
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classes ) );

		$title_html = '';
		if ( $is_link ) {
			$link = get_permalink( $post_id );
			if ( $link ) {
				$title_html = sprintf(
					'<%1$s %2$s><a href="%3$s" target="%4$s" rel="%5$s">%6$s</a></%1$s>',
					esc_attr( $tag_name ),
					$wrapper_attributes,
					esc_url( $link ),
					esc_attr( $target ),
					$rel,
					esc_html( $title )
				);
			}
		}

		if ( '' === $title_html ) {
			$title_html = sprintf(
				'<%1$s %2$s>%3$s</%1$s>',
				esc_attr( $tag_name ),
				$wrapper_attributes,
				esc_html( $title )
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
		return [ 'postId', 'postType', 'decoupledEdit' ];
	}

	/**
	 * Disable the frontend script for this block.
	 *
	 * @param string|null $key Data to get, or default to everything.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
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
