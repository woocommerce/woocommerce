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

	/**
	 * Test registering a layout template with an existing ID.
	 */
	public function test_register_duplicate_id() {
		$this->expectException( \ValueError::class );
		$this->layout_template_registry->register( 'test-layout-template', 'test', TestLayoutTemplate::class );
		$this->layout_template_registry->register( 'test-layout-template', 'test', TestLayoutTemplate::class );
	}

	/**
	 * Test registering a layout template with an empty area.
	 */
	public function test_register_empty_area() {
		$this->expectException( \ValueError::class );
		$this->layout_template_registry->register( 'test-layout-template', '', TestLayoutTemplate::class );
	}

	/**
	 * Test registering a layout template with a non-existing class.
	 */
	public function test_register_non_existing_class() {
		$this->expectException( \ValueError::class );
		$this->layout_template_registry->register( 'test-layout-template', 'test', 'NonExistingClass' );
	}

	/**
	 * Test registering a layout template with a class that does not implement the BlockTemplateInterface.
	 */
	public function test_register_non_block_template_class() {
		$this->expectException( \ValueError::class );
		$this->layout_template_registry->register( 'test-layout-template', 'test', \stdClass::class );
	}
}
