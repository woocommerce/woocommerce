<?php

namespace Automattic\WooCommerce\Tests\LayoutTemplates;

use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\SimpleProductTemplate;
use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\ProductVariationTemplate;

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
	 * Layout templates to register.
	 *
	 * @var array
	 */
	protected $layout_templates_to_register;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$this->layout_template_registry = new LayoutTemplateRegistry();

		$this->layout_templates_to_register = array(
			'test-layout-template' => array(
				'area'       => 'test',
				'class_name' => TestLayoutTemplate::class,
			),
			'simple-product'       => array(
				'area'       => 'product-form',
				'class_name' => SimpleProductTemplate::class,
			),
			'product-variation'    => array(
				'area'       => 'product-form',
				'class_name' => ProductVariationTemplate::class,
			),
		);
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

	/**
	 * Test instantiating layout templates.
	 */
	public function test_instantiate() {
		foreach ( $this->layout_templates_to_register as $template_id => $template_info ) {
			$this->layout_template_registry->register( $template_id, $template_info['area'], $template_info['class_name'] );
		}

		$layout_templates = $this->layout_template_registry->instantiate_layout_templates();

		$this->assertCount( 3, $layout_templates );

		foreach ( $layout_templates as $layout_template ) {
			$template_info = $this->layout_templates_to_register[ $layout_template->get_id() ];
			$this->assertInstanceOf( $template_info['class_name'], $layout_template );
		}
	}
}
