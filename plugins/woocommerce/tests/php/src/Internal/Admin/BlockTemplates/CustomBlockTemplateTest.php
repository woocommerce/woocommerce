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
