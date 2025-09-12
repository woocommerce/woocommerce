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
		// Initialize store info ability
		StoreInfoAbility::init();
		
		// Future abilities will be initialized here:
		// ProductAbility::init();
		// OrderAbility::init();
	}

	/**
	 * Get WooCommerce ability IDs from the WordPress Abilities API.
	 *
	 * @return array Array of WooCommerce ability IDs.
	 */
	public function getAbilitiesIDs(): array {
		// Check if the abilities API is available
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		// Get all registered abilities
		$all_abilities = wp_get_abilities();
		
		// Filter for WooCommerce-specific abilities
		$woocommerce_abilities = array();
		foreach ( array_keys( $all_abilities ) as $ability_id ) {
			// Check if ability ID starts with 'woocommerce/'
			if ( strpos( $ability_id, 'woocommerce/' ) === 0 ) {
				$woocommerce_abilities[] = $ability_id;
			}
		}

		return $woocommerce_abilities;
	}
}