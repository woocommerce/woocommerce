<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the Block class.
 */
class BlockTest extends WC_Unit_Test_Case {
	/**
	 * Test that the block name is required when creating a block.
	 */
	public function test_name_is_required() {
		$template = new BlockTemplate();

		$this->expectException( \ValueError::class );

		new Block( [], $template );
	}

	/**
	 * Test that an ID is generated if not provided when creating a block.
	 */
	public function test_id_is_generated_if_not_provided() {
		$template = new BlockTemplate();

		$block = new Block(
			[
				'blockName' => 'test-block-name',
			],
			$template
		);

		$this->assertSame( 'test-block-name-1', $block->get_id() );
	}

	/**
	 * Test that setting a parent from a different template is prevented.
	 */
	public function test_parent_from_different_template_throws_exception() {
		$template   = new BlockTemplate();
		$template_2 = new BlockTemplate();

		$parent = new Block(
			[
				'blockName' => 'test-block-parent-name',
			],
			$template_2
		);

		$this->expectException( \ValueError::class );

		new Block(
			[
				'blockName' => 'test-block-name',
			],
			$template,
			$parent
		);
	}

	/**
	 * Test that adding a block to a block sets the parent and root template correctly
	 * and that the block is added to the root template.
	 */
	public function test_add_block() {
		$template = new BlockTemplate();

		$block = $template->add_block(
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
			$template->get_block( 'test-block-id-2' ),
			'Failed asserting that the child block is in the root template.'
		);
	}

	/**
	 * Test that adding nested blocks sets the parent and root template correctly.
	 */
	public function test_nested_add_block() {
		$block_template = new BlockTemplate();

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

		$block->add_block(
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

		$grandchild_parent = $grandchild_block->get_parent();

		$this->assertSame(
			$child_block_1,
			$grandchild_parent,
			'Failed asserting that the grandchild block\'s parent is the block it was added to.'
		);

		$this->assertInstanceOf(
			BlockContainerInterface::class,
			$grandchild_parent,
			'Failed asserting that the grandchild block\'s parent is a BlockContainerInterface instance.'
		);

		$this->assertSame(
			$block,
			$grandchild_parent->get_parent(),
			'Failed asserting that the grandchild block\'s grandparent is correct.'
		);
	}

	/**
	 * Test that getting the block as a formatted template is structured correctly.
	 */
	public function test_get_formatted_template() {
		$template = new BlockTemplate();

		$block = $template->add_block(
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

		$formatted_template = $block->get_formatted_template();

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
			$formatted_template,
			'Failed asserting that the block is converted to a formatted template correctly.'
		);
	}

	/**
	 * Test that getting the inner blocks as a sorted formatted template is ordered correctly.
	 */
	public function test_get_formatted_template_with_sorting() {
		$template = new BlockTemplate();

		$block = $template->add_block(
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
				'test-block-name',
				[],
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
			],
			$block->get_formatted_template(),
			'Failed asserting that the inner blocks are sorted by order.'
		);
	}
}
