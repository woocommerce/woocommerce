<?php
/**
 * Abilities REST Bridge class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\Config\OrdersConfig;
use Automattic\WooCommerce\Internal\Abilities\Config\ProductsConfig;
use Automattic\WooCommerce\Internal\Abilities\REST\RestAbilityFactory;
use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities REST Bridge class for WooCommerce.
 *
 * Configuration-driven registry that exposes REST endpoints as unified WordPress abilities.
 * Each ability supports multiple operations (list, get, create, update, delete) via an action parameter.
 */
class AbilitiesRestBridge {

	/**
	 * Get REST controller configurations with unified resource-based abilities.
	 *
	 * Each configuration defines a single unified ability that supports multiple operations
	 * via an 'action' parameter (list, get, create, update, delete).
	 *
	 * Configurations are loaded from separate config classes for clarity and maintainability.
	 * Each config class defines:
	 * - Operations supported
	 * - Controller class
	 * - Allowed parameters (for MCP context optimization)
	 *
	 * @return array Controller configurations.
	 */
	private static function get_configurations(): array {
		return array(
			ProductsConfig::get_config(),
			OrdersConfig::get_config(),
		);
	}

	/**
	 * Initialize the ability registration.
	 *
	 * @internal
	 */
	final public static function init(): void {
		/*
		 * Register abilities when Abilities API is ready.
		 * Support both old (pre-6.9) and new (6.9+) action names.
		 */
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register all configured abilities.
	 */
	public static function register_abilities(): void {
		/**
		 * Filters whether to bypass the MCP request check when registering abilities.
		 *
		 * Allows abilities to be registered outside of MCP requests (e.g., for settings display).
		 *
		 * @since 10.4.0
		 *
		 * @param bool $bypass_check Whether to bypass the MCP request check. Default false.
		 */
		$bypass_check = apply_filters( 'woocommerce_mcp_bypass_request_check', false );

		// Only register abilities if this is an MCP endpoint request or check is bypassed.
		// We check here (on abilities_api_init action) rather than earlier
		// because REST request detection requires the WordPress REST infrastructure
		// to be fully initialized.
		if ( ! $bypass_check && ! MCPAdapterProvider::is_mcp_request() ) {
			return;
		}

		foreach ( self::get_configurations() as $config ) {
			RestAbilityFactory::register_controller_abilities( $config );
		}
	}
}
