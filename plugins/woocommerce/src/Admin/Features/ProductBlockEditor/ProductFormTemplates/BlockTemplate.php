<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

/**
 * Block template class.
 */
class BlockTemplate implements BlockContainerInterface {
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
	public function get_block_by_id( string $block_id ): ?Block {
		return $this->block_cache[ $block_id ] ?? null;
	}

	/**
	 * Add a block to the template. This is an internal method and should not be called directly.
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
	public function &get_root_template(): BlockTemplate {
		return $this;
	}

	/**
	 * Get the parent block container.
	 */
	public function &get_parent(): ?BlockContainerInterface {
		return null;
	}

	/**
	 * Get the template as a simple array.
	 */
	public function get_as_simple_array(): array {
		return $this->get_child_blocks_as_simple_array();
	}
}
