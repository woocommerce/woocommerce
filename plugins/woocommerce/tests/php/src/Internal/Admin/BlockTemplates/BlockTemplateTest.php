<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the BlockTemplate class.
 */
class BlockTemplateTest extends WC_Unit_Test_Case {
	/**
	 * Test generating a block ID.
	 */
	public function test_generate_block_id() {
		$template = new BlockTemplate();

		$this->assertSame( 'test-block-id-1', $template->generate_block_id( 'test-block-id' ) );
	}

	/**
	 * Test adding a block.
	 */
	public function test_add_block() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$this->assertSame( $block, $template->get_block( 'test-block-id' ) );
	}

	/**
	 * Test the after_add_block hooks fires.
	 */
	public function test_after_add_block_hooks() {
		$template = new BlockTemplate();

		$hook_called = false;

		$after_add_block_hook = function( BlockInterface $block ) use ( &$hook_called ) {
			$hook_called = true;

			if ( 'test-block-id' === $block->get_id() ) {
				$hook_called = true;
			}

			$root_template = $block->get_root_template();

			if ( $root_template->get_block( 'test-block-id-2' ) ) {
				// The block was already added, so just return.
				// This short-circuiting done because this hook will be called two times:
				// 1. When the `test-block-id` block is added to the root template.
				// 2. When the `test-block-id-2` block is added to the template in this hook.
				// Without this short-circuiting, the second time `add_block` is called in this
				// hook would throw an exception, which is handled by the API (an error gets logged).
				return;
			}

			$root_template->add_block(
				[
					'id'        => 'test-block-id-2',
					'blockName' => 'test-block-name-2',
				]
			);
		};

		try {
			add_action( 'woocommerce_block_template_after_add_block', $after_add_block_hook );

			$specific_hook_called = false;

			$specific_after_add_block_hook = function( BlockInterface $block ) use ( &$specific_hook_called ) {
				if ( 'test-block-id' === $block->get_id() ) {
					$specific_hook_called = true;
				}
			};

			add_action( 'woocommerce_block_template_area_uncategorized_after_add_block_test-block-id', $specific_after_add_block_hook );

			$template->add_block(
				[
					'id'        => 'test-block-id',
					'blockName' => 'test-block-name',
				]
			);

			$this->assertTrue(
				$hook_called,
				'Failed asserting that that the hook was called.'
			);

			$this->assertTrue(
				$specific_hook_called,
				'Failed asserting that that the specific hook was called.'
			);

			$this->assertNotNull(
				$template->get_block( 'test-block-id-2' ),
				'Failed asserting that the block was added to the template from the hook.'
			);
		} finally {
			remove_action( 'woocommerce_block_template_after_add_block', $after_add_block_hook );
			remove_action( 'woocommerce_block_template_area_uncategorized_after_add_block_test-block-id', $specific_after_add_block_hook );
		}
	}

	/**
	 * Test the after_remove_block hooks fires.
	 */
	public function test_after_remove_block_hooks() {
		$template = new BlockTemplate();

		$hook_called = false;

		$after_remove_block_hook = function( BlockInterface $block ) use ( &$hook_called ) {
			$hook_called = true;

			if ( 'test-block-id' === $block->get_id() ) {
				$hook_called = true;
			}

			$root_template = $block->get_root_template();
		};

		$specific_hook_called = false;

		$specific_after_remove_block_hook = function( BlockInterface $block ) use ( &$specific_hook_called ) {
			if ( 'test-block-id' === $block->get_id() ) {
				$specific_hook_called = true;
			}
		};

		try {
			add_action( 'woocommerce_block_template_after_remove_block', $after_remove_block_hook );
			add_action( 'woocommerce_block_template_area_uncategorized_after_remove_block_test-block-id', $specific_after_remove_block_hook );

			$block = $template->add_block(
				[
					'id'        => 'test-block-id',
					'blockName' => 'test-block-name',
				]
			);

			$block->remove();

			$this->assertTrue(
				$hook_called,
				'Failed asserting that that the hook was called.'
			);

			$this->assertTrue(
				$specific_hook_called,
				'Failed asserting that that the specific hook was called.'
			);

			$this->assertTrue(
				$block->is_detached(),
				'Failed asserting that the block was added to the template from the hook.'
			);
		} finally {
			remove_action( 'woocommerce_block_template_after_remove_block', $after_remove_block_hook );
			remove_action( 'woocommerce_block_template_area_uncategorized_after_remove_block_test-block-id', $specific_after_remove_block_hook );
		}
	}

	/**
	 * Test adding a block throws an exception if a block with the same ID already exists.
	 */
	public function test_add_block_throws_exception_if_block_with_same_id_already_exists() {
		$template = new BlockTemplate();

		$template->add_block(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$this->expectException( \ValueError::class );

		$template->add_block(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);
	}

	/**
	 * Test adding a block generates an ID if one is not provided.
	 */
	public function test_add_block_generates_id_if_not_provided() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			[
				'blockName' => 'test-block-name',
			]
		);

		$this->assertSame( 'test-block-name-1', $block->get_id() );
	}

	/**
	 * Test getting a block by ID returns null if the block does not exist.
	 */
	public function test_get_block_returns_null_if_block_does_not_exist() {
		$template = new BlockTemplate();

		$this->assertNull( $template->get_block( 'test-block-id' ) );
	}

	/**
	 * Test getting a block by ID returns a reference to the block.
	 */
	public function test_get_block_returns_reference() {
		$template = new BlockTemplate();

		$template->add_block(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$block = $template->get_block( 'test-block-id' );

		$block->set_order( 23 );

		$this->assertSame( 23, $template->get_block( 'test-block-id' )->get_order() );
	}

	/**
	 * Test that the formatted template representation of a block template is correct.
	 */
	public function test_get_formatted_template() {
		$template = new BlockTemplate();

		$template->add_block(
			[
				'blockName'  => 'test-block-name-c',
				'order'      => 100,
				'attributes' => [
					'attr-c1' => 'value-c1',
					'attr-c2' => 'value-c2',
				],
			]
		);

		$block_b = $template->add_block(
			[
				'blockName'  => 'test-block-name-b',
				'order'      => 50,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$template->add_block(
			[
				'blockName'  => 'test-block-name-a',
				'order'      => 10,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName'  => 'test-block-name-2',
				'order'      => 20,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName'  => 'test-block-name-1',
				'order'      => 10,
				'attributes' => [
					'attr-3' => 'value-3',
					'attr-4' => 'value-4',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName' => 'test-block-name-3',
				'order'     => 30,
			]
		);

		$this->assertSame(
			[
				[
					'test-block-name-a',
					[
						'attr-1'              => 'value-1',
						'attr-2'              => 'value-2',
						'_templateBlockId'    => 'test-block-name-a-1',
						'_templateBlockOrder' => 10,
					],
				],
				[
					'test-block-name-b',
					[
						'attr-1'              => 'value-1',
						'attr-2'              => 'value-2',
						'_templateBlockId'    => 'test-block-name-b-1',
						'_templateBlockOrder' => 50,
					],
					[
						[
							'test-block-name-1',
							[
								'attr-3'              => 'value-3',
								'attr-4'              => 'value-4',
								'_templateBlockId'    => 'test-block-name-1-1',
								'_templateBlockOrder' => 10,
							],
						],
						[
							'test-block-name-2',
							[
								'attr-1'              => 'value-1',
								'attr-2'              => 'value-2',
								'_templateBlockId'    => 'test-block-name-2-1',
								'_templateBlockOrder' => 20,
							],
						],
						[
							'test-block-name-3',
							[
								'_templateBlockId'    => 'test-block-name-3-1',
								'_templateBlockOrder' => 30,
							],
						],
					],
				],
				[
					'test-block-name-c',
					[
						'attr-c1'             => 'value-c1',
						'attr-c2'             => 'value-c2',
						'_templateBlockId'    => 'test-block-name-c-1',
						'_templateBlockOrder' => 100,
					],
				],
			],
			$template->get_formatted_template(),
			'Failed asserting that the block is converted to a simple array correctly.'
		);
	}

	/**
	 * Test that inserting a block to a parent in the template works.
	 */
	public function test_inserting_block_by_parent_id() {
		$template = new BlockTemplate();

		$template->add_block(
			[
				'blockName'  => 'test-block-name-c',
				'order'      => 100,
				'attributes' => [
					'attr-c1' => 'value-c1',
					'attr-c2' => 'value-c2',
				],
			]
		);

		$block_b = $template->add_block(
			[
				'id'         => 'b',
				'blockName'  => 'test-block-name-b',
				'order'      => 50,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$template->add_block(
			[
				'id'         => 'a',
				'blockName'  => 'test-block-name-a',
				'order'      => 10,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName'  => 'test-block-name-2',
				'order'      => 20,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName'  => 'test-block-name-1',
				'order'      => 10,
				'attributes' => [
					'attr-3' => 'value-3',
					'attr-4' => 'value-4',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName' => 'test-block-name-3',
				'order'     => 30,
			]
		);

		$block_to_insert_in = $template->get_block( 'a' );

		$block_to_insert_in->add_block(
			[
				'blockName' => 'inserted-block',
			]
		);

		$another_block_to_insert_in = $template->get_block( 'b' );

		$another_block_to_insert_in->add_block(
			[
				'blockName' => 'another-inserted-block',
				'order'     => 15,
			]
		);

		$this->assertSame(
			[
				[
					'test-block-name-a',
					[
						'attr-1'              => 'value-1',
						'attr-2'              => 'value-2',
						'_templateBlockId'    => 'a',
						'_templateBlockOrder' => 10,
					],
					[
						[
							'inserted-block',
							[
								'_templateBlockId'    => 'inserted-block-1',
								'_templateBlockOrder' => 10000,
							],
						],
					],
				],
				[
					'test-block-name-b',
					[
						'attr-1'              => 'value-1',
						'attr-2'              => 'value-2',
						'_templateBlockId'    => 'b',
						'_templateBlockOrder' => 50,
					],
					[
						[
							'test-block-name-1',
							[
								'attr-3'              => 'value-3',
								'attr-4'              => 'value-4',
								'_templateBlockId'    => 'test-block-name-1-1',
								'_templateBlockOrder' => 10,
							],
						],
						[
							'another-inserted-block',
							[
								'_templateBlockId'    => 'another-inserted-block-1',
								'_templateBlockOrder' => 15,
							],
						],
						[
							'test-block-name-2',
							[
								'attr-1'              => 'value-1',
								'attr-2'              => 'value-2',
								'_templateBlockId'    => 'test-block-name-2-1',
								'_templateBlockOrder' => 20,
							],
						],
						[
							'test-block-name-3',
							[
								'_templateBlockId'    => 'test-block-name-3-1',
								'_templateBlockOrder' => 30,
							],
						],
					],
				],
				[
					'test-block-name-c',
					[
						'attr-c1'             => 'value-c1',
						'attr-c2'             => 'value-c2',
						'_templateBlockId'    => 'test-block-name-c-1',
						'_templateBlockOrder' => 100,
					],
				],
			],
			$template->get_formatted_template(),
			'Failed asserting that the template is converted to a formatted template correctly.'
		);
	}

	/**
	 * Test that removing a block in the template works.
	 */
	public function test_removing_blocks() {
		$template = new BlockTemplate();

		$template->add_block(
			[
				'blockName'  => 'test-block-name-c',
				'order'      => 100,
				'attributes' => [
					'attr-c1' => 'value-c1',
					'attr-c2' => 'value-c2',
				],
			]
		);

		$block_b = $template->add_block(
			[
				'id'         => 'b',
				'blockName'  => 'test-block-name-b',
				'order'      => 50,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$template->add_block(
			[
				'id'         => 'a',
				'blockName'  => 'test-block-name-a',
				'order'      => 10,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName'  => 'test-block-name-2',
				'order'      => 20,
				'attributes' => [
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName'  => 'test-block-name-1',
				'order'      => 10,
				'attributes' => [
					'attr-3' => 'value-3',
					'attr-4' => 'value-4',
				],
			]
		);

		$block_b->add_block(
			[
				'blockName' => 'test-block-name-3',
				'order'     => 30,
			]
		);

		$block_to_insert_in = $template->get_block( 'a' );

		$block_to_insert_in->add_block(
			[
				'blockName' => 'inserted-block',
			]
		);

		$template->remove_block( 'b' );

		$this->assertSame(
			[
				[
					'test-block-name-a',
					[
						'attr-1'              => 'value-1',
						'attr-2'              => 'value-2',
						'_templateBlockId'    => 'a',
						'_templateBlockOrder' => 10,
					],
					[
						[
							'inserted-block',
							[
								'_templateBlockId'    => 'inserted-block-1',
								'_templateBlockOrder' => 10000,
							],
						],
					],
				],
				[
					'test-block-name-c',
					[
						'attr-c1'             => 'value-c1',
						'attr-c2'             => 'value-c2',
						'_templateBlockId'    => 'test-block-name-c-1',
						'_templateBlockOrder' => 100,
					],
				],
			],
			$template->get_formatted_template(),
			'Failed asserting that the template is converted to a formatted template correctly.'
		);
	}
}
