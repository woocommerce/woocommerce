<?php
/**
 * Base block template.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

class BlockTemplate implements BlockContainerInterface {
	use BlockContainerTrait;

	private $block_cache = [];

	public function get_block_by_id( string $block_id ): ?Block {
		return $this->block_cache[ $block_id ] ?? null;
	}

	public function _add_block_to_template( Block &$block ) {
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

	public function &get_root_template(): BlockTemplate {
		return $this;
	}

	public function &get_parent(): ?BlockContainerInterface {
		return null;
	}

	public function get_as_simple_array(): array {
		return $this->get_child_blocks_as_simple_array();
	}
}
