<?php
/**
 *  Plugin class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\POSIntegration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
final class ProductFeed implements RegisterHooksInterface {
	/**
	 * Integration registry.
	 *
	 * @var IntegrationRegistry
	 */
	private IntegrationRegistry $integration_registry;

	/**
	 * Private constructor
	 */
	public function init(
		IntegrationRegistry $integration_registry,
		POSIntegration $pos_integration,
	) {
		$this->integration_registry = $integration_registry;

		$this->integration_registry->register_integration( $pos_integration );
	}

	/**
	 * Initialize plugin components
	 */
	public function register(): void {
		// Let all integrations register their hooks.
		foreach ( $this->integration_registry->get_integrations() as $integration ) {
			$integration->register_hooks();
		}
	}

	/**
	 * Plugin activation
	 */
	public function activate(): void {
		foreach ( $this->integration_registry->get_integrations() as $integration ) {
			$integration->activate();
		}
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate(): void {
		foreach ( $this->integration_registry->get_integrations() as $integration ) {
			$integration->deactivate();
		}
	}
}
