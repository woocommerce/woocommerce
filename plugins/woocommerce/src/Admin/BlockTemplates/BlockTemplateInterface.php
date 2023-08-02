<?php

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Interface for block-based template.
 */
interface BlockTemplateInterface extends ContainerInterface {
	/**
	 * Get a block by ID.
	 *
	 * @param string $block_id The block ID.
	 */
	public function get_block( string $block_id ): ?BlockInterface;

	/**
	 * Generate a block ID based on a base.
	 *
	 * @param string $id_base The base to use when generating an ID.
	 * @return string
	 */
	public function generate_block_id( string $id_base ): string;
}
