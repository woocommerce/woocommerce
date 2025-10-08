<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticController;

/**
 * Tests for AgenticController class.
 */
class AgenticControllerTest extends \WC_Unit_Test_Case {
	/**
	 * Controller instance.
	 *
	 * @var AgenticController
	 */
	private $controller;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->controller = new AgenticController();
	}

	/**
	 * Test that controller registers hooks.
	 */
	public function test_register_hooks() {
		// Remove any existing hooks first.
		remove_all_filters( 'woocommerce_webhook_topics' );

		// Register the controller.
		$this->controller->register();

		// Check that webhook topics filter is registered.
		$this->assertTrue( has_filter( 'woocommerce_webhook_topics' ) );
	}

	/**
	 * Test that webhooks are initialized through register.
	 */
	public function test_webhooks_initialized() {
		// Register the controller.
		$this->controller->register();

		// Check that webhook topics are registered.
		$topics = apply_filters( 'woocommerce_webhook_topics', array() );

		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_created', $topics );
		$this->assertEquals( 'Agentic Order Created', $topics['action.woocommerce_agentic_order_created'] );

		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_updated', $topics );
		$this->assertEquals( 'Agentic Order Updated', $topics['action.woocommerce_agentic_order_updated'] );
	}
}