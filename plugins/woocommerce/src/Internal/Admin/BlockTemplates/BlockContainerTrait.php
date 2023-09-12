<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;

/**
 * Trait for block containers.
 */
trait BlockContainerTrait {
	/**
	 * The inner blocks.
	 *
	 * @var BlockInterface[]
	 */
	private $inner_blocks = [];

	// phpcs doesn't take into account exceptions thrown by called methods.
	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Add a block to the block container.
	 *
	 * @param BlockInterface $block The block.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If a block with the specified ID already exists in the template.
	 * @throws \UnexpectedValueException If the block container is not the parent of the block.
	 * @throws \UnexpectedValueException If the block container's root template is not the same as the block's root template.
	 */
	protected function &add_inner_block( BlockInterface $block ): BlockInterface {
		if ( $block->get_parent() !== $this ) {
			throw new \UnexpectedValueException( 'The block container is not the parent of the block.' );
		}

		if ( $block->get_root_template() !== $this->get_root_template() ) {
			throw new \UnexpectedValueException( 'The block container\'s root template is not the same as the block\'s root template.' );
		}

		$is_detached = method_exists( $this, 'is_detached' ) && $this->is_detached();
		if ( $is_detached ) {
			BlockTemplateLogger::get_instance()->warning(
				'Block added to detached container. Block will not be included in the template, since the container will not be included in the template.',
				[
					'block'     => $block,
					'container' => $this,
					'template'  => $this->get_root_template(),
				]
			);
		} else {
			$this->get_root_template()->cache_block( $block );
		}

		$this->inner_blocks[] = &$block;

		$this->do_after_add_block_action( $block );
		$this->do_after_add_specific_block_action( $block );

		return $block;
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Checks if a block is a descendant of the block container.
	 *
	 * @param BlockInterface $block The block.
	 */
	private function is_block_descendant( BlockInterface $block ): bool {
		$parent = $block->get_parent();

		if ( $parent === $this ) {
			return true;
		}

		if ( ! $parent instanceof BlockInterface ) {
			return false;
		}

		return $this->is_block_descendant( $parent );
	}

	/**
	 * Get a block by ID.
	 *
	 * @param string $block_id The block ID.
	 */
	public function get_block( string $block_id ): ?BlockInterface {
		foreach ( $this->inner_blocks as $block ) {
			if ( $block->get_id() === $block_id ) {
				return $block;
			}
		}

		foreach ( $this->inner_blocks as $block ) {
			if ( $block instanceof ContainerInterface ) {
				$block = $block->get_block( $block_id );

				if ( $block ) {
					return $block;
				}
			}
		}

		return null;
	}

	/**
	 * Remove a block from the block container.
	 *
	 * @param string $block_id The block ID.
	 *
	 * @throws \UnexpectedValueException If the block container is not an ancestor of the block.
	 */
	public function remove_block( string $block_id ) {
		$root_template = $this->get_root_template();

		$block = $root_template->get_block( $block_id );

		if ( ! $block ) {
			return;
		}

		if ( ! $this->is_block_descendant( $block ) ) {
			throw new \UnexpectedValueException( 'The block container is not an ancestor of the block.' );
		}

		// If the block is a container, remove all of its blocks.
		if ( $block instanceof ContainerInterface ) {
			$block->remove_blocks();
		}

		$parent = $block->get_parent();
		$parent->remove_inner_block( $block );
	}

	/**
	 * Remove all blocks from the block container.
	 */
	public function remove_blocks() {
		array_map(
			function ( BlockInterface $block ) {
				$this->remove_block( $block->get_id() );
			},
			$this->inner_blocks
		);
	}

	/**
	 * Remove a block from the block container's inner blocks. This is an internal method and should not be called directly
	 * except for from the BlockContainerTrait's remove_block() method.
	 *
	 * @param BlockInterface $block The block.
	 */
	public function remove_inner_block( BlockInterface $block ) {
		// Remove block from root template's cache.
		$root_template = $this->get_root_template();
		$root_template->uncache_block( $block->get_id() );

		$this->inner_blocks = array_filter(
			$this->inner_blocks,
			function ( BlockInterface $inner_block ) use ( $block ) {
				return $inner_block !== $block;
			}
		);

		BlockTemplateLogger::get_instance()->info(
			'Block removed from template.',
			[
				'block'    => $block,
				'template' => $root_template,
			]
		);

		$this->do_after_remove_block_action( $block );
		$this->do_after_remove_specific_block_action( $block );
	}

	/**
	 * Get the inner blocks sorted by order.
	 */
	private function get_inner_blocks_sorted_by_order(): array {
		$sorted_inner_blocks = $this->inner_blocks;

		usort(
			$sorted_inner_blocks,
			function( BlockInterface $a, BlockInterface $b ) {
				return $a->get_order() <=> $b->get_order();
			}
		);

		return $sorted_inner_blocks;
	}

	/**
	 * Get the inner blocks as a formatted template.
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
	 * Do the `woocommerce_block_template_after_add_block` action.
	 * Handle exceptions thrown by the action.
	 *
	 * @param BlockInterface $block The block.
	 */
	private function do_after_add_block_action( BlockInterface $block ) {
		try {
			/**
			 * Action called after a block is added to a block container.
			 *
			 * This action can be used to perform actions after a block is added to the block container,
			 * such as adding a dependent block.
			 *
			 * @param BlockInterface $block The block.
			 *
			 * @since 8.2.0
			 */
			do_action( 'woocommerce_block_template_after_add_block', $block );
		} catch ( \Exception $e ) {
			$this->handle_exception_doing_action(
				'Error after adding block to template.',
				'woocommerce_block_template_after_add_block',
				$block,
				$e
			);
		}
	}

	/**
	 * Do the `woocommerce_block_template_area_{template_area}_after_add_block_{block_id}` action.
	 * Handle exceptions thrown by the action.
	 *
	 * @param BlockInterface $block The block.
	 */
	private function do_after_add_specific_block_action( BlockInterface $block ) {
		try {
			/**
			 * Action called after a specific block is added to a template with a specific area.
			 *
			 * This action can be used to perform actions after a specific block is added to a template with a specific area,
			 * such as adding a dependent block.
			 *
			 * @param BlockInterface $block The block.
			 *
			 * @since 8.2.0
			 */
			do_action( "woocommerce_block_template_area_{$this->get_root_template()->get_area()}_after_add_block_{$block->get_id()}", $block );
		} catch ( \Exception $e ) {
			$this->handle_exception_doing_action(
				'Error after adding block to template.',
				"woocommerce_block_template_area_{$this->get_root_template()->get_area()}_after_add_block_{$block->get_id()}",
				$block,
				$e
			);
		}
	}

	/**
	 * Do the `woocommerce_block_template_after_remove_block` action.
	 * Handle exceptions thrown by the action.
	 *
	 * @param BlockInterface $block The block.
	 */
	private function do_after_remove_block_action( BlockInterface $block ) {
		try {
			/**
			 * Action called after a block is removed from a block container.
			 *
			 * This action can be used to perform actions after a block is removed from the block container,
			 * such as removing a dependent block.
			 *
			 * @param BlockInterface $block The block.
			 *
			 * @since 8.2.0
			 */
			do_action( 'woocommerce_block_template_after_remove_block', $block );
		} catch ( \Exception $e ) {
			$this->handle_exception_doing_action(
				'Error after removing block from template.',
				'woocommerce_block_template_after_remove_block',
				$block,
				$e
			);
		}
	}

	/**
	 * Do the `woocommerce_block_template_area_{template_area}_after_remove_block_{block_id}` action.
	 * Handle exceptions thrown by the action.
	 *
	 * @param BlockInterface $block The block.
	 */
	private function do_after_remove_specific_block_action( BlockInterface $block ) {
		try {
			/**
			 * Action called after a specific block is removed from a template with a specific area.
			 *
			 * This action can be used to perform actions after a specific block is removed from a template with a specific area,
			 * such as removing a dependent block.
			 *
			 * @param BlockInterface $block The block.
			 *
			 * @since 8.2.0
			 */
			do_action( "woocommerce_block_template_area_{$this->get_root_template()->get_area()}_after_remove_block_{$block->get_id()}", $block );
		} catch ( \Exception $e ) {
			$this->handle_exception_doing_action(
				'Error after removing block from template.',
				"woocommerce_block_template_area_{$this->get_root_template()->get_area()}_after_remove_block_{$block->get_id()}",
				$block,
				$e
			);
		}
	}

	/**
	 * Handle an exception thrown by an action.
	 *
	 * @param string         $message    The message.
	 * @param string         $action_tag The action tag.
	 * @param BlockInterface $block      The block.
	 * @param \Exception     $e          The exception.
	 */
	private function handle_exception_doing_action( string $message, string $action_tag, BlockInterface $block, \Exception $e ) {
		BlockTemplateLogger::get_instance()->error(
			$message,
			[
				'exception' => $e,
				'action'    => $action_tag,
				'container' => $this,
				'block'     => $block,
				'template'  => $this->get_root_template(),
			],
		);
	}
}
