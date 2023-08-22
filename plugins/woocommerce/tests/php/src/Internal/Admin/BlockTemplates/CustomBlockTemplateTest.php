<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the CustomBlockTemplate class.
 */
class CustomBlockTemplateTest extends WC_Unit_Test_Case {
	/**
	 * Test getting the template ID.
	 */
	public function test_get_id() {
		$template = new CustomBlockTemplate();
		$this->assertEquals( $template->get_id(), 'custom-block-template' );
	}

	/**
	 * Test getting the template ID.
	 */
	public function test_get_title() {
		$template = new CustomBlockTemplate();
		$this->assertEquals( $template->get_title(), 'Custom Block Template' );
	}

	/**
	 * Test getting the template ID.
	 */
	public function test_get_description() {
		$template = new CustomBlockTemplate();
		$this->assertEquals( $template->get_description(), 'A custom block template for testing.' );
	}

	/**
	 * Test that the add_block method does not exist by default on templates.
	 */
	public function test_add_block_does_not_exist() {
		$template = new CustomBlockTemplate();
		$this->assertFalse( method_exists( $template, 'add_block' ) );
	}

	/**
	 * Test that a custom block inserter method inserts as expected.
	 */
	public function test_add_custom_block() {
		$template = new CustomBlockTemplate();

		$template->add_custom_block(
			[
				'id'        => 'test-block-name',
				'blockName' => 'test-block-name',
			]
		);

		$block = $template->get_block( 'test-block-name' );
		$this->assertInstanceOf( CustomBlock::class, $block );
	}
}
