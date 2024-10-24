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
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 * - Hook into pre_render_block to update the query.
	 */
	protected function initialize() {
		add_filter( 'block_type_metadata_settings', array( $this, 'add_block_type_metadata_settings' ), 10, 2 );
		parent::initialize();
	}

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
		if ( isset( $block->context['displayLayout'] ) && isset( $block->context['query'] ) ) {
			$classnames = 'is-product-collection-layout-' . $block->context['displayLayout']['type'] . ' ';

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
			$product_id     = get_the_ID();

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
						'postId'   => $product_id,
					)
				)
			)->render( array( 'dynamic' => false ) );

			$interactive = array(
				'namespace' => 'woocommerce/product-collection',
			);

			$context = array(
				'productId' => $product_id,
			);

			$li_directives = '
				data-wc-interactive=\'' . wp_json_encode( $interactive, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) . '\'
				data-wc-context=\'' . wp_json_encode( $context, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) . '\'
				data-wc-key="product-item-' . $product_id . '"
			';

			// Wrap the render inner blocks in a `li` element with the appropriate post classes.
			$post_classes = implode( ' ', get_post_class( 'wc-block-product' ) );
			$content     .= strtr(
				'<li class="{classes}"
					{li_directives}
				>
					{content}
				</li>',
				array(
					'{classes}'       => esc_attr( $post_classes ),
					'{li_directives}' => $li_directives,
					'{content}'       => $block_content,
				)
			);
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

	/**
	 * Product Template renders inner blocks manually so we need to skip default
	 * rendering routine for its inner blocks
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 * @return array
	 */
	public function add_block_type_metadata_settings( $settings, $metadata ) {
		if ( ! empty( $metadata['name'] ) && 'woocommerce/product-template' === $metadata['name'] ) {
			$settings['skip_inner_blocks'] = true;
		}
			return $settings;
	}
}
