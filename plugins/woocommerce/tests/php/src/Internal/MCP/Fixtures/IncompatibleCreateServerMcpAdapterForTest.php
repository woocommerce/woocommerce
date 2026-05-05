<?php
/**
 * IncompatibleCreateServerMcpAdapterForTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP\Fixtures;

/**
 * MCP adapter test fixture with an incompatible create_server() signature.
 */
class IncompatibleCreateServerMcpAdapterForTest {

	/**
	 * Get the adapter instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		return new self();
	}

	/**
	 * Create a server.
	 *
	 * @param mixed $server_id Server ID.
	 * @param mixed $server_route_namespace Server route namespace.
	 * @param mixed $server_route Server route.
	 * @param mixed $server_name Server name.
	 * @param mixed $server_description Server description.
	 * @param mixed $server_version Server version.
	 * @param mixed $mcp_transports MCP transports.
	 * @param mixed $error_handler Error handler.
	 * @param mixed $observability_handler Observability handler.
	 * @param mixed $tools Tools.
	 * @param mixed $resources Resources.
	 * @return self
	 */
	public function create_server( $server_id, $server_route_namespace, $server_route, $server_name, $server_description, $server_version, $mcp_transports, $error_handler, $observability_handler, $tools, $resources ) {
		return $this;
	}
}
