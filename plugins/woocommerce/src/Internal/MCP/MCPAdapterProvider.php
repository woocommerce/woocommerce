<?php
/**
 * MCP Adapter Provider class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Formerly provided the WooCommerce MCP endpoint (woocommerce/mcp).
 *
 * Kept so existing callers don't fatal. The methods do nothing except report incorrect usage.
 *
 * @deprecated 11.3.0 The WooCommerce MCP endpoint was removed. Use the MCP Adapter plugin to access WooCommerce abilities over MCP.
 */
class MCPAdapterProvider {

	/**
	 * Namespace of the removed WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	const MCP_NAMESPACE = 'woocommerce';

	/**
	 * Route of the removed WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	const MCP_ROUTE = 'mcp';

	/**
	 * Constructor.
	 *
	 * @deprecated 11.3.0
	 */
	public function __construct() {
		McpEndpointRemoval::doing_it_wrong( __METHOD__ );
	}

	/**
	 * Formerly initialized the MCP adapter when the WooCommerce MCP feature was enabled.
	 *
	 * @deprecated 11.3.0
	 */
	public function maybe_initialize(): void {
		McpEndpointRemoval::doing_it_wrong( __METHOD__ );
	}

	/**
	 * Formerly created the WooCommerce MCP server.
	 *
	 * @deprecated 11.3.0
	 *
	 * @param object $adapter MCP adapter instance. Ignored.
	 */
	public function initialize_mcp_server( $adapter ): void {
		unset( $adapter ); // Kept for backward compatibility.
		McpEndpointRemoval::doing_it_wrong( __METHOD__ );
	}

	/**
	 * Formerly reported whether the MCP adapter was initialized.
	 *
	 * @deprecated 11.3.0
	 *
	 * @return bool Always false.
	 */
	public function is_initialized(): bool {
		McpEndpointRemoval::doing_it_wrong( __METHOD__ );
		return false;
	}

	/**
	 * Formerly reported whether the current request was for the WooCommerce MCP endpoint.
	 *
	 * @deprecated 11.3.0
	 *
	 * @return bool Always false.
	 */
	public static function is_mcp_request(): bool {
		McpEndpointRemoval::doing_it_wrong( __METHOD__ );
		return false;
	}
}
