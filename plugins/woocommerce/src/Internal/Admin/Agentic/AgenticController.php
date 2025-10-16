<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * AgenticController class
 *
 * Main controller for Agentic Commerce Protocol features.
 * Manages initialization of webhooks and future settings for the Agentic feature.
 *
 * @since 10.3.0
 */
class AgenticController implements RegisterHooksInterface {
	/**
	 * Register this class instance to the appropriate hooks.
	 *
	 * @internal
	 */
	public function register() {
		// Don't register hooks during installation.
		if ( Constants::is_true( 'WC_INSTALLING' ) ) {
			return;
		}

		// We want to run on init for translations but before woocommerce_init so that
		// we can hook the new integration settings page. We should be able to simplify
		// this by just hooking here when we no longer need to check if the feature is enabled.
		add_action( 'before_woocommerce_init', array( $this, 'on_init' ) );
	}

	/**
	 * Hook into WordPress on init.
	 *
	 * @internal
	 */
	public function on_init() {
		// Bail if the feature is not enabled.
		if ( ! FeaturesUtil::feature_is_enabled( 'agentic_checkout' ) ) {
			return;
		}

		// Resolve webhook manager from container.
		wc_get_container()->get( AgenticWebhookManager::class )->register();

		// Register Agentic Commerce integration.
		add_filter( 'woocommerce_integrations', array( $this, 'add_agentic_commerce_integration' ) );
	}

	/**
	 * Add Agentic Commerce integration to WooCommerce integrations.
	 *
	 * @param array $integrations Existing integrations.
	 * @return array Modified integrations.
	 */
	public function add_agentic_commerce_integration( $integrations ): array {
		if ( ! is_array( $integrations ) ) {
			$integrations = array();
		}
		$integrations[] = AgenticCommerceIntegration::class;
		return $integrations;
	}
}
