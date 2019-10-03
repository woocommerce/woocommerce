<?php
/**
 * Reviews by Product block.
 *
 * @package WooCommerce\Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * AllReviews class.
 */
class AllReviews extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'all-reviews';

	/**
	 * Registers the block type with WordPress.
	 */
	public function register_block_type() {
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => 'wc-' . $this->block_name,
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'script'          => 'wc-' . $this->block_name . '-frontend',
			)
		);
	}

	/**
	 * Append frontend scripts when rendering the Product Categories List block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		\Automattic\WooCommerce\Blocks\Assets::register_block_script( 'reviews-frontend' );

		return $content;
	}
}
