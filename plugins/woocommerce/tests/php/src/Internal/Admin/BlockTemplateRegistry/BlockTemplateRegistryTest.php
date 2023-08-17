<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplateRegistry;

use WC_Unit_Test_Case;

/**
 * Tests for the BlockTemplateRegistryTest class.
 */
class BlockTemplateRegistryTest extends WC_Unit_Test_Case {
	/**
	 * Block template registry.
	 *
	 * @var BlockTemplateRegistry
	 */
	protected $block_template_registry;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$this->block_template_registry = new BlockTemplateRegistry();
		$this->block_template_registry->register( new CustomBlockTemplate() );
		$this->block_template_registry->register( new BlockTemplate() );
	}

	/**
	 * Test getting all registered templates.
	 */
	public function test_get_all_registered() {
		$registered_templates = $this->block_template_registry->get_all_registered();
		$this->assertCount( 2, $registered_templates );
		$this->assertInstanceOf( CustomBlockTemplate::class, $registered_templates['custom-block-template'] );
		$this->assertInstanceOf( BlockTemplate::class, $registered_templates['woocommerce-block-template'] );
	}

	/**
	 * Test registering a template with an existing ID.
	 */
	public function test_register_duplicate_id() {
		$this->expectException( \ValueError::class );
		$registered_templates = $this->block_template_registry->register( new BlockTemplate() );
	}

	/**
	 * Test getting a single template by ID.
	 */
	public function test_get_registered() {
		$custom_block_template = $this->block_template_registry->get_registered( 'custom-block-template' );
		$this->assertInstanceOf( CustomBlockTemplate::class, $custom_block_template );
	}
}
