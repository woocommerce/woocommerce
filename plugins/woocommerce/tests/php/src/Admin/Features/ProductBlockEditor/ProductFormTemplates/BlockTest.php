<?php

namespace Automattic\WooCommerce\Tests\Admin\Features\ProductBlockEditor\ProductFormTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\Block;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockTemplate;

use WC_Unit_Test_Case;

class BlockTest extends WC_Unit_Test_Case {
	public function test_name_is_required() {
		$block_template = new BlockTemplate();

		$this->expectException( \ValueError::class );

		$block = new Block( [], $block_template );
	}

	public function test_id_is_generated_if_not_provided() {
		$block_template = new BlockTemplate();

		$block = new Block(
			[
				'blockName' => 'test-block-name',
			],
			$block_template
		);

		$this->assertSame( 'test-block-name-1', $block->get_id() );
	}

	public function test_parent_from_different_template_throws_exception() {
		$block_template   = new BlockTemplate();
		$block_template_2 = new BlockTemplate();

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

	public function test_add_block() {
		$block_template = new BlockTemplate();

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
}
