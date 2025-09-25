<?php
/**
 * MCP Tools Integration Test class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\REST;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesRestBridge;
use WC_Helper_Product;
use WC_Helper_Order;

/**
 * Integration tests for MCP tools via MCP REST endpoint.
 *
 * These tests verify that MCP tools work end-to-end through the full MCP server stack by:
 * 1. Making REST requests to /wp-json/woocommerce/mcp
 * 2. Using JSON-RPC protocol format
 * 3. Testing authentication and transport layer
 * 4. Verifying complete request/response flow
 */
class MCPToolsIntegrationTest extends \WC_REST_Unit_Test_Case {


	/**
	 * Test product.
	 *
	 * @var \WC_Product
	 */
	private $test_product;

	/**
	 * Test order.
	 *
	 * @var \WC_Order
	 */
	private $test_order;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Test API key for MCP requests.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Set up once before all tests in this class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Enable MCP feature flag for all tests.
		add_filter( 'woocommerce_features', function( $features ) {
			$features['mcp_integration'] = true;
			return $features;
		}, 1 );

		// Also try enabling via option.
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );

		// Bootstrap Abilities API.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$bootstrap_file = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $bootstrap_file ) ) {
				require $bootstrap_file;
			}
		}

		// Mock MCP transport authentication for all tests.
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );
		add_filter( 'woocommerce_is_mcp_request', '__return_true' );
		add_filter( 'woocommerce_check_rest_ability_permissions_for_method', '__return_true' );
	}

	/**
	 * Clean up once after all tests in this class.
	 */
	public static function tearDownAfterClass(): void {
		// Reset filters added in setUpBeforeClass.
		remove_all_filters( 'woocommerce_features' );
		remove_all_filters( 'woocommerce_mcp_allow_insecure_transport' );
		remove_all_filters( 'woocommerce_is_mcp_request' );
		remove_all_filters( 'woocommerce_check_rest_ability_permissions_for_method' );

		// Clean up feature flag options.
		delete_option( 'woocommerce_feature_mcp_integration_enabled' );

		parent::tearDownAfterClass();
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		// Skip if abilities API not available.
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available' );
		}

		// Create admin user.
		$this->admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );

		// Create test product.
		$this->test_product = WC_Helper_Product::create_simple_product();
		$this->test_product->set_name( 'Test Product for MCP' );
		$this->test_product->set_regular_price( '99.99' );
		$this->test_product->save();

		// Create test order.
		$this->test_order = WC_Helper_Order::create_order();
		$this->test_order->add_product( $this->test_product, 2 );
		$this->test_order->set_status( 'processing' );
		$this->test_order->save();

		// Create a real WooCommerce API key for testing.
		$this->api_key = $this->create_api_key();

		// Initialize MCP for each test to ensure clean state.
		$this->initialize_mcp();
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		// Clean up test data.
		if ( $this->test_product ) {
			$this->test_product->delete( true );
		}
		if ( $this->test_order ) {
			$this->test_order->delete( true );
		}

		// Reset any test-specific filters.
		remove_all_filters( 'woocommerce_mcp_include_ability' );
		remove_all_filters( 'mcp_validation_enabled' );
		remove_all_filters( 'rest_pre_dispatch' );

		// Reset global abilities registry to prevent duplication warnings.
		global $wp_abilities;
		$wp_abilities = array();

		// Reset user.
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Create a WooCommerce API key for testing.
	 *
	 * @return string The API key in format consumer_key:consumer_secret.
	 */
	private function create_api_key(): string {
		global $wpdb;

		// Generate consumer key and secret.
		$consumer_key = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

		// Hash the consumer key as WooCommerce does.
		$hashed_consumer_key = wc_api_hash( $consumer_key );

		// Insert API key into database.
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $this->admin_user_id,
				'description'     => 'Test API Key for MCP Integration Tests',
				'permissions'     => 'read_write',
				'consumer_key'    => $hashed_consumer_key,
				'consumer_secret' => $consumer_secret,
				'nonces'          => null,
			)
		);

		return $consumer_key . ':' . $consumer_secret;
	}

	/**
	 * Initialize MCP server and abilities.
	 */
	private function initialize_mcp(): void {
		// Clear existing WooCommerce abilities to prevent duplication warnings.
		if ( function_exists( 'wp_get_abilities' ) ) {
			$existing_abilities = wp_get_abilities();
			foreach ( $existing_abilities as $ability_name => $ability ) {
				if ( str_starts_with( $ability_name, 'woocommerce/' ) ) {
					wp_unregister_ability( $ability_name );
				}
			}
		}

		// Trigger abilities API initialization.
		do_action( 'abilities_api_init' );

		// Get MCP provider from container and initialize.
		$container = wc_get_container();
		if ( $container->has( \Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider::class ) ) {
			$mcp_provider = $container->get( \Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider::class );
			$mcp_provider->maybe_initialize();
		}

		// Trigger REST API initialization.
		do_action( 'rest_api_init' );
	}


	/**
	 * Make an MCP REST request.
	 *
	 * @param string $method MCP method name.
	 * @param array  $params Method parameters.
	 * @return \WP_REST_Response Response object.
	 */
	private function make_mcp_request( string $method, array $params = array() ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/woocommerce/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( 'X-MCP-API-Key', $this->api_key );

		$body = array(
			'method' => $method,
			'params' => $params,
		);

		$request->set_body( wp_json_encode( $body ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * Call an MCP tool via REST endpoint.
	 *
	 * @param string $tool_name Tool name.
	 * @param array  $arguments Tool arguments.
	 * @return \WP_REST_Response Response object.
	 */
	private function call_mcp_tool( string $tool_name, array $arguments = array() ): \WP_REST_Response {
		return $this->make_mcp_request(
			'tools/call',
			array(
				'name' => $tool_name,
				'arguments' => $arguments,
			)
		);
	}

	/**
	 * Test that product get tool works via MCP REST endpoint.
	 */
	public function test_product_get_tool_works() {
		$response = $this->call_mcp_tool(
			'woocommerce-products-get',
			array( 'id' => $this->test_product->get_id() )
		);

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'content', $data, 'Response should have content field' );
		$this->assertArrayHasKey( 'structuredContent', $data, 'Response should have structuredContent field' );

		// Check structured content contains product data.
		$product_data = $data['structuredContent'];
		$this->assertIsArray( $product_data, 'Structured content should be array' );
		$this->assertEquals( $this->test_product->get_id(), $product_data['id'], 'Product ID should match' );
		$this->assertEquals( 'Test Product for MCP', $product_data['name'], 'Product name should match' );
		$this->assertEquals( '99.99', $product_data['regular_price'], 'Product price should match' );
	}

	/**
	 * Test that product list tool works via MCP REST endpoint.
	 */
	public function test_product_list_tool_works() {
		$response = $this->call_mcp_tool(
			'woocommerce-products-list',
			array( 'per_page' => 10 )
		);

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'content', $data, 'Response should have content field' );
		$this->assertArrayHasKey( 'structuredContent', $data, 'Response should have structuredContent field' );

		// Check structured content contains product list.
		$list_data = $data['structuredContent'];
		$this->assertIsArray( $list_data, 'Structured content should be array' );
		$this->assertArrayHasKey( 'data', $list_data, 'List should have data field' );
		$this->assertIsArray( $list_data['data'], 'Data should be array' );

		// Our test product should be in the list.
		$found = false;
		foreach ( $list_data['data'] as $product ) {
			if ( $product['id'] === $this->test_product->get_id() ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Test product should be in list' );
	}

	/**
	 * Test that order get tool works via MCP REST endpoint.
	 */
	public function test_order_get_tool_works() {
		$response = $this->call_mcp_tool(
			'woocommerce-orders-get',
			array( 'id' => $this->test_order->get_id() )
		);

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'content', $data, 'Response should have content field' );
		$this->assertArrayHasKey( 'structuredContent', $data, 'Response should have structuredContent field' );

		// Check structured content contains order data.
		$order_data = $data['structuredContent'];
		$this->assertIsArray( $order_data, 'Structured content should be array' );
		$this->assertEquals( $this->test_order->get_id(), $order_data['id'], 'Order ID should match' );
		$this->assertEquals( 'processing', $order_data['status'], 'Order status should match' );
		$this->assertArrayHasKey( 'line_items', $order_data, 'Order should have line items' );
	}

	/**
	 * Test that order list tool works via MCP REST endpoint.
	 */
	public function test_order_list_tool_works() {
		$response = $this->call_mcp_tool(
			'woocommerce-orders-list',
			array( 'per_page' => 10 )
		);

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'content', $data, 'Response should have content field' );
		$this->assertArrayHasKey( 'structuredContent', $data, 'Response should have structuredContent field' );

		// Check structured content contains order list.
		$list_data = $data['structuredContent'];
		$this->assertIsArray( $list_data, 'Structured content should be array' );
		$this->assertArrayHasKey( 'data', $list_data, 'List should have data field' );

		// Our test order should be in the list.
		$found = false;
		foreach ( $list_data['data'] as $order ) {
			if ( $order['id'] === $this->test_order->get_id() ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Test order should be in list' );
	}

	/**
	 * Test that order note create tool works via MCP REST endpoint.
	 */
	public function test_order_note_create_tool_works() {
		$response = $this->call_mcp_tool(
			'woocommerce-order-notes-create',
			array(
				'order_id'      => $this->test_order->get_id(),
				'note'          => 'Test note from MCP integration test',
				'customer_note' => false,
			)
		);

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'content', $data, 'Response should have content field' );
		$this->assertArrayHasKey( 'structuredContent', $data, 'Response should have structuredContent field' );

		// Check structured content contains note data.
		$note_data = $data['structuredContent'];
		$this->assertIsArray( $note_data, 'Structured content should be array' );
		$this->assertArrayHasKey( 'id', $note_data, 'Note should have ID' );
		$this->assertArrayHasKey( 'note', $note_data, 'Note should have note field' );
		$this->assertEquals( 'Test note from MCP integration test', $note_data['note'], 'Note text should match' );
	}

	/**
	 * Test that order notes list tool works via MCP REST endpoint.
	 */
	public function test_order_notes_list_tool_works() {
		// First add a note.
		$this->test_order->add_order_note( 'Test note for list' );

		$response = $this->call_mcp_tool(
			'woocommerce-order-notes-list',
			array( 'order_id' => $this->test_order->get_id() )
		);

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'content', $data, 'Response should have content field' );
		$this->assertArrayHasKey( 'structuredContent', $data, 'Response should have structuredContent field' );

		// Check structured content contains notes list.
		$notes_data = $data['structuredContent'];
		$this->assertIsArray( $notes_data, 'Structured content should be array' );
		$this->assertArrayHasKey( 'data', $notes_data, 'Notes should have data field' );
		$this->assertNotEmpty( $notes_data['data'], 'Notes data should not be empty' );

		// Check we have our test note.
		$found = false;
		foreach ( $notes_data['data'] as $note ) {
			if ( strpos( $note['note'], 'Test note for list' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Test note should be in list' );
	}

	/**
	 * Test that system status get tool works via MCP REST endpoint.
	 */
	public function test_system_status_get_tool_works() {
		$response = $this->call_mcp_tool( 'woocommerce-system-status-get' );

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'content', $data, 'Response should have content field' );
		$this->assertArrayHasKey( 'structuredContent', $data, 'Response should have structuredContent field' );

		// Check structured content contains system status.
		$status_data = $data['structuredContent'];
		$this->assertIsArray( $status_data, 'Structured content should be array' );
		$this->assertArrayHasKey( 'data', $status_data, 'System status should have data field' );
		$this->assertArrayHasKey( 'environment', $status_data['data'], 'System status should have environment data' );
		$this->assertArrayHasKey( 'database', $status_data['data'], 'System status should have database data' );
		$this->assertArrayHasKey( 'active_plugins', $status_data['data'], 'System status should have plugins data' );
	}

	/**
	 * Test that tools/list method works via MCP REST endpoint.
	 */
	public function test_tools_list_works() {
		$response = $this->make_mcp_request( 'tools/list' );

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'tools', $data, 'Response should have tools field' );
		$this->assertIsArray( $data['tools'], 'Tools should be array' );

		// Should have WooCommerce tools.
		$tool_names = array_column( $data['tools'], 'name' );
		$this->assertContains( 'woocommerce-products-get', $tool_names, 'Should have product get tool' );
		$this->assertContains( 'woocommerce-products-list', $tool_names, 'Should have product list tool' );
		$this->assertContains( 'woocommerce-orders-get', $tool_names, 'Should have order get tool' );

		// Check tool structure.
		foreach ( $data['tools'] as $tool ) {
			$this->assertArrayHasKey( 'name', $tool, 'Tool should have name' );
			$this->assertArrayHasKey( 'description', $tool, 'Tool should have description' );
			$this->assertArrayHasKey( 'inputSchema', $tool, 'Tool should have input schema' );
		}
	}

	/**
	 * Test that initialize method works via MCP REST endpoint.
	 */
	public function test_initialize_works() {
		$response = $this->make_mcp_request( 'initialize' );

		// Should return successful response.
		$this->assertEquals( 200, $response->get_status(), 'MCP REST request should return 200' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'protocolVersion', $data, 'Should have protocol version' );
		$this->assertArrayHasKey( 'capabilities', $data, 'Should have capabilities' );
		$this->assertArrayHasKey( 'serverInfo', $data, 'Should have server info' );

		// Check capabilities.
		$capabilities = $data['capabilities'];
		$this->assertArrayHasKey( 'tools', $capabilities, 'Should support tools' );
	}

	/**
	 * Test error handling for invalid tool names.
	 */
	public function test_error_handling_invalid_tool() {
		$response = $this->call_mcp_tool( 'non-existent/tool' );

		// Should return error response.
		$this->assertEquals( 200, $response->get_status(), 'REST response should be 200 (error is in content)' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'error', $data, 'Response should have error field' );

		$error = $data['error'];
		$this->assertArrayHasKey( 'code', $error, 'Error should have code' );
		$this->assertArrayHasKey( 'message', $error, 'Error should have message' );
		$this->assertStringContains( 'not found', $error['message'], 'Error message should mention tool not found' );
	}

	/**
	 * Test error handling for invalid method.
	 */
	public function test_error_handling_invalid_method() {
		$response = $this->make_mcp_request( 'invalid/method' );

		// Should return error response.
		$this->assertEquals( 200, $response->get_status(), 'REST response should be 200 (error is in content)' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'error', $data, 'Response should have error field' );

		$error = $data['error'];
		$this->assertArrayHasKey( 'code', $error, 'Error should have code' );
		$this->assertArrayHasKey( 'message', $error, 'Error should have message' );
	}

	/**
	 * Test error handling for malformed requests.
	 */
	public function test_error_handling_malformed_request() {
		$request = new \WP_REST_Request( 'POST', '/woocommerce/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( 'X-MCP-API-Key', $this->api_key );

		// Send malformed JSON (missing method).
		$request->set_body( wp_json_encode( array( 'params' => array() ) ) );

		$response = $this->server->dispatch( $request );

		// Should return error response.
		$this->assertEquals( 400, $response->get_status(), 'Should return 400 for malformed request' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be array' );
		$this->assertArrayHasKey( 'code', $data, 'Error response should have code' );
		$this->assertArrayHasKey( 'message', $data, 'Error response should have message' );
	}
}