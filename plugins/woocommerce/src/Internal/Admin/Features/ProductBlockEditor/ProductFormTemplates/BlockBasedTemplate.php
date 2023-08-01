<?php

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockBasedTemplateInterface;

/**
 * Block template class.
 */
class BlockBasedTemplate implements BlockBasedTemplateInterface {
	use BlockContainerTrait;

	/**
	 * The block cache.
	 *
	 * @var BlockInterface[]
	 */
	private $block_cache = [];

	/**
	 * Get a block by ID.
	 *
	 * @param string $block_id The block ID.
	 */
	public function get_block( string $block_id ): ?BlockInterface {
		return $this->block_cache[ $block_id ] ?? null;
	}

	/**
	 * Add a block to the template. This is an internal method and should not be called directly
	 * except for classes that implement BlockContainerInterface, in their add_block() method.
	 *
	 * @param BlockInterface $block The block to add.
	 *
	 * @throws \ValueError If a block with the specified ID already exists in the template.
	 *
	 * @ignore
	 */
	public function internal_add_block_to_template( BlockInterface &$block ) {
		$id = $block->get_id();

		if ( isset( $this->block_cache[ $id ] ) ) {
			throw new \ValueError( 'A block with the specified ID already exists in the template.' );
		}

		$this->block_cache[ $id ] = $block;
	}

	/**
	 * Generate a block ID based on a base.
	 *
	 * @param string $id_base The base to use when generating an ID.
	 * @return string
	 */
	public function generate_block_id( string $id_base ): string {
		$instance_count = 0;

		do {
			$instance_count++;
			$block_id = $id_base . '-' . $instance_count;
		} while ( isset( $this->block_cache[ $block_id ] ) );

		return $block_id;
	}

	/**
	 * Get the root template.
	 */
	public function &get_root_template(): BlockBasedTemplateInterface {
		return $this;
	}

	/**
	 * Get the parent block container.
	 */
	public function &get_parent(): ?BlockContainerInterface {
		return null;
	}

	/**
	 * Get the template as a formatted template.
	 */
	public function get_as_formatted_template(): array {
		return $this->get_child_blocks_as_simple_array();
	}
}
