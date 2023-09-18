<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * SingleProduct class.
 */
class SingleProduct extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'single-product';

	/**
	 * Product ID of the current product to be displayed in the Single Product block.
	 * This is used to replace the global post for the Single Product inner blocks.
	 *
	 * @var int
	 */
	protected $product_id = 0;

	/**
	 * Single Product inner blocks names.
	 * This is used to map all the inner blocks for a Single Product block.
	 *
	 * @var array
	 */
	protected $single_product_inner_blocks_names = [];

	/**
	 * Initialize the block and Hook into the `render_block_context` filter
	 * to update the context with the correct data.
	 *
	 * @var string
	 */
	protected function initialize() {
		parent::initialize();
		add_filter( 'render_block_context', [ $this, 'update_context' ], 10, 3 );
		add_filter( 'render_block_core/post-excerpt', [ $this, 'restore_global_post' ], 10, 3 );
		add_filter( 'render_block_core/post-title', [ $this, 'restore_global_post' ], 10, 3 );
	}

	/**
	 * Restore the global post variable right before generating the render output for the post title and/or post excerpt blocks.
	 *
	 * This is required due to the changes made via the replace_post_for_single_product_inner_block method.
	 * It is a temporary fix to ensure these blocks work as expected until Gutenberg versions 15.2 and 15.6 are part of the core of WordPress.
	 *
	 * @see https://github.com/WordPress/gutenberg/pull/48001
	 * @see https://github.com/WordPress/gutenberg/pull/49495
	 *
	 * @param  string    $block_content  The block content.
	 * @param  array     $parsed_block  The full block, including name and attributes.
	 * @param  \WP_Block $block_instance  The block instance.
	 *
	 * @return mixed
	 */
	public function restore_global_post( $block_content, $parsed_block, $block_instance ) {
		if ( isset( $block_instance->context['singleProduct'] ) && $block_instance->context['singleProduct'] ) {
			wp_reset_postdata();
		}

		return $block_content;
	}

	/**
	 * Update the context by injecting the correct post data
	 * for each one of the Single Product inner blocks.
	 *
	 * @param array    $context Block context.
	 * @param array    $block Block attributes.
	 * @param WP_Block $parent_block Block instance.
	 *
	 * @return array Updated block context.
	 */
	public function update_context( $context, $block, $parent_block ) {
		if ( 'woocommerce/single-product' === $block['blockName']
			&& isset( $block['attrs']['productId'] ) ) {
				$this->product_id = $block['attrs']['productId'];

				$this->single_product_inner_blocks_names = array_reverse(
					$this->extract_single_product_inner_block_names( $block )
				);
		}

		$this->replace_post_for_single_product_inner_block( $block, $context );

		return $context;
	}

	/**
	 * Extract the inner block names for the Single Product block. This way it's possible
	 * to map all the inner blocks for a Single Product block and manipulate the data as needed.
	 *
	 * @param array $block The Single Product block or its inner blocks.
	 * @param array $result Array of inner block names.
	 *
	 * @return array Array containing all the inner block names of a Single Product block.
	 */
	protected function extract_single_product_inner_block_names( $block, &$result = [] ) {
		if ( isset( $block['blockName'] ) ) {
			$result[] = $block['blockName'];
		}

		if ( isset( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				$this->extract_single_product_inner_block_names( $inner_block, $result );
			}
		}
		return $result;
	}

	/**
	 * Replace the global post for the Single Product inner blocks and reset it after.
	 *
	 * This is needed because some of the inner blocks may use the global post
	 * instead of fetching the product through the `productId` attribute, so even if the
	 * `productId` is passed to the inner block, it will still use the global post.
	 *
	 * @param array $block Block attributes.
	 * @param array $context Block context.
	 */
	protected function replace_post_for_single_product_inner_block( $block, &$context ) {
		if ( $this->single_product_inner_blocks_names ) {
			$block_name = array_pop( $this->single_product_inner_blocks_names );

			if ( $block_name === $block['blockName'] ) {
				/**
				 * This is a temporary fix to ensure the Post Title and Excerpt blocks work as expected
				 * until Gutenberg versions 15.2 and 15.6 are included in the core of WordPress.
				 *
				 * Important: the original post data is restored in the restore_global_post method.
				 *
				 * @see https://github.com/WordPress/gutenberg/pull/48001
				 * @see https://github.com/WordPress/gutenberg/pull/49495
				 */
				if ( 'core/post-excerpt' === $block_name || 'core/post-title' === $block_name ) {
					global $post;
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$post = get_post( $this->product_id );

					if ( $post instanceof \WP_Post ) {
						setup_postdata( $post );
					}
				}

				$context['postId']        = $this->product_id;
				$context['singleProduct'] = true;
			}
		}
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 *
	 * @return null This block has no frontend script.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
