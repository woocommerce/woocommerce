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

		new Block( array(), $template );
	}

	/**
	 * Test that an ID is generated if not provided when creating a block.
	 */
	public function test_id_is_generated_if_not_provided() {
		$template = new BlockTemplate();

		$block = new Block(
			array(
				'blockName' => 'test-block-name',
			),
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
			array(
				'blockName' => 'test-block-parent-name',
			),
			$template_2
		);

		$this->expectException( \ValueError::class );

		new Block(
			array(
				'blockName' => 'test-block-name',
			),
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
			array(
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			)
		);

		$child_block = $block->add_block(
			array(
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			)
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
	 * Test that removing a block from a block detaches it
	 * and that the block is removed from the root template.
	 */
	public function test_remove_block() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			)
		);

		$child_block = $block->add_block(
			array(
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			)
		);

		$block->remove_block( 'test-block-id-2' );

		$this->assertNull(
			$template->get_block( 'test-block-id-2' ),
			'Failed asserting that the child block was removed from the root template.'
		);

		$this->assertNull(
			$block->get_block( 'test-block-id-2' ),
			'Failed asserting that the child block was removed from the parent.'
		);

		$this->assertTrue(
			$child_block->is_detached(),
			'Failed asserting that the child block is detached from its parent and root template.'
		);
	}

	/**
	 * Test that removing a block from a block detaches it
	 * and that the block is removed from the root template, as well as any descendants.
	 */
	public function test_remove_nested_block() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			)
		);

		$child_block = $block->add_block(
			array(
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			)
		);

		$template->remove_block( 'test-block-id-2' );

		$this->assertNull(
			$template->get_block( 'test-block-id-2' ),
			'Failed asserting that the nested descendent block was removed from the root template.'
		);

		$this->assertNull(
			$block->get_block( 'test-block-id-2' ),
			'Failed asserting that the nested descendent block was removed from the parent.'
		);

		$this->assertTrue(
			$child_block->is_detached(),
			'Failed asserting that the nested descendent block is detached from its parent and root template.'
		);
	}

	/**
	 * Test that removing a block from a block detaches it
	 * and that the block is removed from the root template, as well as any descendants.
	 */
	public function test_remove_block_and_descendants() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			)
		);

		$child_block = $block->add_block(
			array(
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			)
		);

		$template->remove_block( 'test-block-id' );

		$this->assertNull(
			$template->get_block( 'test-block-id' ),
			'Failed asserting that the child block was removed from the root template.'
		);

		$this->assertNull(
			$template->get_block( 'test-block-id-2' ),
			'Failed asserting that the nested descendent block was removed from the root template.'
		);

		$this->assertNull(
			$block->get_block( 'test-block-id-2' ),
			'Failed asserting that the child block was removed from the parent.'
		);

		$this->assertTrue(
			$block->is_detached(),
			'Failed asserting that the block is detached from its parent and root template.'
		);

		$this->assertTrue(
			$child_block->is_detached(),
			'Failed asserting that the child block is detached from its parent and root template.'
		);
	}

	/**
	 * Test that removing a block by calling remove on it detaches it.
	 */
	public function test_remove_block_self() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			)
		);

		$block->remove();

		$this->assertTrue(
			$block->is_detached(),
			'Failed asserting that the block is detached from its parent and root template.'
		);
	}

	/**
	 * Test that adding nested blocks sets the parent and root template correctly.
	 */
	public function test_nested_add_block() {
		$block_template = new BlockTemplate();

		$block = $block_template->add_block(
			array(
				'blockName' => 'test-block-name',
			)
		);

		$child_block_1 = $block->add_block(
			array(
				'blockName' => 'test-block-name',
			)
		);

		$block->add_block(
			array(
				'blockName' => 'test-block-name',
			)
		);

		$grandchild_block = $child_block_1->add_block(
			array(
				'blockName' => 'test-block-name',
			)
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
	 * Test that a block added to a detached block is detached.
	 */
	public function test_block_added_to_detached_block_is_detached() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'id'        => 'test-block-id',
				'blockName' => 'test-block-name',
			)
		);

		$template->remove_block( 'test-block-id' );

		$child_block = $block->add_block(
			array(
				'id'        => 'test-block-id-2',
				'blockName' => 'test-block-name-2',
			)
		);

		$this->assertNull(
			$template->get_block( 'test-block-id' ),
			'Failed asserting that the block was removed from the root template.'
		);

		$this->assertNull(
			$template->get_block( 'test-block-id-2' ),
			'Failed asserting that the nested block is not in the root template.'
		);

		$this->assertNotNull(
			$block->get_block( 'test-block-id-2' ),
			'Failed asserting that the nested block is in the parent.'
		);

		$this->assertTrue(
			$child_block->is_detached(),
			'Failed asserting that the nested descendent block is detached from its parent and root template.'
		);
	}

	/**
	 * Test that hide conditions can be passed in when creating a block.
	 */
	public function test_hide_conditions_in_constructor() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'blockName'      => 'test-block-name',
				'hideConditions' => array(
					array(
						'expression' => 'foo === bar',
					),
				),
			)
		);

		$this->assertSame(
			array(
				'k0' => array(
					'expression' => 'foo === bar',
				),
			),
			$block->get_hide_conditions(),
			'Failed asserting that the hide conditions are set correctly in the constructor.'
		);
	}

	/**
	 * Test that disable conditions can be passed in when creating a block.
	 */
	public function test_disable_conditions_in_constructor() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'blockName'         => 'test-block-name',
				'disableConditions' => array(
					array(
						'expression' => 'foo === bar',
					),
				),
			)
		);

		$this->assertSame(
			array(
				'k0' => array(
					'expression' => 'foo === bar',
				),
			),
			$block->get_disable_conditions(),
			'Failed asserting that the disable conditions are set correctly in the constructor.'
		);
	}

	/**
	 * Test that hide conditions can be added to a block.
	 */
	public function test_add_hide_condition() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'blockName' => 'test-block-name',
			)
		);

		$condition_1_key = $block->add_hide_condition( 'editedProduct.manage_stock === true' );

		$condition_2_key = $block->add_hide_condition( 'true' );

		$condition_3_key = $block->add_hide_condition( 'foo > 10' );

		$block->remove_hide_condition( $condition_2_key );

		$this->assertSame(
			array(
				$condition_1_key => array(
					'expression' => 'editedProduct.manage_stock === true',
				),
				$condition_3_key => array(
					'expression' => 'foo > 10',
				),
			),
			$block->get_hide_conditions(),
			'Failed asserting that the hide conditions are added correctly.'
		);
	}

	/**
	 * Test that hide conditions can be added to a block.
	 */
	public function test_add_disable_condition() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'blockName' => 'test-block-name',
			)
		);

		$condition_1_key = $block->add_disable_condition( 'editedProduct.manage_stock === true' );

		$condition_2_key = $block->add_disable_condition( 'true' );

		$condition_3_key = $block->add_disable_condition( 'foo > 10' );

		$block->remove_disable_condition( $condition_2_key );

		$this->assertSame(
			array(
				$condition_1_key => array(
					'expression' => 'editedProduct.manage_stock === true',
				),
				$condition_3_key => array(
					'expression' => 'foo > 10',
				),
			),
			$block->get_disable_conditions(),
			'Failed asserting that the hide conditions are added correctly.'
		);
	}

	/**
	 * Test that getting the block as a formatted template is structured correctly.
	 */
	public function test_get_formatted_template() {
		$template = new BlockTemplate();

		$block = $template->add_block(
			array(
				'id'         => 'test-block-id',
				'blockName'  => 'test-block-name',
				'attributes' => array(
					'attr-1' => 'value-1',
					'attr-2' => 'value-2',
				),
			)
		);

		$block->add_hide_condition( 'foo === bar' );

		$block->add_disable_condition( 'test > 100' );

		$block->add_block(
			array(
				'id'         => 'test-block-id-2',
				'blockName'  => 'test-block-name-2',
				'attributes' => array(
					'attr-3' => 'value-3',
					'attr-4' => 'value-4',
				),
			)
		);

		$block->add_block(
			array(
				'id'        => 'test-block-id-3',
				'blockName' => 'test-block-name-3',
			)
		);

		$formatted_template = $block->get_formatted_template();

		$this->assertSame(
			array(
				'test-block-name',
				array(
					'attr-1'                          => 'value-1',
					'attr-2'                          => 'value-2',
					'_templateBlockId'                => 'test-block-id',
					'_templateBlockOrder'             => 10000,
					'_templateBlockHideConditions'    => array(
						array(
							'expression' => 'foo === bar',
						),
					),
					'_templateBlockDisableConditions' => array(
						array(
							'expression' => 'test > 100',
						),
					),
				),
				array(
					array(
						'test-block-name-2',
						array(
							'attr-3'              => 'value-3',
							'attr-4'              => 'value-4',
							'_templateBlockId'    => 'test-block-id-2',
							'_templateBlockOrder' => 10000,
						),
					),
					array(
						'test-block-name-3',
						array(
							'_templateBlockId'    => 'test-block-id-3',
							'_templateBlockOrder' => 10000,
						),
					),
				),
			),
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
			array(
				'blockName' => 'test-block-name',
			)
		);

		$block->add_block(
			array(
				'blockName' => 'five',
				'order'     => 5,
			)
		);

		$block->add_block(
			array(
				'blockName' => 'three',
				'order'     => 3,
			)
		);

		$block->add_block(
			array(
				'blockName' => 'one',
				'order'     => 1,
			)
		);

		$block->add_block(
			array(
				'blockName' => 'four',
				'order'     => 4,
			)
		);

		$block->add_block(
			array(
				'blockName' => 'two',
				'order'     => 2,
			)
		);

		$this->assertSame(
			array(
				'test-block-name',
				array(
					'_templateBlockId'    => 'test-block-name-1',
					'_templateBlockOrder' => 10000,
				),
				array(
					array(
						'one',
						array(
							'_templateBlockId'    => 'one-1',
							'_templateBlockOrder' => 1,
						),
					),
					array(
						'two',
						array(
							'_templateBlockId'    => 'two-1',
							'_templateBlockOrder' => 2,
						),
					),
					array(
						'three',
						array(
							'_templateBlockId'    => 'three-1',
							'_templateBlockOrder' => 3,
						),
					),
					array(
						'four',
						array(
							'_templateBlockId'    => 'four-1',
							'_templateBlockOrder' => 4,
						),
					),
					array(
						'five',
						array(
							'_templateBlockId'    => 'five-1',
							'_templateBlockOrder' => 5,
						),
					),
				),
			),
			$block->get_formatted_template(),
			'Failed asserting that the inner blocks are sorted by order.'
		);
	}
	/**
	 * Test for set_attribute method.
	 */
	public function test_set_attribute() {
		$template = new BlockTemplate();

		$block = new Block(
			array(
				'blockName' => 'test-block-name',
			),
			$template
		);

		$block->set_attribute( 'test-attr', 'test-value' );

		$this->assertSame( 'test-value', $block->get_attributes()['test-attr'] );
	}
}
