<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

trait BlockContainerTrait {
	private $child_blocks = [];

	public function &add_block( array $block_data ): Block {
		$root_template = $this->get_root_template();

		$block = new Block( $block_data, $root_template, $this );
		$root_template->_add_block_to_template( $block );
		$this->child_blocks[] = &$block;
		return $block;
	}
}
