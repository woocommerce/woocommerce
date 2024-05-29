<?php

use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

use Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates\SimpleProductTemplate;
use Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates\ProductVariationTemplate;

use Automattic\WooCommerce\Tests\LayoutTemplates\TestLayoutTemplate;

/**
 * class WC_REST_Layout_Templates_Controller_Tests.
 * Layout Templates Controller tests for V3 REST API.
 */
class WC_REST_Layout_Templates_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$layout_template_registry = wc_get_container()->get( LayoutTemplateRegistry::class );

		$layout_template_registry->unregister_all();

		$layout_template_registry->register( 'test-layout-template', 'test', TestLayoutTemplate::class );
		$layout_template_registry->register( 'simple-product', 'product-form', SimpleProductTemplate::class );
		$layout_template_registry->register( 'product-variation', 'product-form', ProductVariationTemplate::class );
	}

	/**
	 * Test getting all layout templates.
	 */
	public function test_get_all_items() {
		$response = $this->do_rest_get_request( 'layout-templates' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$this->assertCount( 3, $data );

		$this->assertArrayHasKey( 'test-layout-template', $data );
		$this->assertArrayHasKey( 'simple-product', $data );
		$this->assertArrayHasKey( 'product-variation', $data );
	}

	/**
	 * Test getting all layout templates for a specific area.
	 */
	public function test_get_all_items_for_area() {
		$response = $this->do_rest_get_request( 'layout-templates', array( 'area' => 'product-form' ) );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$this->assertCount( 2, $data );

		$this->assertArrayHasKey( 'simple-product', $data );
		$this->assertArrayHasKey( 'product-variation', $data );
	}

	/**
	 * Test getting all layout templates for an invalid area.
	 */
	public function test_get_all_items_for_invalid_area() {
		$response = $this->do_rest_get_request( 'layout-templates', array( 'area' => 'invalid-area' ) );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertEmpty( $data );
	}

	/**
	 * Test getting a single layout template.
	 */
	public function test_get_single_item() {
		$response = $this->do_rest_get_request( 'layout-templates/test-layout-template' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$this->assertEquals( 'test-layout-template', $data['id'] );
		$this->assertEquals( 'test', $data['area'] );

		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'blockTemplates', $data );
	}

	/**
	 * Test getting a single layout template with invalid id.
	 */
	public function test_get_single_item_with_invalid_id() {
		$response = $this->do_rest_get_request( 'layout-templates/invalid-layout-template' );

		$this->assertEquals( 404, $response->get_status() );
	}
}
