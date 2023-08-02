<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockContainer;
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

		$parent = new BlockContainer(
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

		$formatted_template = $block->get_formatted_template();

		$this->assertSame(
			[
				'test-block-name',
				[
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			],
			$formatted_template,
			'Failed asserting that the block is converted to a formatted template correctly.'
		);
	}
}
