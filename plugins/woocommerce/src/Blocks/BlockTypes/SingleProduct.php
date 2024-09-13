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
	 * Initialize the block and Hook into the `render_block_context` filter
	 * to update the context with the correct data.
	 *
	 * @var string
	 */
	protected function initialize() {
		parent::initialize();
		// Use an early priority to ensure other context hooks have access to the context provided here.
		add_filter( 'render_block_context', [ $this, 'update_context' ], 0, 3 );
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
