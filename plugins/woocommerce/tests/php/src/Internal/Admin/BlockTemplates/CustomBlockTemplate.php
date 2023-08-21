<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlockTemplate;

/**
 * Custom block template class.
 */
class CustomBlockTemplate extends AbstractBlockTemplate {
	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'custom-block-template';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return 'Custom Block Template';
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return 'A custom block template for testing.';
	}

	/**
	 * Get the template area.
	 */
	public function get_area(): string {
		return 'test-area';
	}

	/**
	 * Add a custom block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_custom_block( array $block_config ): BlockInterface {
		$block = new CustomBlock( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}
}
