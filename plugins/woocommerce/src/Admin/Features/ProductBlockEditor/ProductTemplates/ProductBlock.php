<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;

class ProductBlock extends Block implements ContainerInterface {

	/**
	 * Adds block to the section block.
	 *
	 * @param array $block_config The block data.
	 */
	public function &add_block(array $block_config): BlockInterface
	{
		$block = new ProductBlock($block_config, $this->get_root_template(), $this);
		return $this->add_inner_block($block);
	}

	/**
	 * Adds block to the section block.
	 *
	 * @param array $conditional_attributes Conditional attributes.
	 * @param array $block_configs The nested block data.
	 */
	public function add_conditional_block(array $conditional_attributes, array ...$block_configs): BlockInterface
	{
		$conditional_id = '';
		if ( isset( $block_configs[0]['id'] ) ) {
			$conditional_id = $block_configs[0]['id'] . '-conditional';
		}
		$conditional_block = $this->add_block([
			'id' => $conditional_id,
			'blockName' => 'woocommerce/conditional',
			'attributes' => $conditional_attributes
		]);
		foreach ( $block_configs as $block_config ) {
			$conditional_block->add_block($block_config);

		}
		return $conditional_block;
	}
}
