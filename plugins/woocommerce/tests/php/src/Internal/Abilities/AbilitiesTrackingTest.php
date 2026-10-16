<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesTracking;
use WC_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;

/**
 * Tests for the AbilitiesTracking class.
 */
class AbilitiesTrackingTest extends WC_Unit_Test_Case {

	private const ABILITY_ID = 'woocommerce/tracking-test';

	private const INNER_ABILITY_ID = 'woocommerce/tracking-test-inner';

	private const UNTRACKED_ABILITY_ID = 'tracking-test/other';

	private const SERVER_KEYS = array( 'REQUEST_SCHEME', 'HTTP_HOST', 'REQUEST_URI', 'HTTP_REFERER', 'HTTP_MCP_PROTOCOL_VERSION', 'HTTP_MCP_SESSION_ID' );

	/**
	 * The System Under Test, with events captured instead of sent.
	 *
	 * @var AbilitiesTracking&object{recorded: array}
	 */
	private $sut;

	/**
	 * What the test ability's execute callback returns, or a callable that produces it.
	 *
	 * @var mixed
	 */
	private $execute_result = array( 'ok' => true );

	/**
	 * Value the test ability's permission callback returns.
	 *
	 * @var mixed
	 */
	private $permission = true;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private static int $administrator_id;

	/**
	 * Original action counts, restored on tear down.
	 *
	 * @var array<string, int|null>
	 */
	private array $original_action_counts = array();

	/**
	 * Original $_SERVER values, restored on tear down.
	 *
	 * @var array<string, mixed>
	 */
	private array $original_server = array();

	/**
	 * Create shared fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory Factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API is not available.' );
		}

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}
		// The Abilities API registry requires init to have fired.
		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		foreach ( self::SERVER_KEYS as $key ) {
			$this->original_server[ $key ] = $_SERVER[ $key ] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		wp_set_current_user( self::$administrator_id );
		$this->register_test_abilities();

		update_option( 'woocommerce_allow_tracking', 'yes' );

		$this->sut = new class() extends AbilitiesTracking {
			/**
			 * Captured Tracks properties.
			 *
			 * @var array
			 */
			public array $recorded = array();

			/**
			 * Whether to behave as a long-running process.
			 *
			 * @var bool
			 */
			public bool $long_running = false;

			/**
			 * Capture the event instead of sending it.
			 *
			 * @param array $properties Tracks properties.
			 */
			protected function send_event( array $properties ): void {
				$this->recorded[] = $properties;
			}

			/**
			 * Report the configured process type.
			 *
			 * @return bool
			 */
			protected function is_long_running_process(): bool {
				return $this->long_running;
			}
		};
		$this->sut->handle_init();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		try {
			// The abilities registry is a singleton that outlives the test.
			foreach ( array( self::ABILITY_ID, self::INNER_ABILITY_ID, self::UNTRACKED_ABILITY_ID ) as $ability_id ) {
				if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
					wp_unregister_ability( $ability_id );
				}
			}

			foreach ( $this->original_action_counts as $action => $original_count ) {
				if ( null === $original_count ) {
					unset( $wp_actions[ $action ] );
				} else {
					$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}

			foreach ( $this->original_server as $key => $value ) {
				if ( null === $value ) {
					unset( $_SERVER[ $key ] );
				} else {
					$_SERVER[ $key ] = $value;
				}
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should record a successful MCP call with normalized properties.
	 */
	public function test_records_successful_mcp_call(): void {
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded, 'One event should be recorded per call' );
		$event = $this->sut->recorded[0];
		$this->assertSame( 'wcadmin_ability_called', $event['_en'] );
		$this->assertSame( 'woocommerce_tracking_test', $event['ability_name'] );
		$this->assertSame( 'success', $event['outcome'] );
		$this->assertSame( 'none', $event['error_code'] );
		$this->assertSame( 'mcp', $event['channel'] );
		$this->assertSame( 'none', $event['external_client'], 'Without an MCP session the client is unknown' );
		$this->assertIsInt( $event['duration_ms'] );
		$this->assertContains( $event['is_test'], array( 'true', 'false' ) );
		$this->assertArrayNotHasKey( 'error', $event, 'A property named error makes Tracks drop the event' );
	}

	/**
	 * @testdox Should not send ability input, including input carried in the request URL or referrer.
	 */
	public function test_does_not_send_ability_input(): void {
		$_SERVER['REQUEST_SCHEME'] = 'https';
		$_SERVER['HTTP_HOST']      = 'example.com';
		$_SERVER['REQUEST_URI']    = '/wp-json/wp-abilities/v1/abilities/woocommerce/tracking-test/run?input[search]=customer@example.com';
		$_SERVER['HTTP_REFERER']   = 'https://example.com/?search=customer@example.com';
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute( array( 'search' => 'customer@example.com' ) );
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertArrayNotHasKey( '_dl', $this->sut->recorded[0] );
		$this->assertArrayNotHasKey( '_dr', $this->sut->recorded[0] );
		$this->assertStringNotContainsString( 'customer@example.com', (string) wp_json_encode( $this->sut->recorded[0] ) );
	}

	/**
	 * @testdox Should record calls made through the core Abilities API run endpoint as the rest channel.
	 */
	public function test_records_rest_run_endpoint_call(): void {
		$response = rest_do_request( new WP_REST_Request( 'POST', '/wp-abilities/v1/abilities/' . self::ABILITY_ID . '/run' ) );
		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( 200, $response->get_status(), 'The ability should run through REST' );
		$this->assertCount( 2, $this->sut->recorded );
		$this->assertSame( 'rest', $this->sut->recorded[0]['channel'] );
		$this->assertSame( 'wp_abilities', $this->sut->recorded[0]['rest_namespace'] );
		$this->assertSame( 'success', $this->sut->recorded[0]['outcome'] );
		$this->assertSame( 'other', $this->sut->recorded[1]['channel'], 'The direct call after the REST request is not a REST call' );
		$this->assertSame( 'none', $this->sut->recorded[1]['rest_namespace'] );
	}

	/**
	 * @testdox Should record run requests rejected by the Abilities REST API before execution.
	 */
	public function test_records_rest_rejection_before_execution(): void {
		$this->permission = false;

		$response = rest_do_request( new WP_REST_Request( 'POST', '/wp-abilities/v1/abilities/' . self::ABILITY_ID . '/run' ) );
		$this->sut->handle_shutdown();

		$this->assertGreaterThanOrEqual( 400, $response->get_status() );
		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'rest', $this->sut->recorded[0]['channel'] );
		$this->assertSame( 'error', $this->sut->recorded[0]['outcome'] );
		$this->assertNotSame( 'none', $this->sut->recorded[0]['error_code'] );
		$this->assertArrayNotHasKey( 'duration_ms', $this->sut->recorded[0] );
	}

	/**
	 * @testdox Should record a REST run request that fails during execution only once.
	 */
	public function test_records_rest_execution_failure_once(): void {
		$this->execute_result = new WP_Error( 'tracking_test_failure' );

		rest_do_request( new WP_REST_Request( 'POST', '/wp-abilities/v1/abilities/' . self::ABILITY_ID . '/run' ) );
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'error', $this->sut->recorded[0]['outcome'] );
	}

	/**
	 * @testdox Should record calls inside other REST routes with the route namespace only.
	 */
	public function test_records_namespace_of_other_rest_routes(): void {
		$this->register_route_that_runs_ability( 'tracking-test/v1', '/orders/(?P<id>\d+)', 'GET' );

		rest_do_request( new WP_REST_Request( 'GET', '/tracking-test/v1/orders/123' ) );
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'other', $this->sut->recorded[0]['channel'] );
		$this->assertSame( 'tracking_test', $this->sut->recorded[0]['rest_namespace'] );
	}

	/**
	 * @testdox Should record known MCP clients from request metadata, and other clients as other.
	 * @testWith ["Claude Code", "claude_code"]
	 *           ["claude-ai", "claude"]
	 *           ["cursor-vscode", "cursor"]
	 *           ["Visual Studio Code", "vscode"]
	 *           ["openai-mcp", "chatgpt"]
	 *           ["Jane's laptop agent", "other"]
	 *
	 * @param string $client_name Client name sent by the MCP client.
	 * @param string $expected    Recorded external_client.
	 */
	public function test_records_known_mcp_clients_from_request_metadata( string $client_name, string $expected ): void {
		$_SERVER['HTTP_MCP_PROTOCOL_VERSION'] = '2026-07-28';
		$this->register_route_that_runs_ability( 'tracking-mcp/v1', '/mcp', 'POST' );
		$request = new WP_REST_Request( 'POST', '/tracking-mcp/v1/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			(string) wp_json_encode(
				array(
					'jsonrpc' => '2.0',
					'id'      => 1,
					'method'  => 'tools/call',
					'params'  => array(
						'name'  => 'products-query',
						'_meta' => array( 'io.modelcontextprotocol/clientInfo' => array( 'name' => $client_name ) ),
					),
				)
			)
		);

		rest_do_request( $request );
		$this->sut->handle_shutdown();

		$this->assertSame( 'mcp', $this->sut->recorded[0]['channel'] );
		$this->assertSame( $expected, $this->sut->recorded[0]['external_client'] );
	}

	/**
	 * @testdox Should read the MCP client from the MCP Adapter session when the request has no client metadata.
	 */
	public function test_records_mcp_client_from_adapter_session(): void {
		if ( ! class_exists( 'WP\MCP\Transport\Infrastructure\SessionManager' ) ) {
			class_alias( FakeMcpSessionManager::class, 'WP\MCP\Transport\Infrastructure\SessionManager' );
		}
		if ( ! is_a( 'WP\MCP\Transport\Infrastructure\SessionManager', FakeMcpSessionManager::class, true ) ) {
			$this->markTestSkipped( 'The MCP Adapter plugin is loaded.' );
		}
		$_SERVER['HTTP_MCP_SESSION_ID'] = 'session-1';
		FakeMcpSessionManager::$sessions[ self::$administrator_id ]['session-1'] = array( 'client_params' => array( 'clientInfo' => array( 'name' => 'claude-code' ) ) );

		try {
			wp_get_ability( self::ABILITY_ID )->execute();
		} finally {
			FakeMcpSessionManager::$sessions = array();
		}
		$this->sut->handle_shutdown();

		$this->assertSame( 'claude_code', $this->sut->recorded[0]['external_client'] );
	}

	/**
	 * @testdox Should detect MCP requests from standard MCP HTTP headers, whatever server handles them.
	 * @testWith ["HTTP_MCP_PROTOCOL_VERSION", "2025-11-25"]
	 *           ["HTTP_MCP_SESSION_ID", "1868a90c"]
	 *
	 * @param string $header Server key of the header.
	 * @param string $value  Header value.
	 */
	public function test_detects_mcp_requests_from_headers( string $header, string $value ): void {
		$_SERVER[ $header ] = $value;

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( 'mcp', $this->sut->recorded[0]['channel'] );
	}

	/**
	 * @testdox Should record whether the call was authenticated with an application password.
	 */
	public function test_records_application_password_authentication(): void {
		global $wp_rest_application_password_uuid;

		$ability = wp_get_ability( self::ABILITY_ID );
		$ability->execute();
		$wp_rest_application_password_uuid = 'f5bd2b1a-0000-4000-8000-000000000000'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		try {
			$ability->execute();
		} finally {
			$wp_rest_application_password_uuid = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		$this->sut->handle_shutdown();

		$this->assertSame( array( 'false', 'true' ), array_column( $this->sut->recorded, 'uses_app_password' ) );
	}

	/**
	 * @testdox Should record WordPress 7.1+ failure outcomes with their error codes.
	 * @dataProvider provide_wordpress_7_1_failures
	 *
	 * @param string $failure       Failure to cause.
	 * @param string $expected_code Expected error code.
	 * @param bool   $executed      Whether the call reached execution, and so reports a duration.
	 */
	public function test_records_wordpress_7_1_failures( string $failure, string $expected_code, bool $executed ): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		switch ( $failure ) {
			case 'execute_error':
				$this->execute_result = new WP_Error( 'tracking_test_failure', 'Failed for customer@example.com' );
				break;
			case 'permission_denied':
				$this->permission = false;
				break;
			case 'input_rejected_with_false':
				add_filter( 'wp_ability_validate_input', '__return_false' );
				break;
			case 'output_rejected_with_error':
				add_filter( 'wp_ability_validate_output', fn() => new WP_Error( 'tracking_test_bad_output' ) );
				break;
			case 'short_circuit_error':
				add_filter( 'wp_pre_execute_ability', fn() => new WP_Error( 'tracking_test_rate_limited' ) );
				break;
		}
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'error', $this->sut->recorded[0]['outcome'] );
		$this->assertSame( $expected_code, $this->sut->recorded[0]['error_code'] );
		$this->assertSame( $executed, array_key_exists( 'duration_ms', $this->sut->recorded[0] ), 'Only executed calls report a duration' );
	}

	/**
	 * Failures WordPress 7.1+ reports through hooks, with the expected error code.
	 *
	 * @return array<string, array{string, string, bool}>
	 */
	public function provide_wordpress_7_1_failures(): array {
		return array(
			'execute callback error' => array( 'execute_error', 'tracking_test_failure', true ),
			'permission denied'      => array( 'permission_denied', 'ability_invalid_permissions', false ),
			'input filter false'     => array( 'input_rejected_with_false', 'ability_invalid_input', false ),
			'output filter error'    => array( 'output_rejected_with_error', 'tracking_test_bad_output', true ),
			'short-circuit error'    => array( 'short_circuit_error', 'tracking_test_rate_limited', false ),
		);
	}

	/**
	 * @testdox Should record a failure without a closing hook as unknown on shutdown.
	 */
	public function test_records_failure_without_closing_hook_as_unknown(): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		add_filter( 'wp_ability_normalize_input', fn() => new WP_Error( 'tracking_test_normalize' ) );
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'unknown', $this->sut->recorded[0]['error_code'] );
		$this->assertArrayNotHasKey( 'duration_ms', $this->sut->recorded[0] );
	}

	/**
	 * @testdox Should record a short-circuited call as a success.
	 */
	public function test_records_short_circuited_call(): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		add_filter( 'wp_pre_execute_ability', fn() => array( 'cached' => true ) );
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'success', $this->sut->recorded[0]['outcome'] );
	}

	/**
	 * @testdox Should record nested WooCommerce ability calls once each.
	 */
	public function test_records_nested_calls_once_each(): void {
		$this->execute_result = fn() => wp_get_ability( self::INNER_ABILITY_ID )->execute();
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$outcomes = array_column( $this->sut->recorded, 'outcome', 'ability_name' );
		ksort( $outcomes );
		$this->assertCount( 2, $this->sut->recorded );
		$this->assertSame(
			array(
				'woocommerce_tracking_test'       => 'success',
				'woocommerce_tracking_test_inner' => 'success',
			),
			$outcomes
		);
	}

	/**
	 * @testdox Should record both calls when a failed call is followed by a successful one.
	 */
	public function test_records_failed_call_followed_by_successful_call(): void {
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		$this->execute_result = new WP_Error( 'tracking_test_failure' );
		$ability->execute();
		$this->execute_result = array( 'ok' => true );
		$ability->execute();
		$this->sut->handle_shutdown();

		$outcomes = array_column( $this->sut->recorded, 'outcome' );
		sort( $outcomes );
		$this->assertSame( array( 'error', 'success' ), $outcomes );
	}

	/**
	 * @testdox Should fall back to the execution hooks available in WordPress 7.0.
	 */
	public function test_falls_back_to_wordpress_7_0_hooks(): void {
		foreach ( array( 'wp_ability_invoked', 'wp_pre_execute_ability', 'wp_ability_validate_input', 'wp_ability_permission_result', 'wp_ability_execute_result', 'wp_ability_validate_output' ) as $hook ) {
			remove_all_filters( $hook );
		}
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		$ability->execute();
		$this->execute_result = new WP_Error( 'tracking_test_failure' );
		$ability->execute();
		$this->sut->handle_shutdown();

		$events = array_column( $this->sut->recorded, null, 'outcome' );
		$this->assertCount( 2, $this->sut->recorded );
		$this->assertIsInt( $events['success']['duration_ms'] );
		$this->assertSame( 'unknown', $events['error']['error_code'], 'Failed executions fire no hook in WordPress 7.0' );
		$this->assertArrayNotHasKey( 'duration_ms', $events['error'] );
	}

	/**
	 * @testdox Should not record abilities outside the woocommerce namespace.
	 */
	public function test_ignores_abilities_outside_woocommerce_namespace(): void {
		$this->start_mcp_tool_call();

		wp_get_ability( self::UNTRACKED_ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( array(), $this->sut->recorded );
	}

	/**
	 * @testdox Should record direct PHP calls as the other channel.
	 */
	public function test_records_direct_php_calls(): void {
		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'other', $this->sut->recorded[0]['channel'] );
		$this->assertSame( 'none', $this->sut->recorded[0]['external_client'] );
	}

	/**
	 * @testdox Should not attach any hooks when usage tracking is disabled.
	 */
	public function test_does_not_attach_hooks_when_tracking_disabled(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		$sut = new AbilitiesTracking();

		$sut->handle_init();

		$this->assertGreaterThan( 0, $this->count_hooked_callbacks( $this->sut ), 'The enabled instance from setUp should be hooked' );
		$this->assertSame( 0, $this->count_hooked_callbacks( $sut ), 'Only the constructor init hook should be attached' );
	}

	/**
	 * @testdox Should cap the number of events recorded in one request.
	 */
	public function test_caps_events_per_request(): void {
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		for ( $i = 0; $i < 30; $i++ ) {
			$ability->execute();
		}
		$this->sut->handle_shutdown();

		$this->assertCount( 25, $this->sut->recorded );
	}

	/**
	 * @testdox Should send full buffers during long-running processes instead of dropping events.
	 */
	public function test_flushes_full_buffer_in_long_running_process(): void {
		$this->sut->long_running = true;
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		for ( $i = 0; $i < 30; $i++ ) {
			$ability->execute();
		}

		$this->assertCount( 25, $this->sut->recorded, 'The first full buffer should be sent before shutdown' );
		$this->sut->handle_shutdown();
		$this->assertCount( 30, $this->sut->recorded );
	}

	/**
	 * @testdox Should not record a call that turned usage tracking off.
	 */
	public function test_does_not_record_call_that_disables_tracking(): void {
		$this->execute_result = static function () {
			update_option( 'woocommerce_allow_tracking', 'no' );
			return array( 'ok' => true );
		};
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( array(), $this->sut->recorded );
	}

	/**
	 * @testdox Should record each call with the user who made it.
	 */
	public function test_records_user_context_of_each_call(): void {
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		$ability->execute();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$ability->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( array( 'administrator', 'shop_manager' ), array_column( $this->sut->recorded, 'role' ) );
	}

	/**
	 * Register a REST route whose callback runs the test ability.
	 *
	 * @param string $route_namespace Route namespace.
	 * @param string $route           Route.
	 * @param string $method          HTTP method.
	 */
	private function register_route_that_runs_ability( string $route_namespace, string $route, string $method ): void {
		$register_route = function () use ( $route_namespace, $route, $method ): void {
			register_rest_route(
				$route_namespace,
				$route,
				array(
					'methods'             => $method,
					'callback'            => fn() => wp_get_ability( self::ABILITY_ID )->execute(),
					'permission_callback' => '__return_true',
				)
			);
		};
		rest_get_server();
		add_action( 'rest_api_init', $register_route );
		do_action( 'rest_api_init', rest_get_server() );
	}

	/**
	 * Simulate the MCP Adapter starting a tool call in this request.
	 */
	private function start_mcp_tool_call(): void {
		apply_filters( 'mcp_adapter_pre_tool_call', array(), 'mcp-adapter/execute-ability', null, null );
	}

	/**
	 * Skip the test on WordPress versions without hooks for every ability outcome (before 7.1).
	 */
	private function skip_unless_wordpress_exposes_all_outcomes(): void {
		if ( ! class_exists( 'WP_Filter_Sentinel' ) ) {
			$this->markTestSkipped( 'WordPress 7.0 fires no hooks for failed ability calls.' );
		}
	}

	/**
	 * Count callbacks of an instance attached to any hook, except the constructor's init hook.
	 *
	 * @param object $instance The instance.
	 * @return int
	 */
	private function count_hooked_callbacks( object $instance ): int {
		global $wp_filter;

		$count = 0;
		foreach ( $wp_filter as $hook_name => $hook ) {
			foreach ( $hook->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( is_array( $callback['function'] ) && $instance === $callback['function'][0] && 'init' !== $hook_name ) {
						++$count;
					}
				}
			}
		}
		return $count;
	}

	/**
	 * Register the abilities used by these tests.
	 */
	private function register_test_abilities(): void {
		// Create the registry first; its creation fires wp_abilities_api_init, which would run the callback below twice.
		wp_get_abilities();

		if ( ! wp_has_ability_category( 'woocommerce' ) ) {
			$register_category = static function (): void {
				wp_register_ability_category(
					'woocommerce',
					array(
						'label'       => 'WooCommerce',
						'description' => 'Store management abilities.',
					)
				);
			};
			add_action( 'wp_abilities_api_categories_init', $register_category );
			do_action( 'wp_abilities_api_categories_init' );
			remove_action( 'wp_abilities_api_categories_init', $register_category );
		}

		$execute = function () {
			return is_callable( $this->execute_result ) ? ( $this->execute_result )() : $this->execute_result;
		};

		$register_abilities = function () use ( $execute ): void {
			$abilities = array(
				self::ABILITY_ID           => $execute,
				self::INNER_ABILITY_ID     => fn() => array( 'ok' => true ),
				self::UNTRACKED_ABILITY_ID => $execute,
			);
			foreach ( $abilities as $ability_id => $callback ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'               => 'Tracking test',
						'description'         => 'Ability used by the tracking tests.',
						'category'            => 'woocommerce',
						'input_schema'        => array(
							'type'    => 'object',
							'default' => array(),
						),
						'execute_callback'    => $callback,
						'permission_callback' => fn() => $this->permission,
						'meta'                => array( 'show_in_rest' => true ),
					)
				);
			}
		};
		add_action( 'wp_abilities_api_init', $register_abilities );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $register_abilities );
	}
}
