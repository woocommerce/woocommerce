<?php
/**
 * WCTracksObservabilityHandlerIntegrationTest class file.
 *
 * Full end-to-end integration tests that register real abilities and make
 * actual MCP tool calls through the complete MCP adapter stack.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Internal\MCP\WCTracksObservabilityHandler;
use WP\MCP\Core\McpServer;
use WP\MCP\Handlers\Initialize\InitializeHandler;
use WP\MCP\Handlers\Prompts\PromptsHandler;
use WP\MCP\Handlers\Resources\ResourcesHandler;
use WP\MCP\Handlers\System\SystemHandler;
use WP\MCP\Handlers\Tools\ToolsHandler;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Transport\Infrastructure\McpTransportContext;
use WP\MCP\Transport\Infrastructure\RequestRouter;

/**
 * Full integration tests for WCTracksObservabilityHandler.
 *
 * These tests register real WordPress abilities, create a real MCP server,
 * and make actual tool calls through the complete stack.
 */
class WCTracksObservabilityHandlerIntegrationTest extends \WC_Unit_Test_Case {

	/**
	 * Captured track events for verification.
	 *
	 * @var array
	 */
	private $captured_events = array();

	/**
	 * Original tracking option value.
	 *
	 * @var string
	 */
	private $original_tracking_option;

	/**
	 * Test abilities registered for cleanup.
	 *
	 * @var array
	 */
	private $test_abilities = array();

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		// Load the Abilities API if not already loaded.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$bootstrap_file = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $bootstrap_file ) ) {
				require $bootstrap_file;
			}
		}

		// Abilities API requires init action to have fired.
		if ( ! isset( $wp_actions['init'] ) ) {
			$wp_actions['init'] = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// Bootstrap the MCP Adapter.
		if ( ! class_exists( 'WP\\MCP\\Core\\McpServer' ) ) {
			$mcp_bootstrap = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/mcp-adapter/includes/Autoloader.php';
			if ( file_exists( $mcp_bootstrap ) ) {
				require_once $mcp_bootstrap;
				if ( class_exists( 'WP\\MCP\\Autoloader' ) ) {
					\WP\MCP\Autoloader::autoload();
				}
			}
		}

		// Store original tracking option.
		$this->original_tracking_option = get_option( 'woocommerce_allow_tracking', 'no' );

		// Reset captured events.
		$this->captured_events = array();

		// Hook into WC_Tracks to capture events.
		add_filter( 'woocommerce_tracks_event_properties', array( $this, 'capture_track_event' ), 10, 2 );

		// Register test abilities directly into the registry.
		// This bypasses the init action timing requirement for testing purposes.
		$this->register_test_abilities_directly();
	}

	/**
	 * Register test abilities directly into the registry.
	 * Bypasses the init action requirement for testing purposes.
	 */
	private function register_test_abilities_directly(): void {
		if ( ! class_exists( 'WP_Abilities_Registry' ) || ! class_exists( 'WP_Ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available' );
			return;
		}

		$registry = \WP_Abilities_Registry::get_instance();

		// Register echo test ability directly.
		$echo_id = 'woocommerce/observability-test-echo';
		if ( ! $registry->is_registered( $echo_id ) ) {
			$ability = new \WP_Ability(
				$echo_id,
				array(
					'label'               => 'Test Echo',
					'description'         => 'Echoes back input for testing observability',
					'category'            => 'woocommerce-rest',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'message' => array(
								'type'        => 'string',
								'description' => 'Message to echo',
							),
							'count'   => array(
								'type'        => 'integer',
								'description' => 'Repeat count',
							),
						),
					),
					'execute_callback'    => function ( array $input ) {
						return array(
							'echoed'  => $input['message'] ?? 'no message',
							'count'   => $input['count'] ?? 1,
							'success' => true,
						);
					},
					'permission_callback' => function () {
						return true;
					},
					'meta'                => array(
						'mcp' => array( 'public' => true ),
					),
				)
			);
			// Use reflection to bypass the init action check.
			$this->register_ability_via_reflection( $registry, $echo_id, $ability );
			$this->test_abilities[] = $echo_id;
		}

		// Register product test ability directly.
		$product_id = 'woocommerce/observability-test-product';
		if ( ! $registry->is_registered( $product_id ) ) {
			$ability = new \WP_Ability(
				$product_id,
				array(
					'label'               => 'Test Product',
					'description'         => 'Simulates product creation for testing',
					'category'            => 'woocommerce-rest',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'name'          => array(
								'type'        => 'string',
								'description' => 'Product name',
							),
							'regular_price' => array(
								'type'        => 'string',
								'description' => 'Regular price',
							),
							'sku'           => array(
								'type'        => 'string',
								'description' => 'SKU',
							),
						),
						'required'   => array( 'name', 'regular_price' ),
					),
					'execute_callback'    => function ( array $input ) {
						return array(
							'id'      => 999,
							'name'    => $input['name'],
							'price'   => $input['regular_price'],
							'sku'     => $input['sku'] ?? null,
							'success' => true,
						);
					},
					'permission_callback' => function () {
						return true;
					},
					'meta'                => array(
						'mcp' => array( 'public' => true ),
					),
				)
			);
			$this->register_ability_via_reflection( $registry, $product_id, $ability );
			$this->test_abilities[] = $product_id;
		}
	}

	/**
	 * Register an ability directly into the registry via reflection.
	 *
	 * @param \WP_Abilities_Registry $registry The abilities registry.
	 * @param string                 $id       The ability ID.
	 * @param \WP_Ability            $ability  The ability object.
	 */
	private function register_ability_via_reflection( $registry, string $id, $ability ): void {
		$reflection = new \ReflectionClass( $registry );
		$property   = $reflection->getProperty( 'registered_abilities' );
		$property->setAccessible( true );
		$abilities        = $property->getValue( $registry );
		$abilities[ $id ] = $ability;
		$property->setValue( $registry, $abilities );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Restore original tracking option.
		update_option( 'woocommerce_allow_tracking', $this->original_tracking_option );

		// Remove filter.
		remove_filter( 'woocommerce_tracks_event_properties', array( $this, 'capture_track_event' ), 10 );

		// Unregister test abilities via reflection.
		if ( class_exists( 'WP_Abilities_Registry' ) ) {
			$registry   = \WP_Abilities_Registry::get_instance();
			$reflection = new \ReflectionClass( $registry );
			$property   = $reflection->getProperty( 'registered_abilities' );
			$property->setAccessible( true );
			$abilities = $property->getValue( $registry );

			foreach ( $this->test_abilities as $ability_id ) {
				unset( $abilities[ $ability_id ] );
			}

			$property->setValue( $registry, $abilities );
		}
		$this->test_abilities = array();

		parent::tearDown();
	}

	/**
	 * Capture track events for verification.
	 *
	 * @param array  $properties Event properties.
	 * @param string $event_name Event name.
	 * @return array
	 */
	public function capture_track_event( array $properties, string $event_name ): array {
		$this->captured_events[] = array(
			'event_name' => $event_name,
			'properties' => $properties,
		);
		return $properties;
	}

	/**
	 * Create an MCP server with real abilities and WCTracksObservabilityHandler.
	 *
	 * @param array $ability_ids Array of ability IDs to include.
	 * @return RequestRouter The request router for making tool calls.
	 */
	private function create_mcp_server( array $ability_ids ): RequestRouter {
		// Create the MCP server with real abilities.
		$server = new McpServer(
			'woocommerce-observability-test',
			'woocommerce',
			'/mcp',
			'WooCommerce Observability Test Server',
			'Test server for observability integration tests',
			'1.0.0',
			array(), // No transports needed for direct testing.
			ErrorLogMcpErrorHandler::class,
			WCTracksObservabilityHandler::class,
			$ability_ids,
			array(), // No resources.
			array(), // No prompts.
		);

		// Create handlers with the real server.
		$error_handler         = new ErrorLogMcpErrorHandler();
		$observability_handler = new WCTracksObservabilityHandler();
		$tools_handler         = new ToolsHandler( $server );
		$resources_handler     = new ResourcesHandler( $server );
		$prompts_handler       = new PromptsHandler( $server );
		$system_handler        = new SystemHandler( $server );
		$initialize_handler    = new InitializeHandler( $server );

		// Create transport context.
		$context = new McpTransportContext(
			array(
				'mcp_server'            => $server,
				'initialize_handler'    => $initialize_handler,
				'tools_handler'         => $tools_handler,
				'resources_handler'     => $resources_handler,
				'prompts_handler'       => $prompts_handler,
				'system_handler'        => $system_handler,
				'observability_handler' => $observability_handler,
				'error_handler'         => $error_handler,
			)
		);

		return $context->request_router;
	}

	/**
	 * Test that a real tool call is tracked with correct properties.
	 */
	public function test_real_tool_call_is_tracked(): void {
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Create MCP server with test ability.
		$router = $this->create_mcp_server( array( 'woocommerce/observability-test-echo' ) );

		// Make a real tool call (tool name format: ability ID with / replaced by -).
		$result = $router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-observability-test-echo',
				'arguments' => array(
					'message' => 'Hello World',
					'count'   => 3,
				),
			),
			1,
			'test-transport'
		);

		// Verify the tool executed successfully.
		$this->assertArrayHasKey( 'content', $result, 'Tool should return content' );
		$this->assertArrayNotHasKey( 'error', $result, 'Tool should not return error' );

		// Verify tracking event was captured.
		$this->assertNotEmpty( $this->captured_events, 'Should capture tracking event' );

		$event = $this->captured_events[0];
		$this->assertSame( 'wcadmin_mcp_tool_call', $event['event_name'] );
		$this->assertSame( 'woocommerce_observability_test_echo', $event['properties']['tool_name'] );
		$this->assertSame( 'success', $event['properties']['status'] );
		$this->assertArrayHasKey( 'duration_ms', $event['properties'] );
	}

	/**
	 * Test that parameter fields from real tool call are correctly tracked.
	 */
	public function test_real_tool_parameter_fields_are_tracked(): void {
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Create MCP server with product test ability.
		$router = $this->create_mcp_server( array( 'woocommerce/observability-test-product' ) );

		// Make a real tool call with multiple parameters.
		$result = $router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-observability-test-product',
				'arguments' => array(
					'name'          => 'Test Product',
					'regular_price' => '29.99',
					'sku'           => 'TEST-123',
				),
			),
			1,
			'test-transport'
		);

		// Verify tool executed.
		$this->assertArrayHasKey( 'content', $result );

		// Verify tracking with param_fields.
		$this->assertNotEmpty( $this->captured_events );

		$event        = $this->captured_events[0];
		$param_fields = explode( ',', $event['properties']['param_fields'] );

		$this->assertContains( 'name', $param_fields );
		$this->assertContains( 'regular_price', $param_fields );
		$this->assertContains( 'sku', $param_fields );
	}

	/**
	 * Test that calling unknown tool tracks error status.
	 */
	public function test_unknown_tool_call_tracks_error(): void {
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Create MCP server with a different ability.
		$router = $this->create_mcp_server( array( 'woocommerce/observability-test-echo' ) );

		// Call a tool that doesn't exist.
		$result = $router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-nonexistent-tool',
				'arguments' => array(),
			),
			1,
			'test-transport'
		);

		// Verify tracking event captures error.
		$this->assertNotEmpty( $this->captured_events );

		$event = $this->captured_events[0];
		$this->assertSame( 'wcadmin_mcp_tool_call', $event['event_name'] );
		$this->assertSame( 'error', $event['properties']['status'] );
	}

	/**
	 * Test that tracking is disabled when option is set to no.
	 */
	public function test_no_tracking_when_disabled(): void {
		// Disable tracking.
		update_option( 'woocommerce_allow_tracking', 'no' );

		// Create MCP server.
		$router = $this->create_mcp_server( array( 'woocommerce/observability-test-echo' ) );

		// Make a tool call.
		$router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-observability-test-echo',
				'arguments' => array( 'message' => 'test' ),
			),
			1,
			'test-transport'
		);

		// Verify no events captured.
		$this->assertEmpty( $this->captured_events, 'Should not track when disabled' );
	}

	/**
	 * Test that duration is tracked for real tool execution.
	 */
	public function test_duration_reflects_real_execution_time(): void {
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Create MCP server.
		$router = $this->create_mcp_server( array( 'woocommerce/observability-test-echo' ) );

		// Make a tool call.
		$router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-observability-test-echo',
				'arguments' => array(),
			),
			1,
			'test-transport'
		);

		// Verify duration is present and reasonable.
		$this->assertNotEmpty( $this->captured_events );
		$event = $this->captured_events[0];

		$this->assertArrayHasKey( 'duration_ms', $event['properties'] );
		$this->assertIsFloat( $event['properties']['duration_ms'] );
		$this->assertGreaterThan( 0, $event['properties']['duration_ms'] );
	}

	/**
	 * Test multiple real tool calls generate separate events.
	 */
	public function test_multiple_real_tool_calls_generate_separate_events(): void {
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Create MCP server with both test abilities.
		$router = $this->create_mcp_server(
			array(
				'woocommerce/observability-test-echo',
				'woocommerce/observability-test-product',
			)
		);

		// Make first tool call.
		$router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-observability-test-echo',
				'arguments' => array( 'message' => 'first' ),
			),
			1,
			'test-transport'
		);

		// Make second tool call.
		$router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-observability-test-product',
				'arguments' => array(
					'name'          => 'Product',
					'regular_price' => '10.00',
				),
			),
			2,
			'test-transport'
		);

		// Verify two separate events.
		$this->assertCount( 2, $this->captured_events );

		$this->assertSame( 'woocommerce_observability_test_echo', $this->captured_events[0]['properties']['tool_name'] );
		$this->assertSame( 'woocommerce_observability_test_product', $this->captured_events[1]['properties']['tool_name'] );
	}

	/**
	 * Test that tool result data is not included in tracking (privacy).
	 */
	public function test_tool_result_data_not_in_tracking(): void {
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Create MCP server.
		$router = $this->create_mcp_server( array( 'woocommerce/observability-test-echo' ) );

		// Make a tool call with sensitive-looking data.
		$router->route_request(
			'tools/call',
			array(
				'name'      => 'woocommerce-observability-test-echo',
				'arguments' => array(
					'message' => 'secret-password-123',
				),
			),
			1,
			'test-transport'
		);

		// Verify tracking doesn't contain argument values.
		$this->assertNotEmpty( $this->captured_events );
		$properties = $this->captured_events[0]['properties'];

		// Should only have field names, not values.
		$this->assertStringNotContainsString( 'secret-password-123', $properties['param_fields'] );
		$this->assertStringNotContainsString( 'secret-password-123', $properties['tool_name'] );
	}
}
