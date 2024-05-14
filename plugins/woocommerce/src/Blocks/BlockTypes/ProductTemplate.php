<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductCollectionUtils;
use WP_Block;

/**
 * ProductTemplate class.
 */
class ProductTemplate extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-template';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$query = ProductCollectionUtils::prepare_and_execute_query( $block );

		if ( ! $query->have_posts() ) {
			return '';
		}

		if ( $this->block_core_post_template_uses_featured_image( $block->inner_blocks ) ) {
			update_post_thumbnail_cache( $query );
		}

		$classnames = '';

		// For backward compatibility we need to keep the `displayLayout` handling even though we've
		// replaced it with Gutenberg's native `layout` support type. This is because the
		// deprecation won't run until they edit a post or page with the block and save.
		if ( isset( $block->context['displayLayout'] ) && isset( $block->context['query'] ) ) {
			if ( isset( $block->context['displayLayout']['type'] ) && 'flex' === $block->context['displayLayout']['type'] ) {
				if ( isset( $block->context['displayLayout']['shrinkColumns'] ) && $block->context['displayLayout']['shrinkColumns'] ) {
					$classnames = "wc-block-product-template__responsive columns-{$block->context['displayLayout']['columns']}";
				} else {
					$classnames = "is-flex-container columns-{$block->context['displayLayout']['columns']}";
				}
			}
		}

		if ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
			$classnames .= ' has-link-color';
		}

		$classnames .= ' wc-block-product-template';

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => trim( $classnames ) ) );

		$content = '';
		while ( $query->have_posts() ) {
			$query->the_post();

			// Get an instance of the current Post Template block.
			$block_instance = $block->parsed_block;

			// Set the block name to one that does not correspond to an existing registered block.
			// This ensures that for the inner instances of the Post Template block, we do not render any block supports.
			$block_instance['blockName'] = 'core/null';

			// Render the inner blocks of the Post Template block with `dynamic` set to `false` to prevent calling
			// `render_callback` and ensure that no wrapper markup is included.
			$block_content = (
			new WP_Block(
				$block_instance,
				array(
					'postType' => get_post_type(),
					'postId'   => get_the_ID(),
				)
			)
			)->render( array( 'dynamic' => false ) );

			// Wrap the render inner blocks in a `li` element with the appropriate post classes.
			$post_classes = implode( ' ', get_post_class( 'wc-block-product' ) );
			$content     .= '<li data-wc-key="product-item-' . get_the_ID() . '" class="' . esc_attr( $post_classes ) . '">' . $block_content . '</li>';
		}

		/*
		* Use this function to restore the context of the template tags
		* from a secondary query loop back to the main query loop.
		* Since we use two custom loops, it's safest to always restore.
		*/
		wp_reset_postdata();

		return sprintf(
			'<ul %1$s>%2$s</ul>',
			$wrapper_attributes,
			$content
		);
	}

	/**
	 * Determines whether a block list contains a block that uses the featured image.
	 *
	 * @param WP_Block_List $inner_blocks Inner block instance.
	 *
	 * @return bool Whether the block list contains a block that uses the featured image.
	 */
	protected function block_core_post_template_uses_featured_image( $inner_blocks ) {
		foreach ( $inner_blocks as $block ) {
			if ( 'core/post-featured-image' === $block->name ) {
				return true;
			}
			if (
			'core/cover' === $block->name &&
			! empty( $block->attributes['useFeaturedImage'] )
			) {
				return true;
			}
			if ( $block->inner_blocks && block_core_post_template_uses_featured_image( $block->inner_blocks ) ) {
				return true;
			}
		}

		return false;
	}
}
