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

	private const LONG_ABILITY_ID = 'woocommerce/tracking-test-with-a-long-name-that-goes-past-sixty-four-characters';

	private const NO_SCHEMA_ABILITY_ID = 'woocommerce/tracking-test-no-schema';

	private const SERVER_KEYS = array( 'REQUEST_SCHEME', 'HTTP_HOST', 'REQUEST_URI', 'HTTP_REFERER', 'HTTP_MCP_PROTOCOL_VERSION', 'HTTP_MCP_SESSION_ID', 'A8C_PROXIED_REQUEST' );

	/**
	 * Hooks that exist only on WordPress 7.1. Removed from this instance to exercise the 7.0 path.
	 *
	 * @var string[]
	 */
	private const WORDPRESS_7_1_HOOKS = array(
		'wp_ability_invoked',
		'wp_pre_execute_ability',
		'wp_ability_normalize_input',
		'wp_ability_validate_input',
		'wp_ability_permission_result',
		'wp_ability_execute_result',
		'wp_ability_validate_output',
	);

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
	 * What the inner test ability's execute callback returns.
	 *
	 * @var mixed
	 */
	private $inner_result = array( 'ok' => true );

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

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init', 'rest_api_init' ) as $action ) {
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

		$this->sut = $this->create_sut();
		$this->sut->handle_init();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		try {
			if ( isset( $this->sut ) ) {
				$this->remove_instance_callbacks( $this->sut );
			}

			// The abilities registry is a singleton that outlives the test.
			foreach ( array( self::ABILITY_ID, self::INNER_ABILITY_ID, self::UNTRACKED_ABILITY_ID, self::LONG_ABILITY_ID, self::NO_SCHEMA_ABILITY_ID ) as $ability_id ) {
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
		$this->assertSame( 'woocommerce__tracking-test', $event['ability_name'] );
		$this->assertSame( 'success', $event['outcome'] );
		$this->assertSame( 'none', $event['error_code'] );
		$this->assertSame( 'mcp', $event['channel'] );
		$this->assertSame( 'none', $event['external_client'], 'Without an MCP session the client is unknown' );
		$this->assertIsInt( $event['duration_ms'] );
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
		$this->assertCount( 1, $this->sut->recorded, 'The direct call after the REST request is not an agent call' );
		$this->assertSame( 'rest', $this->sut->recorded[0]['channel'] );
		$this->assertSame( 'wp_abilities', $this->sut->recorded[0]['rest_namespace'] );
		$this->assertSame( 'success', $this->sut->recorded[0]['outcome'] );
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
		$this->assertSame( 'rest_ability_cannot_execute', $this->sut->recorded[0]['error_code'] );
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
	 * @testdox Should not record calls inside other REST routes.
	 */
	public function test_ignores_calls_inside_other_rest_routes(): void {
		$this->register_route_that_runs_ability( 'tracking-test/v1', '/orders/(?P<id>\d+)', 'GET' );

		rest_do_request( new WP_REST_Request( 'GET', '/tracking-test/v1/orders/123' ) );
		$this->sut->handle_shutdown();

		$this->assertSame( array(), $this->sut->recorded );
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
	 * @testdox Should detect MCP tool calls from a JSON-RPC body when the client sends no MCP headers.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $batch Whether the tool call is sent in a JSON-RPC batch.
	 */
	public function test_detects_mcp_tool_calls_from_json_rpc_body( bool $batch ): void {
		$this->register_route_that_runs_ability( 'tracking-test/v1', '/mcp', 'POST' );
		$message = array(
			'jsonrpc' => '2.0',
			'id'      => 1,
			'method'  => 'tools/call',
			'params'  => array( 'name' => 'tracking-test' ),
		);
		$request = new WP_REST_Request( 'POST', '/tracking-test/v1/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $batch ? array( $message ) : $message ) );

		rest_do_request( $request );
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'mcp', $this->sut->recorded[0]['channel'] );
	}

	/**
	 * @testdox Should record MCP tool calls the MCP Adapter refuses on its permission check before execution, and no other permission checks.
	 * @testWith ["tools/call", 1]
	 *           ["tools/list", 0]
	 *
	 * @param string $method   JSON-RPC method of the MCP request.
	 * @param int    $expected Expected number of events.
	 */
	public function test_records_mcp_calls_refused_before_execution( string $method, int $expected ): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		// An MCP request by its headers, so only the JSON-RPC method tells a tool call from a listing.
		$_SERVER['HTTP_MCP_PROTOCOL_VERSION'] = '2025-11-25';
		$this->permission                     = false;
		$request                              = new WP_REST_Request( 'POST', '/mcp/mcp-adapter-default-server' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			(string) wp_json_encode(
				array(
					'jsonrpc' => '2.0',
					'id'      => 1,
					'method'  => $method,
				)
			)
		);
		$this->sut->handle_rest_request_before_callbacks( null, null, $request );

		wp_get_ability( self::ABILITY_ID )->check_permissions();
		$this->sut->handle_shutdown();

		$this->assertCount( $expected, $this->sut->recorded );
		if ( 0 === $expected ) {
			return;
		}
		$this->assertSame( 'error', $this->sut->recorded[0]['outcome'] );
		$this->assertSame( 'ability_invalid_permissions', $this->sut->recorded[0]['error_code'] );
		$this->assertSame( 'mcp', $this->sut->recorded[0]['channel'] );
	}

	/**
	 * @testdox Should not report Jetpack authentication for signature arguments Jetpack didn't verify.
	 */
	public function test_does_not_trust_unverified_jetpack_signature_arguments(): void {
		$this->start_mcp_tool_call();
		$_GET['token']     = 'abc:1:2';
		$_GET['signature'] = 'forged';

		try {
			wp_get_ability( self::ABILITY_ID )->execute();
		} finally {
			unset( $_GET['token'], $_GET['signature'] );
		}
		$this->sut->handle_shutdown();

		$this->assertSame( 'cookie', $this->sut->recorded[0]['auth'] );
	}

	/**
	 * @testdox Should record how the caller authenticated.
	 */
	public function test_records_auth_method(): void {
		global $wp_rest_application_password_uuid;

		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );
		$ability->execute();
		$wp_rest_application_password_uuid = 'f5bd2b1a-0000-4000-8000-000000000000'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		try {
			$ability->execute();
		} finally {
			$wp_rest_application_password_uuid = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		$this->sut->handle_shutdown();

		$this->assertSame( array( 'cookie', 'application_password' ), array_column( $this->sut->recorded, 'auth' ) );
	}

	/**
	 * @testdox Should record whether the call came through the Automattic proxy.
	 */
	public function test_records_is_a11n(): void {
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );
		$ability->execute();
		$_SERVER['A8C_PROXIED_REQUEST'] = '1';
		$ability->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( array( 'false', 'true' ), array_column( $this->sut->recorded, 'is_a11n' ) );
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
	 * @testdox Should record only slug-like error codes.
	 * @testWith ["tracking_test_failure", "tracking_test_failure"]
	 *           ["Tracking-Test-Failure", "tracking_test_failure"]
	 *           ["customer@example.com", "other"]
	 *           ["order 1234 for Jane", "other"]
	 *
	 * @param string $code     Error code returned by the ability.
	 * @param string $expected Recorded error code.
	 */
	public function test_records_only_slug_like_error_codes( string $code, string $expected ): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		$this->execute_result = new WP_Error( $code );
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( $expected, $this->sut->recorded[0]['error_code'] );
	}

	/**
	 * @testdox Should record an input-normalization error with its error code.
	 */
	public function test_records_normalize_input_error(): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		add_filter( 'wp_ability_normalize_input', fn() => new WP_Error( 'tracking_test_normalize' ) );
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertCount( 1, $this->sut->recorded );
		$this->assertSame( 'tracking_test_normalize', $this->sut->recorded[0]['error_code'] );
		$this->assertArrayNotHasKey( 'duration_ms', $this->sut->recorded[0] );
	}

	/**
	 * @testdox Should record a missing input schema as unknown, because that failure has no closing hook.
	 */
	public function test_records_missing_input_schema_as_unknown(): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		$this->start_mcp_tool_call();

		wp_get_ability( self::NO_SCHEMA_ABILITY_ID )->execute( array( 'raw' => true ) );
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
				'woocommerce__tracking-test'       => 'success',
				'woocommerce__tracking-test-inner' => 'success',
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
		$this->remove_instance_callbacks( $this->sut, self::WORDPRESS_7_1_HOOKS );
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
	 * @testdox Should not record calls made during cron.
	 */
	public function test_ignores_cron_calls(): void {
		add_filter( 'wp_doing_cron', '__return_true' );
		wp_set_current_user( 0 );

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( array(), $this->sut->recorded );
	}

	/**
	 * @testdox Should finish the response before sending events, unless there are none, it runs under WP-CLI, or a filter says not to.
	 */
	public function test_finishes_the_response_before_sending_events(): void {
		$this->start_mcp_tool_call();
		$this->sut->handle_shutdown();
		$this->assertSame( array(), $this->sut->finished_after, 'There was nothing to send' );
		$this->assertSame( array(), $this->sut->pixels_sent_after, 'Nothing to send' );

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();
		$this->assertSame( array( 0 ), $this->sut->finished_after, 'Once, before the event was sent' );
		$this->assertSame( array( 1 ), $this->sut->pixels_sent_after, 'The pixels go out after the response is finished' );

		add_filter( 'woocommerce_ability_tracking_finish_response', '__return_false' );
		try {
			wp_get_ability( self::ABILITY_ID )->execute();
			$this->sut->handle_shutdown();
		} finally {
			remove_filter( 'woocommerce_ability_tracking_finish_response', '__return_false' );
		}
		$this->assertSame( array( 0 ), $this->sut->finished_after, 'The filter keeps the response open' );
		$this->assertSame( array( 1, 1 ), $this->sut->pixels_sent_after, 'The events are still sent' );

		$this->sut->long_running = true;
		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();
		$this->assertSame( array( 0 ), $this->sut->finished_after, 'No client waits under WP-CLI' );
	}

	/**
	 * @testdox Should not record calls or REST rejections from logged-out visitors.
	 */
	public function test_ignores_logged_out_visitors(): void {
		wp_set_current_user( 0 );

		$this->start_mcp_tool_call();
		wp_get_ability( self::ABILITY_ID )->execute();
		$this->permission = false;
		rest_do_request( new WP_REST_Request( 'POST', '/wp-abilities/v1/abilities/' . self::ABILITY_ID . '/run' ) );
		$this->sut->handle_shutdown();

		$this->assertSame( array(), $this->sut->recorded );
	}

	/**
	 * @testdox Should record a failed inner call as unknown when its outer call succeeds on WordPress 7.0.
	 */
	public function test_records_failed_inner_call_on_wordpress_7_0(): void {
		$this->remove_instance_callbacks( $this->sut, self::WORDPRESS_7_1_HOOKS );
		$this->inner_result   = new WP_Error( 'tracking_test_inner_failure' );
		$this->execute_result = function () {
			wp_get_ability( self::INNER_ABILITY_ID )->execute();
			return array( 'ok' => true );
		};
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$events = array_column( $this->sut->recorded, null, 'ability_name' );
		$this->assertCount( 2, $this->sut->recorded );
		$this->assertSame( 'success', $events['woocommerce__tracking-test']['outcome'] );
		$this->assertSame( 'unknown', $events['woocommerce__tracking-test-inner']['error_code'] );
	}

	/**
	 * @testdox Should keep the client and time of an MCP call whose failure is only recorded at shutdown.
	 */
	public function test_keeps_context_of_failures_recorded_at_shutdown(): void {
		$this->remove_instance_callbacks( $this->sut, self::WORDPRESS_7_1_HOOKS );
		$_SERVER['HTTP_MCP_PROTOCOL_VERSION'] = '2026-07-28';
		$this->execute_result                 = new WP_Error( 'tracking_test_failure' );
		$this->register_route_that_runs_ability( 'tracking-mcp/v1', '/mcp', 'POST' );
		$request = new WP_REST_Request( 'POST', '/tracking-mcp/v1/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( array( 'params' => array( '_meta' => array( 'io.modelcontextprotocol/clientInfo' => array( 'name' => 'claude-code' ) ) ) ) ) );

		$called_at = microtime( true ) * 1000;
		rest_do_request( $request );
		$returned_at = microtime( true ) * 1000;
		$this->sut->handle_shutdown();

		$this->assertSame( 'unknown', $this->sut->recorded[0]['error_code'] );
		$this->assertSame( 'claude_code', $this->sut->recorded[0]['external_client'], 'The client must be read while the request is active' );
		$this->assertGreaterThanOrEqual( floor( $called_at ), (float) $this->sut->recorded[0]['_ts'] );
		$this->assertLessThanOrEqual( ceil( $returned_at ), (float) $this->sut->recorded[0]['_ts'], 'The event should carry the time of the call, not of shutdown' );
	}

	/**
	 * @testdox Should keep the outer call's result when a recursive call fails during input normalization.
	 */
	public function test_keeps_outer_result_when_recursive_call_fails_without_closing_hook(): void {
		$this->skip_unless_wordpress_exposes_all_outcomes();
		$calls = 0;
		add_filter(
			'wp_ability_normalize_input',
			static function ( $input ) use ( &$calls ) {
				return 2 === ++$calls ? new WP_Error( 'tracking_test_normalize' ) : $input;
			}
		);
		$this->execute_result = function () {
			wp_get_ability( self::ABILITY_ID )->execute();
			return array( 'ok' => true );
		};
		$this->start_mcp_tool_call();

		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertEqualsCanonicalizing( array( 'none', 'tracking_test_normalize' ), array_column( $this->sut->recorded, 'error_code' ), 'The outer success and the inner normalization error should both be recorded' );
	}

	/**
	 * @testdox Should record long ability names in full.
	 */
	public function test_records_long_ability_names_in_full(): void {
		$this->start_mcp_tool_call();

		wp_get_ability( self::LONG_ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( 'woocommerce__tracking-test-with-a-long-name-that-goes-past-sixty-four-characters', $this->sut->recorded[0]['ability_name'] );
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
	 * @testdox Should not record direct PHP calls outside MCP, the Abilities REST API and WP-CLI.
	 */
	public function test_ignores_direct_php_calls(): void {
		wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( array(), $this->sut->recorded );
	}

	/**
	 * @testdox Should not attach any hooks on a single site when usage tracking is disabled.
	 */
	public function test_does_not_attach_hooks_when_tracking_disabled(): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'On multisite, hooks are always attached and consent is checked per call.' );
		}
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
		$this->assertSame( 5, $this->sut->recorded[24]['events_dropped'], 'The last event should report how many were dropped' );
		$this->assertArrayNotHasKey( 'events_dropped', $this->sut->recorded[0] );
	}

	/**
	 * @testdox Should keep closing hooks matched to their own call after many unclosed failures on WordPress 7.0.
	 */
	public function test_keeps_frames_matched_after_many_unclosed_failures(): void {
		$this->remove_instance_callbacks( $this->sut, self::WORDPRESS_7_1_HOOKS );
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		$this->execute_result = new WP_Error( 'tracking_test_failure' );
		for ( $i = 0; $i < 26; $i++ ) {
			$ability->execute();
		}
		$this->execute_result = array( 'ok' => true );
		$ability->execute();
		$this->sut->handle_shutdown();

		$outcomes = array_count_values( array_column( $this->sut->recorded, 'outcome' ) );
		$this->assertSame( 1, $outcomes['success'] ?? 0, 'The successful call should be recorded as the only success' );
		$this->assertSame( 2, end( $this->sut->recorded )['events_dropped'] );
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
	 * @testdox Should not treat a posted onboarding opt-in as consent.
	 */
	public function test_ignores_posted_onboarding_opt_in(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		$_POST['wc_tracker_checkbox'] = 'yes';
		$this->start_mcp_tool_call();

		try {
			wp_get_ability( self::ABILITY_ID )->execute();
			$this->sut->handle_shutdown();
		} finally {
			unset( $_POST['wc_tracker_checkbox'] );
		}

		$this->assertSame( array(), $this->sut->recorded );
	}

	/**
	 * @testdox Should record calls on an opted-in site of a network when the request started on an opted-out site.
	 */
	public function test_records_calls_after_switching_to_opted_in_site(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}
		$opted_in_site = self::factory()->blog->create();
		switch_to_blog( $opted_in_site );
		update_option( 'woocommerce_allow_tracking', 'yes' );
		restore_current_blog();
		update_option( 'woocommerce_allow_tracking', 'no' );
		remove_all_actions( 'mcp_adapter_pre_tool_call' );
		$sut = $this->create_sut();
		$sut->handle_init();
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		$ability->execute();
		switch_to_blog( $opted_in_site );
		try {
			$ability->execute();
		} finally {
			restore_current_blog();
		}
		$sut->handle_shutdown();

		$this->assertCount( 1, $sut->recorded, 'Only the call on the opted-in site should be recorded' );
	}

	/**
	 * @testdox Should not queue Tracks pixels for the unit-test user.
	 */
	public function test_does_not_queue_pixels_for_unit_test_users(): void {
		$queue = new \ReflectionProperty( \WC_Tracks_Client::class, 'pixel_batch_queue' );
		$queue->setAccessible( true );
		$before        = count( $queue->getValue() );
		$user          = wp_get_current_user();
		$cap_key       = $user->cap_key;
		$user->cap_key = 'wptests_capabilities';
		$tracker       = new AbilitiesTracking();
		$tracker->handle_init();

		try {
			$this->start_mcp_tool_call();
			wp_get_ability( self::ABILITY_ID )->execute();
			$this->sut->handle_shutdown();
			$tracker->handle_shutdown();
		} finally {
			$user->cap_key = $cap_key;
			$this->remove_instance_callbacks( $tracker );
		}

		$this->assertNotEmpty( $this->sut->recorded, 'The call should still be observed by the test double' );
		$this->assertCount( $before, $queue->getValue() );
	}

	/**
	 * @testdox Should keep sending later events when one pixel send throws.
	 */
	public function test_keeps_sending_after_one_event_fails(): void {
		$this->sut->throw_on_send = 1;
		$this->start_mcp_tool_call();
		$ability = wp_get_ability( self::ABILITY_ID );

		$ability->execute();
		$ability->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( 2, $this->sut->sends );
		$this->assertCount( 1, $this->sut->recorded );
	}

	/**
	 * @testdox Should not let a failing Tracks or consent filter break the ability call.
	 * @testWith ["woocommerce_tracks_event_properties"]
	 *           ["woocommerce_apply_tracking"]
	 *
	 * @param string $hook Filter that throws.
	 */
	public function test_tracking_failures_do_not_break_ability_calls( string $hook ): void {
		add_filter(
			$hook,
			static function () {
				throw new \RuntimeException( 'Broken filter.' );
			}
		);
		$this->start_mcp_tool_call();

		$result = wp_get_ability( self::ABILITY_ID )->execute();
		$this->sut->handle_shutdown();

		$this->assertSame( array( 'ok' => true ), $result );
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
	 * Create a tracker that captures events instead of sending them.
	 *
	 * @return AbilitiesTracking&object{recorded: array, long_running: bool}
	 */
	private function create_sut(): AbilitiesTracking {
		return new class() extends AbilitiesTracking {
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
			 * Send attempt that should throw, or 0 to send every event.
			 *
			 * @var int
			 */
			public int $throw_on_send = 0;

			/**
			 * How many sends have been attempted.
			 *
			 * @var int
			 */
			public int $sends = 0;

			/**
			 * Capture the event instead of sending it.
			 *
			 * @param array $properties Tracks properties.
			 */
			protected function send_event( array $properties ): void {
				++$this->sends;
				if ( $this->throw_on_send === $this->sends ) {
					throw new \RuntimeException( 'pixel failed' );
				}
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

			/**
			 * Never send real pixels from tests; note when they would be sent.
			 */
			protected function send_queued_pixels(): void {
				$this->pixels_sent_after[] = count( $this->finished_after );
			}

			/**
			 * How many events had been sent each time the response was finished.
			 *
			 * @var int[]
			 */
			public array $finished_after = array();

			/**
			 * Note when the response would be finished.
			 */
			protected function finish_response(): void {
				$this->finished_after[] = count( $this->recorded );
			}

			/**
			 * How many times the response had been finished each time the queued pixels were sent.
			 *
			 * @var int[]
			 */
			public array $pixels_sent_after = array();
		};
	}

	/**
	 * Register a REST route whose callback runs the test ability.
	 *
	 * @param string $route_namespace Route namespace.
	 * @param string $route           Route.
	 * @param string $method          HTTP method.
	 */
	private function register_route_that_runs_ability( string $route_namespace, string $route, string $method ): void {
		global $wp_actions;

		rest_get_server();
		// register_rest_route() expects rest_api_init to have fired; firing it again would re-register every route.
		$wp_actions['rest_api_init'] = max( 1, (int) ( $wp_actions['rest_api_init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		register_rest_route(
			$route_namespace,
			$route,
			array(
				'methods'             => $method,
				'callback'            => fn() => wp_get_ability( self::ABILITY_ID )->execute(),
				'permission_callback' => '__return_true',
			)
		);
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
	 * Remove one instance's callbacks so later tests keep theirs.
	 *
	 * @param object        $instance  The instance.
	 * @param string[]|null $only_hooks Limit to these hooks, or null for every hook.
	 */
	private function remove_instance_callbacks( object $instance, ?array $only_hooks = null ): void {
		global $wp_filter;

		$removals = array();
		foreach ( $wp_filter as $hook_name => $hook ) {
			if ( null !== $only_hooks && ! in_array( $hook_name, $only_hooks, true ) ) {
				continue;
			}
			foreach ( $hook->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( is_array( $callback['function'] ) && $instance === $callback['function'][0] ) {
						$removals[] = array( $hook_name, $callback['function'], $priority );
					}
				}
			}
		}
		foreach ( $removals as $removal ) {
			remove_filter( $removal[0], $removal[1], $removal[2] );
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
				self::INNER_ABILITY_ID     => fn() => $this->inner_result,
				self::UNTRACKED_ABILITY_ID => $execute,
				self::LONG_ABILITY_ID      => $execute,
				self::NO_SCHEMA_ABILITY_ID => static fn() => array( 'ok' => true ),
			);
			foreach ( $abilities as $ability_id => $callback ) {
				$args = array(
					'label'               => 'Tracking test',
					'description'         => 'Ability used by the tracking tests.',
					'category'            => 'woocommerce',
					'execute_callback'    => $callback,
					'permission_callback' => fn() => $this->permission,
					'meta'                => array( 'show_in_rest' => true ),
				);
				if ( self::NO_SCHEMA_ABILITY_ID !== $ability_id ) {
					$args['input_schema'] = array(
						'type'    => 'object',
						'default' => array(),
					);
				}
				wp_register_ability( $ability_id, $args );
			}
		};
		add_action( 'wp_abilities_api_init', $register_abilities );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $register_abilities );
	}
}
