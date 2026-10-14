<?php
/**
 * McpEndpointRemoval class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

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
	 * Namespace of the removed WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	private const ENDPOINT_NAMESPACE = 'woocommerce';

	/**
	 * Route of the removed WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	private const ENDPOINT_ROUTE = '/mcp';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'handle_rest_api_init' ) );
	}

	/**
	 * Handle the rest_api_init hook.
	 *
	 * Registers the removed WooCommerce MCP endpoint so MCP clients that still use it get an explanation instead of a 404.
	 *
	 * @internal
	 */
	public function handle_rest_api_init(): void {
		register_rest_route(
			self::ENDPOINT_NAMESPACE,
			self::ENDPOINT_ROUTE,
			array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => array( $this, 'get_endpoint_removed_response' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Respond to a request to the removed WooCommerce MCP endpoint.
	 *
	 * The response is a JSON-RPC error, which MCP clients surface to the user or agent, sent with HTTP status 410 Gone.
	 *
	 * @internal
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	public function get_endpoint_removed_response( WP_REST_Request $request ): WP_REST_Response {
		$params     = $request->get_json_params();
		$request_id = is_array( $params ) && isset( $params['id'] ) && ( is_string( $params['id'] ) || is_int( $params['id'] ) ) ? $params['id'] : null;
		$adapter    = rest_url( 'mcp/mcp-adapter-default-server' );

		return new WP_REST_Response(
			array(
				'jsonrpc' => '2.0',
				'id'      => $request_id,
				'error'   => array(
					'code'    => -32000,
					'message' => sprintf(
						/* translators: 1: WooCommerce version, 2: MCP Adapter endpoint URL, 3: Documentation URL. */
						__( 'The WooCommerce MCP endpoint (woocommerce/mcp) was removed in WooCommerce %1$s. To keep using WooCommerce with MCP, install and activate the MCP Adapter plugin (https://wordpress.org/plugins/mcp-adapter/) on this site, then connect your MCP client to %2$s using a WordPress application password. Documentation: %3$s', 'woocommerce' ),
						self::REMOVED_IN,
						$adapter,
						self::DOCS_URL
					),
					'data'    => array(
						'replacement_endpoint' => $adapter,
						'documentation_url'    => self::DOCS_URL,
					),
				),
			),
			410
		);
	}

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
