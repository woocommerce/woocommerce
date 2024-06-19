<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;
use Customers;
use WC_Unit_Test_Case;

/**
 * Tests for the CustomBlock class.
 */
class CustomBlockTest extends WC_Unit_Test_Case {
	/**
	 * Test that the add_block method does not exist by default on blocks.
	 */
	public function test_add_block_does_not_exist() {
		$template = new CustomBlockTemplate();
		$block    = $template->add_custom_block( [ 'blockName' => 'test-block-name' ] );

		$this->assertFalse( method_exists( $block, 'add_block' ) );
	}

	/**
	 * Test that a custom block inserter method inserts as expected.
	 */
	public function test_add_custom_inner_block() {
		$template = new CustomBlockTemplate();
		$block    = $template->add_custom_block( [ 'blockName' => 'test-block-name' ] );

		$block->add_custom_inner_block( 'a' );
		$block->add_custom_inner_block( 'b' );

		$this->assertSame(
			[
				'test-block-name',
				[
					'_templateBlockId'    => 'test-block-name-1',
					'_templateBlockOrder' => 10000,
				],
				[
					[
						'custom-inner-block',
						[
							'title'               => 'a',
							'_templateBlockId'    => 'custom-inner-block-1',
							'_templateBlockOrder' => 10000,
						],
					],
					[
						'custom-inner-block',
						[
							'title'               => 'b',
							'_templateBlockId'    => 'custom-inner-block-2',
							'_templateBlockOrder' => 10000,
						],
					],
				],
			],
			$block->get_formatted_template(),
			'Failed asserting that the inner block was added'
		);
	}

	/**
	 * Test that a custom block is removed as expected.
	 */
	public function test_remove_custom_inner_block() {
		$template = new CustomBlockTemplate();
		$block    = $template->add_custom_block( [ 'blockName' => 'test-block-name' ] );
		$block->add_custom_inner_block( 'a' );
		$block->add_custom_inner_block( 'b' );

		$template->remove_block( 'custom-inner-block-1' );

		$this->assertSame(
			[
				'test-block-name',
				[
					'_templateBlockId'    => 'test-block-name-1',
					'_templateBlockOrder' => 10000,
				],
				[
					[
						'custom-inner-block',
						[
							'title'               => 'b',
							'_templateBlockId'    => 'custom-inner-block-2',
							'_templateBlockOrder' => 10000,
						],
					],
				],
			],
			$block->get_formatted_template(),
			'Failed asserting that the inner block was removed'
		);
	}
}
