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
		AbilitiesCategories::init();
		AbilitiesRestBridge::init();
		$this->maybe_remove_vendor_rest_init();
	}

	/**
	 * Remove the vendor abilities-api REST route registration on WP 6.9+.
	 *
	 * WordPress 6.9+ ships the Abilities REST API in core, making the vendor
	 * package's route registration redundant. Removing the hook prevents fatal
	 * errors when stale PHP opcache serves bytecode from an older vendor version
	 * (e.g. v0.3.0) that references endpoint filenames renamed in v0.4.0.
	 *
	 * TODO: Remove this method and the wordpress/abilities-api Composer dependency
	 * entirely once WordPress 6.9 is the minimum supported version.
	 *
	 * @since 10.7.0
	 */
	private function maybe_remove_vendor_rest_init(): void {
		if ( class_exists( 'WP_REST_Abilities_V1_Run_Controller' ) ) {
			remove_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ), 11 );
		}
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
