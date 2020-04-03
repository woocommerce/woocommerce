<?php
/**
 * Reviews by Product block.
 *
 * @package WooCommerce\Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Assets;

/**
 * ReviewsByProduct class.
 */
class ReviewsByProduct extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'reviews-by-product';

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
	 * Register/enqueue scripts used for this block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	protected function enqueue_scripts( array $attributes = [] ) {
		Assets::register_block_script( 'reviews-frontend' );
	}
}
