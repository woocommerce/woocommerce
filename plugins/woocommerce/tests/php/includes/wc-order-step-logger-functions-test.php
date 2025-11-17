<?php
/**
 * Unit tests for wc-order-step-logger-functions.php.
 *
 * @package WooCommerce\Tests\Functions
 */

declare(strict_types=1);
use Automattic\Jetpack\Constants;
/**
 * Class WC_Order_Step_Logger_Functions_Test
 */
class WC_Order_Step_Logger_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Original REQUEST_METHOD value to restore after tests.
	 *
	 * @var string|null
	 */
	private $original_request_method;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clean up any previous log files.
		$this->clean_up_log_files();

		// Save the original REQUEST_METHOD and set it to POST for the test.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Test setup, not user input.
		$this->original_request_method = $_SERVER['REQUEST_METHOD'] ?? null;
		$_SERVER['REQUEST_METHOD']     = 'POST';
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		// Clean up log files.
		$this->clean_up_log_files();

		// Clear the WC_LOG_THRESHOLD constant if set.
		Constants::clear_single_constant( 'WC_LOG_THRESHOLD' );

		// Reset logging settings.
		delete_option( 'woocommerce_logs_level_threshold' );

		// Restore the original REQUEST_METHOD.
		if ( null === $this->original_request_method ) {
			unset( $_SERVER['REQUEST_METHOD'] );
		} else {
			$_SERVER['REQUEST_METHOD'] = $this->original_request_method;
		}

		parent::tearDown();
	}

	/**
	 * Clean up log files created during tests.
	 */
	private function clean_up_log_files(): void {
		$log_dir = \Automattic\WooCommerce\Utilities\LoggingUtil::get_log_directory();

		if ( is_dir( $log_dir ) ) {
			$files = glob( $log_dir . 'place-order-debug-*.log' );
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
		}
	}

	/**
	 * Get log files matching the pattern.
	 *
	 * @return array Array of log file paths.
	 */
	private function get_log_files(): array {
		$log_dir = \Automattic\WooCommerce\Utilities\LoggingUtil::get_log_directory();

		$files = glob( $log_dir . 'place-order-debug-*.log' );
		return $files ? $files : array();
	}

	/**
	 * @testdox Order step logging should respect the site's logging level configuration when set to CRITICAL.
	 */
	public function test_wc_log_order_step_respects_critical_level_threshold(): void {
		// Set the logging level to CRITICAL only.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::CRITICAL );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Test message - should not be logged',
			array( 'order_object' => $order ),
			false,
			true
		);

		// End logging.
		wc_log_order_step(
			'Final step - should not be logged',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Get log files.
		$log_files = $this->get_log_files();

		// Assert that no log files were created since DEBUG is below CRITICAL threshold.
		$this->assertEmpty(
			$log_files,
			'Expected no log files to be created when logging level is set to CRITICAL'
		);

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should write logs when logging level is set to DEBUG.
	 */
	public function test_wc_log_order_step_writes_logs_at_debug_level(): void {
		// Set the logging level to DEBUG.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Test message - should be logged',
			array( 'order_object' => $order ),
			false,
			true
		);

		// Add another step.
		wc_log_order_step(
			'Another step - should be logged',
			array( 'order_object' => $order ),
			false,
			false
		);

		// End logging.
		wc_log_order_step(
			'Final step - should be logged',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Get log files.
		$log_files = $this->get_log_files();

		// Assert that log files were created.
		$this->assertNotEmpty(
			$log_files,
			'Expected log files to be created when logging level is set to DEBUG'
		);

		// Verify that the log file contains the expected messages.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- The file is created in this function and only read to verify contents in the test.
		$log_content = file_get_contents( $log_files[0] );
		$this->assertStringContainsString( 'Test message - should be logged', $log_content );
		$this->assertStringContainsString( 'Another step - should be logged', $log_content );
		$this->assertStringContainsString( 'Final step - should be logged', $log_content );

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should respect the site's logging level configuration when set via option.
	 */
	public function test_wc_log_order_step_respects_option_level_threshold(): void {
		// Set the logging level to ERROR via option (not constant).
		update_option( 'woocommerce_logs_level_threshold', WC_Log_Levels::ERROR );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Test message - should not be logged',
			array( 'order_object' => $order ),
			false,
			true
		);

		// End logging.
		wc_log_order_step(
			'Final step - should not be logged',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Get log files.
		$log_files = $this->get_log_files();

		// Assert that no log files were created since DEBUG is below ERROR threshold.
		$this->assertEmpty(
			$log_files,
			'Expected no log files to be created when logging level is set to ERROR via option'
		);

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should clean up logs after successful checkout.
	 */
	public function test_wc_log_order_step_cleanup_on_final_step(): void {
		// Set the logging level to DEBUG.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Step 1',
			array( 'order_object' => $order ),
			false,
			true
		);

		// Add another step with unique message.
		wc_log_order_step(
			'Step 2',
			array( 'order_object' => $order ),
			false,
			false
		);

		// End logging with unique final step.
		wc_log_order_step(
			'Step 3 - Final',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Verify that _debug_log_source_pending_deletion meta is set.
		$order_meta = $order->get_meta( '_debug_log_source_pending_deletion' );
		$this->assertNotEmpty(
			$order_meta,
			'Expected _debug_log_source_pending_deletion meta to be set after final step'
		);

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should not clean up logs when steps are repeated (race condition detected).
	 */
	public function test_wc_log_order_step_no_cleanup_on_repeated_steps(): void {
		// Set the logging level to DEBUG.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Step 1',
			array( 'order_object' => $order ),
			false,
			true
		);

		// Add the same step twice (simulating a race condition or recursion).
		wc_log_order_step(
			'Step 1',
			array( 'order_object' => $order ),
			false,
			false
		);

		// End logging.
		wc_log_order_step(
			'Step 2 - Final',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Verify that _debug_log_source_pending_deletion meta is NOT set due to duplicate steps.
		$order_meta = $order->get_meta( '_debug_log_source_pending_deletion' );
		$this->assertEmpty(
			$order_meta,
			'Expected _debug_log_source_pending_deletion NOT to be set when steps are repeated'
		);

		// Clean up the order.
		$order->delete( true );
	}
}
