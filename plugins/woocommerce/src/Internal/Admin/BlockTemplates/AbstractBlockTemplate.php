<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Block template class.
 */
abstract class AbstractBlockTemplate implements BlockTemplateInterface {
	use BlockContainerTrait;

	/**
	 * Get the template ID.
	 */
	abstract public function get_id(): string;

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return '';
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return '';
	}

	/**
	 * Get the template area.
	 */
	public function get_area(): string {
		return 'uncategorized';
	}

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
	 * Caches a block in the template. This is an internal method and should not be called directly
	 * except for from the BlockContainerTrait's add_inner_block() method.
	 *
	 * @param BlockInterface $block The block to cache.
	 *
	 * @throws \ValueError If a block with the specified ID already exists in the template.
	 * @throws \ValueError If the block template that the block belongs to is not this template.
	 *
	 * @ignore
	 */
	public function cache_block( BlockInterface &$block ) {
		$id = $block->get_id();

		if ( isset( $this->block_cache[ $id ] ) ) {
			throw new \ValueError( 'A block with the specified ID already exists in the template.' );
		}

		if ( $block->get_root_template() !== $this ) {
			throw new \ValueError( 'The block template that the block belongs to must be the same as this template.' );
		}

		$this->block_cache[ $id ] = $block;
	}

	/**
	 * Uncaches a block in the template. This is an internal method and should not be called directly
	 * except for from the BlockContainerTrait's remove_block() method.
	 *
	 * @param string $block_id The block ID.
	 *
	 * @ignore
	 */
	public function uncache_block( string $block_id ) {
		if ( isset( $this->block_cache[ $block_id ] ) ) {
			unset( $this->block_cache[ $block_id ] );
		}
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
	public function &get_root_template(): BlockTemplateInterface {
		return $this;
	}

	/**
	 * Get the inner blocks as a formatted template.
	 */
	public function get_formatted_template(): array {
		$inner_blocks = $this->get_inner_blocks_sorted_by_order();

		$inner_blocks_formatted_template = array_map(
			function( BlockInterface $block ) {
				return $block->get_formatted_template();
			},
			$inner_blocks
		);

		return $inner_blocks_formatted_template;
	}

	/**
	 * Get the template as JSON like array.
	 *
	 * @return array The JSON.
	 */
	public function to_json(): array {
		return array(
			'id'             => $this->get_id(),
			'title'          => $this->get_title(),
			'description'    => $this->get_description(),
			'area'           => $this->get_area(),
			'blockTemplates' => $this->get_formatted_template(),
		);
	}
}
