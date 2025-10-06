<?php
/**
 * Abilities Registry class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities Registry class for WooCommerce.
 *
 * Centralized registry that initializes all WooCommerce abilities.
 * These abilities can be consumed by MCP, REST API, or other tools.
 */
class AbilitiesRegistry {

	/**
	 * Initialize the registry.
	 */
	public function __construct() {
		$this->init_abilities();
	}

	/**
	 * Initialize all WooCommerce abilities.
	 */
	private function init_abilities(): void {
		AbilitiesRestBridge::init();
	}

	/**
	 * Get all ability IDs from the WordPress Abilities API.
	 *
	 * @return array Array of all ability IDs.
	 */
	public function get_abilities_ids(): array {
		// Check if the abilities API is available.
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		$all_abilities = wp_get_abilities();

		return array_keys( $all_abilities );
	}
}
