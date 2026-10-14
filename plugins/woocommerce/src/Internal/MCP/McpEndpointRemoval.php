<?php
/**
 * McpEndpointRemoval class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the removal of the WooCommerce MCP endpoint (woocommerce/mcp) and its REST-derived abilities.
 *
 * WooCommerce abilities remain registered through the WordPress Abilities API, and the MCP Adapter
 * plugin exposes them to MCP clients.
 *
 * @since 11.3.0
 */
class McpEndpointRemoval {

	/**
	 * WooCommerce version that removed the WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	public const REMOVED_IN = '11.3.0';

	/**
	 * Documentation for using WooCommerce abilities with MCP.
	 *
	 * @var string
	 */
	public const DOCS_URL = 'https://developer.woocommerce.com/docs/features/mcp/';

	/**
	 * Report a call to code that was removed along with the WooCommerce MCP endpoint.
	 *
	 * @param string $function_name The removed function or method that was called.
	 */
	public static function doing_it_wrong( string $function_name ): void {
		wc_doing_it_wrong(
			$function_name,
			sprintf(
				/* translators: 1: Function or method name, 2: WooCommerce version, 3: Documentation URL. */
				__( '%1$s was removed in WooCommerce %2$s along with the WooCommerce MCP endpoint and its REST-based abilities. WooCommerce abilities remain available through the WordPress Abilities API, and the MCP Adapter plugin exposes them to MCP clients. See %3$s.', 'woocommerce' ),
				$function_name,
				self::REMOVED_IN,
				self::DOCS_URL
			),
			self::REMOVED_IN
		);
	}
}
