<?php

namespace Automattic\WooCommerce\Tests\LayoutTemplates;

use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

use WC_Unit_Test_Case;

/**
 * Tests for the LayoutTemplateRegistry class.
 */
class LayoutTemplateRegistryTest extends WC_Unit_Test_Case {
	/**
	 * Layout template registry.
	 *
	 * @var LayoutTemplateRegistry
	 */
	protected $layout_template_registry;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$this->layout_template_registry = new LayoutTemplateRegistry();
	}

	/**
	 * Test registering a layout template.
	 */
	public function test_register() {
		$this->assertFalse( $this->layout_template_registry->is_registered( 'test-layout-template' ) );

		$this->layout_template_registry->register( 'test-layout-template', 'test', TestLayoutTemplate::class );

		$this->assertTrue( $this->layout_template_registry->is_registered( 'test-layout-template' ) );
	}
}
