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
	 * While the block is actively overriding the global post state,
	 * this will contain the previous post set as the global state.
	 *
	 * @var WP_Post|null
	 */
	protected $overridden_post = null;

	/**
	 * Initialize the block and Hook into the `render_block_context` filter
	 * to update the context with the correct data.
	 *
	 * @var string
	 */
	protected function initialize() {
		parent::initialize();
		// Use an early priority to ensure other context hooks have access to the context provided here.
		add_filter( 'render_block_context', [ $this, 'update_context' ], 0, 2 );

		// In order to ensure that inner blocks have access to the correct post data,
		// we're going to hook into block rendering to set the global post state.
		// The `pre_render_block` hook should be as early as possible and is run
		// before any rendering takes place. A late hook on `render_block` will
		// make sure that all of the rendering for the block has taken place.
		add_filter( 'pre_render_block', [ $this, 'update_post' ], 0, 2 );
		add_filter( 'render_block_woocommerce/single-product', [ $this, 'reset_post' ], 100, 3 );
	}

	/**
	 * Update the context by injecting the correct post data
	 * for each one of the Single Product inner blocks.
	 *
	 * @param array $context Block context.
	 * @param array $block Block attributes.
	 */
	public function update_context( $context, $block ) {
		if ( 'woocommerce/single-product' !== $block['blockName'] ) {
			return $context;
		}

		$id = $block['attrs']['productId'] ?? null;
		if ( ! isset( $id ) ) {
			return $context;
		}

		// Override the context with the selected product.
		$context['postId']        = $id;
		$context['postType']      = get_post_type( $id );
		$context['singleProduct'] = true;
		return $context;
	}

	/**
	 * Updates the global post state to the selected product.
	 *
	 * @param string|null $pre_render   Any pre-rendered content.
	 * @param array       $parsed_block A representation of the block being rendered.
	 */
	public function update_post( $pre_render, $parsed_block ) {
		if ( 'woocommerce/single-product' !== $parsed_block['blockName'] ) {
			return $pre_render;
		}

		// When `pre_render_block` returns a non-null value, WordPress short-circuits
		// block rendering since it assumes the block has already been rendered. If
		// this is the case, we shouldn't set the global post state since we
		// can't hook into the `render_block` hook to reset the post state.
		if ( isset( $pre_render ) ) {
			return $pre_render;
		}

		$id = $parsed_block['attrs']['productId'] ?? null;
		if ( ! isset( $id ) ) {
			return $pre_render;
		}

		$selected_product = get_post( $parsed_block['attrs']['productId'] );
		if ( ! isset( $selected_product ) ) {
			return $pre_render;
		}

		// Before rendering our inner blocks we need to set the global post state
		// to the selected product. This ensures that inner blocks have access
		// to the correct post data.
		global $post;
		$this->overridden_post = $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $selected_product;
		setup_postdata( $post );

		return $pre_render;
	}

	/**
	 * Resets the global post state after rendering the inner blocks.
	 *
	 * @param string $block_content The block content.
	 * @param array  $parsed_block  The parsed block data.
	 */
	public function reset_post( $block_content, $parsed_block ) {
		if ( 'woocommerce/single-product' !== $parsed_block['blockName'] ) {
			return $block_content;
		}

		// We don't want to reset anything if we didn't set it in the first place.
		if ( isset( $this->overridden_post ) ) {
			return $block_content;
		}

		// Replace the global post with the one that was set prior to us
		// overriding it with the selected product.
		global $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $this->overridden_post;
		setup_postdata( $post );
		$this->overridden_post = null;

		return $block_content;
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
