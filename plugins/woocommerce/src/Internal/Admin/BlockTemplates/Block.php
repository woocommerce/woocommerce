<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Generic block with container properties to be used in BlockTemplate.
 */
class Block extends AbstractBlock implements BlockContainerInterface {
	use BlockContainerTrait;

	/**
	 * Get the block configuration as a formatted template.
	 *
	 * @return array The block configuration as a formatted template.
	 */
	public function get_formatted_template(): array {
		$arr = [
			$this->get_name(),
			$this->get_attributes(),
		];

		$inner_blocks = $this->get_inner_blocks_sorted_by_order();

		if ( ! empty( $inner_blocks ) ) {
			$arr[] = array_map(
				function( BlockInterface $block ) {
					return $block->get_formatted_template();
				},
				$inner_blocks
			);
		}

		return $arr;
	}

	/**
	 * Add an inner block to this block.
	 *
	 * @param array $block_config The block data.
	 */
	public function &add_block( array $block_config ): BlockInterface {
		$block = new Block( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}
}
