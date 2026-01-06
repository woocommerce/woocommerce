<?php
/**
 * Integration Registry class.
 *
 * Stores all provider integrations that are available.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IntegrationRegistry
 *
 * @since 10.5.0
 */
class IntegrationRegistry {
	/**
	 * List of all available Integrations.
	 *
	 * @var array<string,IntegrationInterface>
	 */
	private array $integrations = array();

	/**
	 * Register an Integration.
	 *
	 * @since 10.5.0
	 *
	 * @param IntegrationInterface $integration The integration to register.
	 */
	public function register_integration( IntegrationInterface $integration ): void {
		$this->integrations[ $integration->get_id() ] = $integration;
	}

	/**
	 * Get an Integration by ID.
	 *
	 * @since 10.5.0
	 *
	 * @param string $id The ID of the Integration.
	 * @return IntegrationInterface|null The Integration, or null if it is not registered.
	 */
	public function get_integration( string $id ): ?IntegrationInterface {
		return $this->integrations[ $id ] ?? null;
	}

	/**
	 * Get all registered integrations.
	 *
	 * @since 10.5.0
	 *
	 * @return array<string,IntegrationInterface>
	 */
	public function get_integrations(): array {
		return $this->integrations;
	}
}
