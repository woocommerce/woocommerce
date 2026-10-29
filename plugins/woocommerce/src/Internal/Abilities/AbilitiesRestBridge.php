<?php
/**
 * Abilities REST Bridge class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\MCP\McpEndpointRemoval;

defined( 'ABSPATH' ) || exit;

/**
 * Formerly registered REST-derived abilities for the WooCommerce MCP endpoint.
 *
 * Kept so existing callers don't fatal. The methods do nothing except report incorrect usage.
 *
 * @deprecated 11.3.0 Removed along with the WooCommerce MCP endpoint. Use the WooCommerce abilities registered through the WordPress Abilities API.
 */
class AbilitiesRestBridge {

	/**
	 * Formerly hooked the REST-derived ability registration.
	 *
	 * @internal
	 *
	 * @deprecated 11.3.0
	 */
	final public static function init(): void {
		McpEndpointRemoval::doing_it_wrong( __METHOD__ );
	}

	/**
	 * Formerly registered the REST-derived abilities.
	 *
	 * @deprecated 11.3.0
	 */
	public static function register_abilities(): void {
		McpEndpointRemoval::doing_it_wrong( __METHOD__ );
	}
}
