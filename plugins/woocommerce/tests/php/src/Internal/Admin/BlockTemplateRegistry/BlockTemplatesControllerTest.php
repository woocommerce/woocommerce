<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplateRegistry;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplatesController;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\TemplateTransformer;

use WC_REST_Unit_Test_Case;

/**
 * Tests for the BlockTemplatesControllerTest class.
 */
class BlockTemplatesControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Block template registry.
	 *
	 * @var BlockTemplateRegistry
	 */
	protected $block_template_registry;

    /**
	 * Block templates controller.
	 *
	 * @var BlockTemplatesController
	 */
	protected $block_templates_controller;

	/**
	 * Runs before suite initialization.
	 */
	public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
		wc_get_container()->get( BlockTemplatesController::class );
		$block_template_registry = wc_get_container()->get( BlockTemplateRegistry::class );
		$block_template_registry->register( new CustomBlockTemplate() );
	}

    /**
	 * Runs before each test.
	 */
	public function setUp(): void {
        parent::setUp();

        $admin_user = wp_insert_user(
			array(
				'user_login' => uniqid(),
				'role'       => 'administrator',
				'user_pass'  => 'x',
			)
		);
		wp_set_current_user( $admin_user );
	}

	/**
	 * Test getting a registered template when area is specififed.
	 */
	public function test_get_template_by_area() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/templates' );
        $request->set_param( 'area', 'test-area' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $data );
		$this->assertEquals( 'custom-block-template', $data[0]['id'] );
	}

    /**
	 * Test that getting default templates works as expected and does not show the custom templates.
	 */
	public function test_get_template_without_area() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/templates' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

        $found_registery_template = false;
        foreach ( $data as $template ) {
            if ( $template['id'] === 'custom-block-template' ) {
                $found_registery_template = true;
            }
        }

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( $found_registery_template );
	}
}
