<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\MCP\McpEndpointRemoval;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the McpEndpointRemoval class.
 */
class McpEndpointRemovalTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should answer MCP requests to the removed endpoint with a JSON-RPC error and HTTP 410.
	 */
	public function test_removed_endpoint_returns_json_rpc_error(): void {
		$request = new WP_REST_Request( 'POST', '/woocommerce/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'jsonrpc' => '2.0',
					'id'      => 7,
					'method'  => 'initialize',
				)
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 410, $response->get_status(), 'The removed endpoint should respond with 410 Gone' );
		$this->assertSame( '2.0', $data['jsonrpc'] );
		$this->assertSame( 7, $data['id'], 'The JSON-RPC request id should be echoed' );
		$this->assertSame( -32000, $data['error']['code'] );
		$this->assertStringContainsString( 'MCP Adapter plugin', $data['error']['message'] );
		$this->assertStringContainsString( 'mcp/mcp-adapter-default-server', $data['error']['data']['replacement_endpoint'] );
		$this->assertSame( McpEndpointRemoval::DOCS_URL, $data['error']['data']['documentation_url'] );
	}

	/**
	 * @testdox Should answer non-JSON-RPC requests to the removed endpoint with a null JSON-RPC id.
	 */
	public function test_removed_endpoint_handles_requests_without_json_rpc_id(): void {
		$response = rest_do_request( new WP_REST_Request( 'GET', '/woocommerce/mcp' ) );

		$this->assertSame( 410, $response->get_status() );
		$this->assertNull( $response->get_data()['id'], 'Requests without a JSON-RPC id should get a null id' );
	}

	/**
	 * @testdox Should report the WooCommerce MCP feature as disabled even when its old option is enabled.
	 */
	public function test_mcp_feature_is_always_disabled(): void {
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );

		$this->assertFalse( wc_get_container()->get( FeaturesController::class )->feature_is_enabled( 'mcp_integration' ) );
	}
}
