<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticController;

/**
 * Tests for AgenticController class.
 */
class AgenticControllerTest extends \WC_Unit_Test_Case {
	/**
	 * Test that controller initializes webhook manager.
	 */
	public function test_register_initializes_webhook_manager() {
		// Enable the agentic checkout feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		$controller = new AgenticController();
		$controller->register();

		/**
		 * Verify webhook topics are registered (indicates manager was initialized).
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::register_webhook_topic_names()
		 */
		$topics = apply_filters( 'woocommerce_webhook_topics', array() );
		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_created', $topics );
		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_updated', $topics );
	}
}
