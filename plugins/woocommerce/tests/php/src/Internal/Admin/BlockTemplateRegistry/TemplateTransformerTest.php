<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\TemplateTransformer;
use Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates\CustomBlockTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the BlockTemplatesControllerTest class.
 */
class TemplateTransformerTest extends WC_Unit_Test_Case {
    /**
	 * Template transformer instance.
	 *
	 * @var TemplateTransformer
	 */
	protected $template_transformer;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
        parent::setUp();
		$this->template_transformer = new TemplateTransformer();
	}

	/**
	 * Test transforming a template returns a valid WP_Block_Template.
	 */
	public function test_transform() {
        $custom_block_template = new CustomBlockTemplate();
        $wp_block_template     = $this->template_transformer->transform( $custom_block_template );
        $this->assertInstanceOf( \WP_Block_Template::class, $wp_block_template );
        $this->assertEquals( 'custom-block-template', $wp_block_template->id );
        $this->assertEquals( 'Custom Block Template', $wp_block_template->title );
        $this->assertEquals( 'A custom block template for testing.', $wp_block_template->description );
        $this->assertEquals( 'test-area', $wp_block_template->area );
	}
}
