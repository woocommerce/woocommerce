<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates;

use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockBasedTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the Block class.
 */
class BlockTest extends WC_Unit_Test_Case {
	/**
	 * Test that the block name is required when creating a block.
	 */
	public function test_name_is_required() {
		$block_template = new BlockBasedTemplate();

		$this->expectException( \ValueError::class );

		new Block( [], $block_template );
	}

	/**
	 * Test that an ID is generated if not provided when creating a block.
	 */
	public function test_id_is_generated_if_not_provided() {
		$block_template = new BlockBasedTemplate();

		$block = new Block(
			[
				'blockName' => 'test-block-name',
			],
			$block_template
		);

		$this->assertSame( 'test-block-name-1', $block->get_id() );
	}

	/**
	 * Test that setting a parent from a different template is prevented.
	 */
	public function test_parent_from_different_template_throws_exception() {
		$block_template   = new BlockBasedTemplate();
		$block_template_2 = new BlockBasedTemplate();

		$parent = new Block(
			[
				'blockName' => 'test-block-parent-name',
			],
			$block_template_2
		);

		$this->expectException( \ValueError::class );

		new Block(
			[
				'blockName' => 'test-block-name',
			],
			$block_template,
			$parent
		);
	}

	/**
	 * Test that adding a block to a block sets the parent and root template correctly
	 * and that the block is added to the root template.
	 */
	public function test_add_block() {
		$block_template = new BlockBasedTemplate();

		$block = $block_template->add_block(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$child_block = $block->add_block(
			[
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			]
		);

		$this->assertSame(
			$child_block->get_root_template(),
			$block->get_root_template(),
			'Failed asserting that the child block has the same root template as the parent block.'
		);

		$this->assertSame(
			$block,
			$child_block->get_parent(),
			'Failed asserting that the child block\'s parent is the block it was added to.'
		);
		$this->assertSame(
			$child_block,
			$block_template->get_block_by_id( 'test-block-id-2' ),
			'Failed asserting that the child block is in the root template.'
		);
	}

	/**
	 * Test that adding nested blocks sets the parent and root template correctly.
	 */
	public function test_nested_add_block() {
		$block_template = new BlockBasedTemplate();

		$block = $block_template->add_block(
			[
				'blockName' => 'test-block-name',
			]
		);

		$child_block_1 = $block->add_block(
			[
				'blockName' => 'test-block-name',
			]
		);

		$child_block_2 = $block->add_block(
			[
				'blockName' => 'test-block-name',
			]
		);

		$grandchild_block = $child_block_1->add_block(
			[
				'blockName' => 'test-block-name',
			]
		);

		$this->assertSame(
			$block_template,
			$grandchild_block->get_root_template(),
			'Failed asserting that the grandchild block has the same root template.'
		);

		$this->assertSame(
			$child_block_1,
			$grandchild_block->get_parent(),
			'Failed asserting that the grandchild block\'s parent is the block it was added to.'
		);

		$this->assertSame(
			$block,
			$grandchild_block->get_parent()->get_parent(),
			'Failed asserting that the grandchild block\'s grandparent is correct.'
		);
	}

	/**
	 * Test that getting the block as a simple array is structured correctly.
	 */
	public function test_get_as_simple_array() {
		$block_template = new BlockBasedTemplate();

		$block = $block_template->add_block(
			[
				'id'         => 'test-block-id',
				'blockName'  => 'test-block-name',
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$block->add_block(
			[
				'id'         => 'test-block-id-2',
				'blockName'  => 'test-block-name-2',
				'attributes' => [
					'attr-3' => 'value-3',
					'attr-4' => 'value-4',
				],
			]
		);

		$block->add_block(
			[
				'id'        => 'test-block-id-3',
				'blockName' => 'test-block-name-3',
			]
		);

		$simple_array = $block->get_as_simple_array();

		$this->assertSame(
			[
				'test-block-name',
				[
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
				[
					[
						'test-block-name-2',
						[
							'attr-3' => 'value-3',
							'attr-4' => 'value-4',
						],
					],
					[
						'test-block-name-3',
						[],
					],
				],
			],
			$simple_array,
			'Failed asserting that the block is converted to a simple array correctly.'
		);
	}

	/**
	 * Test that getting the child blocks as a sorted simple array is ordered correctly.
	 */
	public function test_get_child_blocks_as_sorted_simple_array() {
		$block_template = new BlockBasedTemplate();

		$block = $block_template->add_block(
			[
				'blockName' => 'test-block-name',
			]
		);

		$block->add_block(
			[
				'blockName' => 'five',
				'order'     => 5,
			]
		);

		$block->add_block(
			[
				'blockName' => 'three',
				'order'     => 3,
			]
		);

		$block->add_block(
			[
				'blockName' => 'one',
				'order'     => 1,
			]
		);

		$block->add_block(
			[
				'blockName' => 'four',
				'order'     => 4,
			]
		);

		$block->add_block(
			[
				'blockName' => 'two',
				'order'     => 2,
			]
		);

		$this->assertSame(
			[
				[
					'one',
					[],
				],
				[
					'two',
					[],
				],
				[
					'three',
					[],
				],
				[
					'four',
					[],
				],
				[
					'five',
					[],
				],
			],
			$block->get_child_blocks_as_simple_array(),
			'Failed asserting that the child blocks are sorted by order.'
		);
	}
}
