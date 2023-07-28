<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

interface BlockContainerInterface {
	public function &add_block( array $block_data ): Block;

	public function &get_root_template(): BlockTemplate;

	public function &get_parent(): ?BlockContainerInterface;

	public function get_child_blocks_as_simple_array(): array;
}
