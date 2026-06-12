<?php
/**
 * Tests for the WC HPOS CLI runner, specifically `wp wc hpos status`.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\CLIRunner;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\LegacyDataHandler;
use Automattic\WooCommerce\Internal\DataStores\Orders\PostsToOrdersMigrationController;
use WP_CLI;

/**
 * Class CLIRunnerTest.
 */
class CLIRunnerTest extends \WC_Unit_Test_Case {

	/**
	 * Captured log messages.
	 *
	 * @var string[]
	 */
	private $logs = array();

	/**
	 * Set up the test: register static mocks for WP_CLI so we can capture log output.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->logs = array();
		$this->register_legacy_proxy_static_mocks(
			array(
				WP_CLI::class => array(
					'log'     => function ( $message ) {
						$this->logs[] = (string) $message;
					},
					'success' => function ( $message ) {
						$this->logs[] = (string) $message;
					},
					'warning' => function ( $message ) {
						$this->logs[] = (string) $message;
					},
				),
			)
		);
	}

	/**
	 * Clean up: reset container replacements so the LegacyDataHandler mock
	 * registered in build_runner() does not leak into later tests in the run.
	 */
	public function tearDown(): void {
		$this->reset_container_replacements();
		parent::tearDown();
	}

	/**
	 * Build a CLIRunner with mocked controller, synchronizer, and legacy handler.
	 *
	 * @param bool $hpos_enabled      Whether HPOS is enabled.
	 * @param bool $data_sync_enabled Whether data sync (compatibility mode) is enabled.
	 * @param int  $unsynced          Unsynced orders count.
	 * @param bool $is_authoritative  Whether the custom orders table is authoritative.
	 * @param int  $cleanup_count     Cleanup count returned by the legacy handler.
	 *
	 * @return CLIRunner
	 */
	private function build_runner( bool $hpos_enabled, bool $data_sync_enabled, int $unsynced, bool $is_authoritative, int $cleanup_count = 0 ): CLIRunner {
		$controller = $this->createMock( CustomOrdersTableController::class );
		$controller->method( 'custom_orders_table_usage_is_enabled' )->willReturn( $hpos_enabled );

		$synchronizer = $this->createMock( DataSynchronizer::class );
		$synchronizer->method( 'data_sync_is_enabled' )->willReturn( $data_sync_enabled );
		$synchronizer->method( 'get_current_orders_pending_sync_count' )->willReturn( $unsynced );
		$synchronizer->method( 'custom_orders_table_is_authoritative' )->willReturn( $is_authoritative );

		$migrator = $this->createMock( PostsToOrdersMigrationController::class );

		$legacy_handler = $this->createMock( LegacyDataHandler::class );
		$legacy_handler->method( 'count_orders_for_cleanup' )->willReturn( $cleanup_count );
		wc_get_container()->replace( LegacyDataHandler::class, $legacy_handler );

		$runner = new CLIRunner();
		$runner->init( $controller, $synchronizer, $migrator );

		return $runner;
	}

	/**
	 * @testdox Default text output renders four log lines in the established format.
	 */
	public function test_status_default_text_output(): void {
		$runner = $this->build_runner( true, true, 0, true );
		$runner->status( array(), array() );

		$this->assertCount( 4, $this->logs );
		$this->assertSame( 'HPOS enabled?: yes', $this->logs[0] );
		$this->assertSame( 'Compatibility mode enabled?: yes', $this->logs[1] );
		$this->assertSame( 'Unsynced orders: 0', $this->logs[2] );
		$this->assertSame( 'Orders subject to cleanup: 0', $this->logs[3] );
	}

	/**
	 * @testdox Default text output reflects the yes/no and integer values from the data sources.
	 */
	public function test_status_default_text_output_uses_source_values(): void {
		$runner = $this->build_runner( false, true, 17, false );
		$runner->status( array(), array() );

		$this->assertCount( 4, $this->logs );
		$this->assertSame( 'HPOS enabled?: no', $this->logs[0] );
		$this->assertSame( 'Compatibility mode enabled?: yes', $this->logs[1] );
		$this->assertSame( 'Unsynced orders: 17', $this->logs[2] );
		$this->assertSame( 'Orders subject to cleanup: 0', $this->logs[3] );
	}

	/**
	 * @testdox --format=json emits a single line of JSON containing the four status keys.
	 */
	public function test_status_json_output(): void {
		$runner = $this->build_runner( true, false, 4, true, 0 );

		$output = $this->capture_echo(
			function () use ( $runner ) {
				$runner->status( array(), array( 'format' => 'json' ) );
			}
		);

		$decoded = json_decode( trim( $output ), true );
		$this->assertSame(
			JSON_ERROR_NONE,
			json_last_error(),
			'Output should be valid JSON: ' . json_last_error_msg()
		);
		$this->assertIsArray( $decoded );

		$this->assertArrayHasKey( 'hpos_enabled', $decoded );
		$this->assertIsBool( $decoded['hpos_enabled'] );
		$this->assertSame( true, $decoded['hpos_enabled'] );

		$this->assertArrayHasKey( 'compatibility_mode_enabled', $decoded );
		$this->assertIsBool( $decoded['compatibility_mode_enabled'] );
		$this->assertSame( false, $decoded['compatibility_mode_enabled'] );

		$this->assertArrayHasKey( 'unsynced_orders', $decoded );
		$this->assertIsInt( $decoded['unsynced_orders'] );
		$this->assertSame( 4, $decoded['unsynced_orders'] );

		$this->assertArrayHasKey( 'orders_subject_to_cleanup', $decoded );
		$this->assertIsInt( $decoded['orders_subject_to_cleanup'] );
		$this->assertSame( 0, $decoded['orders_subject_to_cleanup'] );
	}

	/**
	 * @testdox --format=json uses native booleans rather than the yes/no strings used in text output.
	 */
	public function test_status_json_uses_native_booleans(): void {
		$runner = $this->build_runner( false, false, 0, false );

		$output = $this->capture_echo(
			function () use ( $runner ) {
				$runner->status( array(), array( 'format' => 'json' ) );
			}
		);

		$decoded = json_decode( trim( $output ), true );
		$this->assertSame(
			JSON_ERROR_NONE,
			json_last_error(),
			'Output should be valid JSON: ' . json_last_error_msg()
		);
		$this->assertIsArray( $decoded );
		$this->assertFalse( $decoded['hpos_enabled'] );
		$this->assertFalse( $decoded['compatibility_mode_enabled'] );
	}

	/**
	 * Capture the output of a callback (used for the JSON path which echoes directly).
	 *
	 * @param callable $callback The code to execute.
	 * @return string The captured output.
	 */
	private function capture_echo( callable $callback ): string {
		ob_start();
		$callback();
		return (string) ob_get_clean();
	}

	/**
	 * @testdox Cleanup count is 0 when data sync is enabled, regardless of authoritative state.
	 */
	public function test_status_cleanup_count_is_zero_when_data_sync_enabled(): void {
		$runner = $this->build_runner( true, true, 0, true, 99 );
		$runner->status( array(), array() );

		$this->assertSame( 'Orders subject to cleanup: 0', $this->logs[3] );
	}

	/**
	 * @testdox Cleanup count is 0 when HPOS is not authoritative.
	 */
	public function test_status_cleanup_count_is_zero_when_not_authoritative(): void {
		$runner = $this->build_runner( true, false, 0, false, 99 );
		$runner->status( array(), array() );

		$this->assertSame( 'Orders subject to cleanup: 0', $this->logs[3] );
	}

	/**
	 * @testdox Cleanup count uses the legacy handler value when authoritative and sync is disabled.
	 */
	public function test_status_cleanup_count_uses_legacy_handler_when_authoritative_and_sync_disabled(): void {
		$runner = $this->build_runner( true, false, 0, true, 42 );
		$runner->status( array(), array() );

		$this->assertSame( 'Orders subject to cleanup: 42', $this->logs[3] );
	}
}
