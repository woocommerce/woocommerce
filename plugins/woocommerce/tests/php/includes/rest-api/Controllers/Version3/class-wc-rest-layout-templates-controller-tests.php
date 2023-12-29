<?php

use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\SimpleProductTemplate;
use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\ProductVariationTemplate;

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
}
