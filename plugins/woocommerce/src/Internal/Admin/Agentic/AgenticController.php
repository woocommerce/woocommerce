<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

/**
 * AgenticController class
 *
 * Main controller for Agentic Commerce Protocol features.
 * Manages initialization of webhooks and future settings for the Agentic feature.
 *
 * @since 10.3.0
 */
class AgenticController {
	/**
	 * Webhook manager instance.
	 *
	 * @var AgenticWebhookManager
	 */
	private $webhook_manager;

	/**
	 * Register hooks and initialize components.
	 *
	 * This follows the WooCommerce pattern for controllers.
	 */
	public function register() {
		// Initialize webhook functionality.
		$this->webhook_manager = new AgenticWebhookManager();
	}
}
