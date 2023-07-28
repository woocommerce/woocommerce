<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

interface BlockContainerInterface {
	public function &add_block( array $block_data ): Block;

	public function &get_root_template(): BlockTemplate;

	public function &get_parent(): ?BlockContainerInterface;
}
