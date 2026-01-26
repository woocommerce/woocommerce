<?php
/**
 *  Plugin class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed;

use Automattic\WooCommerce\Internal\ProductFeed\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\POSIntegration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Product Feed class.
 *
 * @since 10.5.0
 */
final class ProductFeed implements RegisterHooksInterface {
	/**
	 * Integration registry.
	 *
	 * @var IntegrationRegistry
	 */
	private IntegrationRegistry $integration_registry;

	/**
	 * Dependency injector.
	 *
	 * @param IntegrationRegistry $integration_registry The integration registry.
	 * @param POSIntegration      $pos_integration The POS integration.
	 * @internal
	 */
	public function init( // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingFinal
		IntegrationRegistry $integration_registry,
		POSIntegration $pos_integration
	): void {
		$this->integration_registry = $integration_registry;
		$this->integration_registry->register_integration( $pos_integration );
	}

	/**
	 * Allows extensions to register integrations.
	 *
	 * @since 10.5.0
	 * @param IntegrationInterface $integration The integration to register.
	 * @return void
	 */
	public function register_integration( IntegrationInterface $integration ): void {
		$this->integration_registry->register_integration( $integration );
	}

	/**
	 * Initialize plugin components
	 *
	 * @since 10.5.0
	 */
	public function register(): void {
		// Let all integrations register their hooks.
		foreach ( $this->integration_registry->get_integrations() as $integration ) {
			$integration->register_hooks();
		}
	}

	/**
	 * Plugin activation
	 *
	 * @since 10.5.0
	 */
	public function activate(): void {
		foreach ( $this->integration_registry->get_integrations() as $integration ) {
			$integration->activate();
		}
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 10.5.0
	 */
	public function deactivate(): void {
		foreach ( $this->integration_registry->get_integrations() as $integration ) {
			$integration->deactivate();
		}
	}
}
