<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates;

use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockBasedTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the BlockTemplate class.
 */
class BlockTemplateTest extends WC_Unit_Test_Case {
	/**
	 * Test generating a block ID.
	 */
	public function test_generate_block_id() {
		$template = new BlockBasedTemplate();

		$this->assertSame( 'test-block-id-1', $template->generate_block_id( 'test-block-id' ) );
	}

	/**
	 * Test adding a block.
	 */
	public function test_add_block() {
		$template = new BlockBasedTemplate();

		$block = $template->add_block(
			[
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			]
		);

		$this->assertSame( $block, $template->get_block( 'test-block-id' ) );
	}

	/**
	 * Test adding a block throws an exception if a block with the same ID already exists.
	 */
	public function test_add_block_throws_exception_if_block_with_same_id_already_exists() {
		$template = new BlockBasedTemplate();

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
		$template = new BlockBasedTemplate();

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
		$template = new BlockBasedTemplate();

		$this->assertNull( $template->get_block( 'test-block-id' ) );
	}

	/**
	 * Test getting a block by ID returns a reference to the block.
	 */
	public function test_get_block_returns_reference() {
		$template = new BlockBasedTemplate();

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
	 * Test that the simple array representation of a block template is correct.
	 */
	public function test_get_as_simple_array() {
		$template = new BlockBasedTemplate();

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
						'attr-1' => 'value-1',
						'attr-2' => 'value-2',
					],
				],
				[
					'test-block-name-b',
					[
						'attr-1' => 'value-1',
						'attr-2' => 'value-2',
					],
					[
						[
							'test-block-name-1',
							[
								'attr-3' => 'value-3',
								'attr-4' => 'value-4',
							],
						],
						[
							'test-block-name-2',
							[
								'attr-1' => 'value-1',
								'attr-2' => 'value-2',
							],
						],
						[
							'test-block-name-3',
							[],
						],
					],
				],
				[
					'test-block-name-c',
					[
						'attr-c1' => 'value-c1',
						'attr-c2' => 'value-c2',
					],
				],
			],
			$template->get_as_simple_array(),
			'Failed asserting that the block is converted to a simple array correctly.'
		);
	}

	/**
	 * Test that inserting a block to a parent in the template works.
	 */
	public function test_inserting_block_by_parent_id() {
		$template = new BlockBasedTemplate();

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
						'attr-1' => 'value-1',
						'attr-2' => 'value-2',
					],
					[
						[
							'inserted-block',
							[],
						],
					],
				],
				[
					'test-block-name-b',
					[
						'attr-1' => 'value-1',
						'attr-2' => 'value-2',
					],
					[
						[
							'test-block-name-1',
							[
								'attr-3' => 'value-3',
								'attr-4' => 'value-4',
							],
						],
						[
							'another-inserted-block',
							[],
						],
						[
							'test-block-name-2',
							[
								'attr-1' => 'value-1',
								'attr-2' => 'value-2',
							],
						],
						[
							'test-block-name-3',
							[],
						],
					],
				],
				[
					'test-block-name-c',
					[
						'attr-c1' => 'value-c1',
						'attr-c2' => 'value-c2',
					],
				],
			],
			$template->get_as_simple_array(),
			'Failed asserting that the block is converted to a simple array correctly.'
		);
	}
}
