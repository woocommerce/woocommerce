<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticController;
use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookManager;

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

		// Ensure WC_INSTALLING is not set during the test.
		if ( defined( 'WC_INSTALLING' ) ) {
			\Automattic\Jetpack\Constants::set_constant( 'WC_INSTALLING', false );
		}

		// Resolve controller from container to ensure proper DI.
		$controller = wc_get_container()->get( AgenticController::class );
		$controller->register();

		// Call on_init directly to initialize the webhook manager.
		$controller->on_init();

		/**
		 * Verify webhook topics are registered (indicates manager was initialized).
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::register_webhook_topic_names()
		 */
		$topics = apply_filters( 'woocommerce_webhook_topics', array() );
		$this->assertArrayHasKey( AgenticWebhookManager::WEBHOOK_TOPIC, $topics );
	}
}
