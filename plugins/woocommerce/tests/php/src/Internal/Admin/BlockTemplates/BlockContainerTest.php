<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockContainer;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the BlockContainer class.
 */
class BlockContainerTest extends WC_Unit_Test_Case {
	/**
	 * Test that adding a block to a block sets the parent and root template correctly
	 * and that the block is added to the root template.
	 */
	public function test_add_block() {
		$template = new BlockTemplate();

		$block = $template->add_block_container(
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
	 * Test that adding a block to a block with a block creator
	 * sets the parent and root template correctly
	 * and that the block is added to the root template.
	 */
	public function test_add_block_with_block_creator() {
		$template = new BlockTemplate();

		$block = $template->add_block_container(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$child_block = $block->add_block(
			[
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			],
			function ( array $config, BlockTemplateInterface &$root_template, BlockContainerInterface &$parent = null ) {
				return new CustomBlock( $config, $root_template, $parent );
			}
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

		$this->assertInstanceOf(
			CustomBlockInterface::class,
			$child_block,
			'Failed asserting that the child block is an instance of CustomBlockInterface.'
		);
	}

	/**
	 * Test that adding a block to a block with a buggy block creator
	 * (one that doesn't properly set the parent)
	 * throws an exception.
	 */
	public function test_add_block_with_buggy_block_creator() {
		$template = new BlockTemplate();

		$block = $template->add_block_container(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$this->expectException( \UnexpectedValueException::class );

		$block->add_block(
			[
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			],
			function ( array $config, BlockTemplateInterface &$root_template, BlockContainerInterface &$parent = null ) {
				return new BuggyCustomBlock( $config, $root_template, $parent );
			}
		);
	}

	/**
	 * Test that adding a block to a block with an invalid block creator
	 * (one that doesn't return a BlockInterface instance)
	 * throws an exception.
	 */
	public function test_add_block_with_invalid_block_creator() {
		$template = new BlockTemplate();

		$block = $template->add_block_container(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$this->expectException( \UnexpectedValueException::class );

		$block->add_block(
			[
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			],
			function () {
				return 23;
			}
		);
	}

	/**
	 * Test that adding nested blocks sets the parent and root template correctly.
	 */
	public function test_nested_add_block() {
		$block_template = new BlockTemplate();

		$block = $block_template->add_block_container(
			[
				'blockName' => 'test-block-name',
			]
		);

		$child_block_1 = $block->add_block_container(
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

		$block = $template->add_block_container(
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

		$block = $template->add_block_container(
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
